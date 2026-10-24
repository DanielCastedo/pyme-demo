<?php

use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Unit;

/*
|--------------------------------------------------------------------------
| ProductUnitController: CRUD, unidades base, factores y restricciones de borrado
|--------------------------------------------------------------------------
*/

/** Datos válidos de una unidad. */
function productUnitPayload(Product $product, array $overrides = []): array
{
    return $overrides + [
        'product_id' => $product->id,
        'unit_id' => Unit::firstOrCreate(['name' => 'Caja Master x24'])->id, // la presentación elige una unidad del catálogo
        'factor' => '24.0000',
        'price' => '360.00',
        'is_base' => '0',
        'barcode' => '7771234567890',
    ];
}

/** Casos inválidos para store y update. */
dataset('invalid product unit fields', [
    'sin producto' => ['product_id', ''],
    'producto inexistente' => ['product_id', 99999],
    'sin unidad de medida' => ['unit_id', ''],
    'unidad de medida inexistente' => ['unit_id', 99999],
    'sin factor' => ['factor', ''],
    'factor cero o negativo' => ['factor', '0'],
    'sin precio' => ['price', ''],
    'precio negativo' => ['price', '-10'],
]);

// ─────────────────────────────────────────────────────────────────────────────
// Autenticación y permisos
// ─────────────────────────────────────────────────────────────────────────────

