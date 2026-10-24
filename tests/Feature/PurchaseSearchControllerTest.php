<?php

use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\StockBatch;
use App\Models\Warehouse;

/*
|--------------------------------------------------------------------------
| PurchaseController::search(): buscador de productos de "Registrar compra"
|--------------------------------------------------------------------------
| Reemplaza el catálogo completo que antes se mandaba entero al navegador (formData()):
| ahora cada búsqueda trae como máximo PurchaseController::SEARCH_LIMIT productos.
*/

beforeEach(function () {
    $this->branch = testBranch();
    $this->warehouse = Warehouse::factory()->create(['branche_id' => $this->branch->id]);
});

it('exige estar autenticado', function () {
    $this->get(route('purchases.products.search'))->assertRedirect(route('login'));
});

it('exige el permiso "ver purchases"', function () {
    $this->actingAs(userWith())->get(route('purchases.products.search'))->assertForbidden();
});

it('nunca devuelve más de 15 resultados, aunque haya muchos más productos', function () {
    $this->actingAs(userWith('ver purchases'));

    Product::factory()->count(20)->create()->each(function ($p) {
        ProductUnit::factory()->create(['product_id' => $p->id, 'is_base' => true, 'factor' => 1]);
    });

    $response = $this->getJson(route('purchases.products.search'))->assertOk();

    expect($response->json('units'))->toHaveCount(15);
});

it('busca por nombre, SKU o código de barras', function () {
    $this->actingAs(userWith('ver purchases'));

    $product = Product::factory()->create(['name' => 'Martillo de uña', 'sku' => 'HM-0001']);
    $unit = ProductUnit::factory()->create(['product_id' => $product->id, 'is_base' => true, 'barcode' => '7591234567890']);
    $other = Product::factory()->create(['name' => 'Destornillador']);
    ProductUnit::factory()->create(['product_id' => $other->id, 'is_base' => true]);

    foreach (['Martillo', 'HM-0001', '7591234567890'] as $term) {
        $ids = $this->getJson(route('purchases.products.search', ['q' => $term]))->json('units.*.id');
        expect($ids)->toContain($unit->id)->and($ids)->not->toContain($other->id ?? null);
    }
});

it('busca por código exacto (barcode o SKU) y devuelve como mucho uno', function () {
    $this->actingAs(userWith('ver purchases'));

    $product = Product::factory()->create(['sku' => 'FG-0001']);
    $unit = ProductUnit::factory()->create(['product_id' => $product->id, 'is_base' => true, 'barcode' => '7770001112223']);

    $byBarcode = $this->getJson(route('purchases.products.search', ['code' => '7770001112223']))->assertOk();
    expect($byBarcode->json('units'))->toHaveCount(1)
        ->and($byBarcode->json('units.0.id'))->toBe($unit->id);

    $bySku = $this->getJson(route('purchases.products.search', ['code' => 'FG-0001']));
    expect($bySku->json('units.0.id'))->toBe($unit->id);
});

it('busca por ids puntuales sin limitarse a 15 (para redibujar líneas ya elegidas)', function () {
    $this->actingAs(userWith('ver purchases'));

    $units = Product::factory()->count(3)->create()->map(fn ($p) => ProductUnit::factory()->create(['product_id' => $p->id, 'is_base' => true]));

    $response = $this->getJson(route('purchases.products.search', ['ids' => $units->pluck('id')->implode(',')]))->assertOk();

    expect($response->json('units.*.id'))->toEqualCanonicalizing($units->pluck('id')->all());
});

it('sugiere el último costo pagado (el lote más reciente), convertido a la presentación buscada', function () {
    $this->actingAs(userWith('ver purchases'));

    $product = Product::factory()->create();
    $unit = ProductUnit::factory()->create(['product_id' => $product->id, 'is_base' => true, 'factor' => 1]);

    StockBatch::create(['product_id' => $product->id, 'warehouse_id' => $this->warehouse->id, 'received_at' => now()->subDay(), 'qty_initial' => 10, 'qty_remaining' => 10, 'unit_cost' => 20]);
    StockBatch::create(['product_id' => $product->id, 'warehouse_id' => $this->warehouse->id, 'received_at' => now(), 'qty_initial' => 5, 'qty_remaining' => 5, 'unit_cost' => 35]); // el más reciente

    $result = $this->getJson(route('purchases.products.search', ['ids' => (string) $unit->id]))->json('units.0');

    expect((float) $result['cost'])->toBe(35.0);
});

it('un producto inactivo no aparece en la búsqueda', function () {
    $this->actingAs(userWith('ver purchases'));

    $product = Product::factory()->create(['name' => 'Producto Inactivo', 'is_active' => false]);
    ProductUnit::factory()->create(['product_id' => $product->id, 'is_base' => true]);

    $response = $this->getJson(route('purchases.products.search', ['q' => 'Inactivo']))->assertOk();

    expect($response->json('units'))->toBe([]);
});
