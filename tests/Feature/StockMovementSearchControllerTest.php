<?php

use App\Models\Product;
use App\Models\ProductUnit;

/*
|--------------------------------------------------------------------------
| StockMovementController::search(): buscador de productos de "Nuevo Movimiento de Stock"
|--------------------------------------------------------------------------
| Reemplaza el catálogo completo que antes se mandaba entero al navegador en create():
| ahora cada búsqueda trae como máximo StockMovementController::SEARCH_LIMIT productos.
*/

it('exige estar autenticado', function () {
    $this->get(route('stock-movements.products.search'))->assertRedirect(route('login'));
});

it('exige el permiso "ver stock-movements"', function () {
    $this->actingAs(userWith())->get(route('stock-movements.products.search'))->assertForbidden();
});

it('nunca devuelve más de 15 resultados, aunque haya muchos más productos', function () {
    $this->actingAs(userWith('ver stock-movements'));

    Product::factory()->count(20)->create()->each(function ($p) {
        ProductUnit::factory()->create(['product_id' => $p->id, 'is_base' => true]);
    });

    $response = $this->getJson(route('stock-movements.products.search'))->assertOk();

    expect($response->json('units'))->toHaveCount(15);
});

it('busca por nombre de producto, nombre de presentación o SKU', function () {
    $this->actingAs(userWith('ver stock-movements'));

    $product = Product::factory()->create(['name' => 'Martillo de uña', 'sku' => 'HM-0001']);
    $unit = ProductUnit::factory()->create(['product_id' => $product->id, 'is_base' => true, 'name' => 'Caja x12']);
    $other = Product::factory()->create(['name' => 'Destornillador']);
    $otherUnit = ProductUnit::factory()->create(['product_id' => $other->id, 'is_base' => true]);

    foreach (['Martillo', 'Caja x12', 'HM-0001'] as $term) {
        $ids = $this->getJson(route('stock-movements.products.search', ['q' => $term]))->json('units.*.id');
        expect($ids)->toContain($unit->id)->and($ids)->not->toContain($otherUnit->id);
    }
});

it('trae el nombre del producto y la presentación juntos, con el factor', function () {
    $this->actingAs(userWith('ver stock-movements'));

    $product = Product::factory()->create(['name' => 'Tornillo autorroscante']);
    $unit = ProductUnit::factory()->create(['product_id' => $product->id, 'is_base' => false, 'name' => 'Caja x100', 'factor' => 100]);

    $result = $this->getJson(route('stock-movements.products.search', ['q' => 'Tornillo']))->json('units.0');

    expect($result['id'])->toBe($unit->id)
        ->and($result['label'])->toBe('Tornillo autorroscante — Caja x100')
        ->and($result['factor'])->toBe(100);
});

it('un producto inactivo no aparece en la búsqueda', function () {
    $this->actingAs(userWith('ver stock-movements'));

    $product = Product::factory()->create(['name' => 'Producto Inactivo', 'is_active' => false]);
    ProductUnit::factory()->create(['product_id' => $product->id, 'is_base' => true]);

    $response = $this->getJson(route('stock-movements.products.search', ['q' => 'Inactivo']))->assertOk();

    expect($response->json('units'))->toBe([]);
});