describe('acceso', function () {
    it('redirige al login a un visitante', function () {
        $this->get(route('product-units.index'))->assertRedirect(route('login'));
    });

    it('rechaza (403) a un usuario sin permisos en todas las acciones', function () {
        $this->actingAs(userWith());
        $unit = ProductUnit::factory()->create();

        $this->get(route('product-units.index'))->assertForbidden();
        $this->get(route('product-units.show', $unit))->assertForbidden();
        $this->get(route('product-units.edit', $unit))->assertForbidden();
        $this->post(route('product-units.store'), productUnitPayload($unit->product))->assertForbidden();
        $this->put(route('product-units.update', $unit), productUnitPayload($unit->product))->assertForbidden();
        $this->delete(route('product-units.destroy', $unit))->assertForbidden();
    });

    it('aplica el permiso específico de cada acción (la policy)', function () {
        $this->actingAs(userWith('ver product-units'));
        $unit = ProductUnit::factory()->create();

        $this->get(route('product-units.index'))->assertOk();
        $this->get(route('product-units.show', $unit))->assertOk();
        $this->get(route('product-units.edit', $unit))->assertForbidden();
        $this->post(route('product-units.store'), productUnitPayload($unit->product))->assertForbidden();
        $this->put(route('product-units.update', $unit), productUnitPayload($unit->product))->assertForbidden();
        $this->delete(route('product-units.destroy', $unit))->assertForbidden();

        expect(ProductUnit::where('id', $unit->id)->exists())->toBeTrue();
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// index
// ─────────────────────────────────────────────────────────────────────────────

describe('index', function () {
    beforeEach(fn () => $this->actingAs(userWith('ver product-units')));

    it('lista las unidades y pagina de a 10', function () {
        $product = Product::factory()->create();
        foreach (range(1, 12) as $i) {
            ProductUnit::factory()->create([
                'product_id' => $product->id,
                'name' => sprintf('Unidad %02d', $i),
                'barcode' => sprintf('BC-%04d', $i),
            ]);
        }

        $response = $this->get(route('product-units.index'))->assertOk()->assertViewIs('product-units.index');

        $units = $response->viewData('productUnits');

        expect($units->total())->toBeGreaterThanOrEqual(12)
            ->and($units->perPage())->toBe(10);
    });

    it('busca por nombre, código de barras o producto (filter[buscar])', function (string $term, string $expectedName) {
        $p1 = Product::factory()->create(['name' => 'Bujía Iridium']);
        $p2 = Product::factory()->create(['name' => 'Líquido de Frenos']);

        ProductUnit::factory()->create(['product_id' => $p1->id, 'name' => 'Caja x4 Bujías', 'barcode' => 'BAR-BUJ-01']);
        ProductUnit::factory()->create(['product_id' => $p2->id, 'name' => 'Botella 500ml', 'barcode' => 'BAR-FRN-02']);

        $names = $this->get(route('product-units.index', ['filter' => ['buscar' => $term]]))
            ->viewData('productUnits')->pluck('name')->all();

        expect($names)->toContain($expectedName);
    })->with([
        'por nombre de unidad' => ['Botella', 'Botella 500ml'],
        'por código de barras' => ['BAR-BUJ-01', 'Caja x4 Bujías'],
        'por nombre de producto' => ['Iridium', 'Caja x4 Bujías'],
    ]);

    it('ordena por los campos permitidos y rechaza los no permitidos con 400', function () {
        $this->get(route('product-units.index', ['sort' => 'name']))->assertOk();
        $this->get(route('product-units.index', ['sort' => '-price']))->assertOk();
        $this->get(route('product-units.index', ['sort' => 'is_base']))->assertStatus(400);
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// store
// ─────────────────────────────────────────────────────────────────────────────

describe('store', function () {
    beforeEach(fn () => $this->actingAs(userWith('ver product-units', 'crear product-units')));

    it('crea la unidad, redirige al listado y avisa', function () {
        $product = Product::factory()->create();

        $this->post(route('product-units.store'), productUnitPayload($product))
            ->assertRedirect(route('product-units.index'))
            ->assertSessionHas('success', 'Unidad de producto registrada exitosamente.');

        $this->assertDatabaseHas('product_units', [
            'product_id' => $product->id,
            'name' => 'Caja Master x24',
            'factor' => 24.0000,
            'price' => 360.00,
        ]);
    });

    it('desmarca la unidad base anterior si la nueva se registra como base', function () {
        $product = Product::factory()->create();
        $oldBase = ProductUnit::factory()->base()->create(['product_id' => $product->id]);

        $this->post(route('product-units.store'), productUnitPayload($product, [
            'unit_id' => Unit::firstOrCreate(['name' => 'Nueva Base'])->id,
            'factor' => '1.0000',
            'is_base' => '1',
            'barcode' => 'NEW-BASE-BC',
        ]))->assertRedirect(route('product-units.index'));

        expect($oldBase->fresh()->is_base)->toBeFalse()
            ->and(ProductUnit::where('product_id', $product->id)->where('is_base', true)->value('name'))->toBe('Nueva Base');
    });

    it('valida los campos y no crea nada', function (string $field, mixed $invalidValue) {
        $product = Product::factory()->create();
        $initialCount = ProductUnit::count();

        $this->from(route('product-units.index'))
            ->post(route('product-units.store'), productUnitPayload($product, [$field => $invalidValue]))
            ->assertRedirect(route('product-units.index'))
            ->assertSessionHasErrors($field);

        expect(ProductUnit::count())->toBe($initialCount);
    })->with('invalid product unit fields');
});

// ─────────────────────────────────────────────────────────────────────────────
// show y edit
// ─────────────────────────────────────────────────────────────────────────────

describe('show y edit', function () {
    it('muestra el detalle de la unidad', function () {
        $this->actingAs(userWith('ver product-units'));
        $unit = ProductUnit::factory()->create();

        $this->get(route('product-units.show', $unit))
            ->assertOk()
            ->assertViewIs('product-units.show')
            ->assertViewHas('productUnit', fn ($u) => $u->id === $unit->id);
    });

    it('muestra el formulario de edición con productos disponibles', function () {
        $this->actingAs(userWith('ver product-units', 'editar product-units'));
        $unit = ProductUnit::factory()->create();

        $this->get(route('product-units.edit', $unit))
            ->assertOk()
            ->assertViewIs('product-units.edit')
            ->assertViewHas('productUnit', fn ($u) => $u->id === $unit->id)
            ->assertViewHas('products');
    });

    it('responde 404 si la unidad no existe', function () {
        $this->actingAs(userWith('ver product-units', 'editar product-units'));
        $this->get(route('product-units.show', 99999))->assertNotFound();
        $this->get(route('product-units.edit', 99999))->assertNotFound();
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// update
// ─────────────────────────────────────────────────────────────────────────────

describe('update', function () {
    beforeEach(fn () => $this->actingAs(userWith('ver product-units', 'editar product-units')));

    it('actualiza los datos, redirige al listado y avisa', function () {
        $unit = ProductUnit::factory()->create(['name' => 'Nombre Viejo']);

        $this->put(route('product-units.update', $unit), [
            'product_id' => $unit->product_id,
            'unit_id' => Unit::firstOrCreate(['name' => 'Nombre Nuevo'])->id,
            'factor' => '12.0000',
            'price' => '150.00',
            'barcode' => $unit->barcode,
        ])
            ->assertRedirect(route('product-units.index'))
            ->assertSessionHas('success', 'Unidad de producto actualizada exitosamente.');

        expect($unit->refresh()->name)->toBe('Nombre Nuevo')
            ->and((float) $unit->price)->toBe(150.00);
    });

    it('responde 404 si la unidad no existe', function () {
        $product = Product::factory()->create();
        $this->put(route('product-units.update', 99999), productUnitPayload($product))->assertNotFound();
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// destroy
// ─────────────────────────────────────────────────────────────────────────────

describe('destroy', function () {
    beforeEach(fn () => $this->actingAs(userWith('ver product-units', 'eliminar product-units')));

    it('elimina una unidad no base sin registros asociados', function () {
        $unit = ProductUnit::factory()->create(['is_base' => false]);

        $this->delete(route('product-units.destroy', $unit))
            ->assertRedirect(route('product-units.index'))
            ->assertSessionHas('success', 'Unidad de producto eliminada exitosamente.');

        expect(ProductUnit::where('id', $unit->id)->exists())->toBeFalse();
    });

    it('impide eliminar la unidad base del producto', function () {
        $unit = ProductUnit::factory()->base()->create();

        $this->delete(route('product-units.destroy', $unit))
            ->assertRedirect(route('product-units.index'))
            ->assertSessionHas('error', 'No se puede eliminar la unidad base del producto. Marca otra unidad como base primero.');

        expect(ProductUnit::where('id', $unit->id)->exists())->toBeTrue();
    });

    it('impide eliminar una unidad con pedidos o cotizaciones asociadas', function () {
        $unit = ProductUnit::factory()->create(['is_base' => false]);
        $customer = Customer::factory()->create();

        // Asociarle una línea de cotización
        $quotation = quotationFor($customer, 'COT-UNIT-1');
        $quotation->items()->create([
            'product_id' => $unit->product_id,
            'product_unit_id' => $unit->id,
            'conversion_factor' => 1,
            'quantity' => 1,
            'unit_price' => 50,
            'subtotal' => 50,
        ]);

        $this->delete(route('product-units.destroy', $unit))
            ->assertRedirect(route('product-units.index'))
            ->assertSessionHas('error', 'No se puede eliminar: esta unidad tiene pedidos, cotizaciones o movimientos de stock asociados.');

        expect(ProductUnit::where('id', $unit->id)->exists())->toBeTrue();
    });

    it('responde 404 si la unidad no existe', function () {
        $this->delete(route('product-units.destroy', 99999))->assertNotFound();
    });
});
