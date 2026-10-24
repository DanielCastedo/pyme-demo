<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductWarehouse;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| ProductWarehouseController: Stock por almacén, clave compuesta y permisos
|--------------------------------------------------------------------------
*/

function createProductWarehouseRecord(Product $product, Warehouse $warehouse, array $overrides = []): ProductWarehouse
{
    DB::table('product_warehouses')->insert($overrides + [
        'product_id' => $product->id,
        'warehouse_id' => $warehouse->id,
        'stock' => 0,
        'min_stock' => 5,
        'avg_cost' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return ProductWarehouse::where('product_id', $product->id)
        ->where('warehouse_id', $warehouse->id)
        ->firstOrFail();
}

// ─────────────────────────────────────────────────────────────────────────────
// Autenticación y permisos
// ─────────────────────────────────────────────────────────────────────────────

describe('acceso', function () {
    it('redirige al login a un visitante', function () {
        $this->get(route('product-warehouses.index'))->assertRedirect(route('login'));
    });

    it('rechaza (403) a un usuario sin permisos en todas las acciones', function () {
        $this->actingAs(userWith());
        $branch = testBranch();
        $warehouse = Warehouse::factory()->create(['branche_id' => $branch->id]);
        $product = Product::factory()->create();
        $record = createProductWarehouseRecord($product, $warehouse);
        $combo = "{$product->id}-{$warehouse->id}";

        $this->get(route('product-warehouses.index'))->assertForbidden();
        $this->get(route('product-warehouses.show', $combo))->assertForbidden();
        $this->get(route('product-warehouses.edit', $combo))->assertForbidden();
        $this->post(route('product-warehouses.store'), [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
        ])->assertForbidden();
        $this->put(route('product-warehouses.update', $combo), ['min_stock' => 10])->assertForbidden();
        $this->delete(route('product-warehouses.destroy', $combo))->assertForbidden();
    });

    it('aplica el permiso específico de cada acción (la policy)', function () {
        $this->actingAs(userWith('ver product-warehouses'));
        $branch = testBranch();
        $warehouse = Warehouse::factory()->create(['branche_id' => $branch->id]);
        $product = Product::factory()->create();
        createProductWarehouseRecord($product, $warehouse);
        $combo = "{$product->id}-{$warehouse->id}";

        $this->get(route('product-warehouses.index'))->assertOk();
        $this->get(route('product-warehouses.show', $combo))->assertOk();
        $this->get(route('product-warehouses.edit', $combo))->assertForbidden();
        $this->delete(route('product-warehouses.destroy', $combo))->assertForbidden();
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// index
// ─────────────────────────────────────────────────────────────────────────────

describe('index', function () {
    beforeEach(fn () => $this->actingAs(userWith('ver product-warehouses')));

    it('lista los registros de stock por almacén y pagina de a 10', function () {
        $branch = testBranch();
        $warehouse = Warehouse::factory()->create(['branche_id' => $branch->id]);

        foreach (range(1, 12) as $i) {
            $product = Product::factory()->create(['name' => sprintf('Prod %02d', $i)]);
            createProductWarehouseRecord($product, $warehouse);
        }

        $response = $this->get(route('product-warehouses.index'))->assertOk()->assertViewIs('product-warehouses.index');

        $items = $response->viewData('productWarehouses');

        expect($items->total())->toBeGreaterThanOrEqual(12)
            ->and($items->perPage())->toBe(10);
    });

    it('busca por nombre de producto, SKU o almacén (filter[buscar])', function (string $term, string $expectedSku) {
        $branch = testBranch();
        $w1 = Warehouse::factory()->create(['branche_id' => $branch->id, 'name' => 'Depósito Los Pozos']);
        $w2 = Warehouse::factory()->create(['branche_id' => $branch->id, 'name' => 'Almacén 4to Anillo']);

        $p1 = Product::factory()->create(['name' => 'Batería Bosch 65Ah', 'sku' => 'SKU-BOSCH-65']);
        $p2 = Product::factory()->create(['name' => 'Filtro Mann W712', 'sku' => 'SKU-MANN-712']);

        createProductWarehouseRecord($p1, $w1);
        createProductWarehouseRecord($p2, $w2);

        $results = $this->get(route('product-warehouses.index', ['filter' => ['buscar' => $term]]))
            ->viewData('productWarehouses')->pluck('product.sku')->all();

        expect($results)->toContain($expectedSku);
    })->with([
        'por SKU' => ['BOSCH-65', 'SKU-BOSCH-65'],
        'por nombre producto' => ['Mann', 'SKU-MANN-712'],
        'por nombre almacén' => ['Pozos', 'SKU-BOSCH-65'],
    ]);

    it('ordena por los campos permitidos y rechaza los no permitidos con 400', function () {
        $this->get(route('product-warehouses.index', ['sort' => 'stock']))->assertOk();
        $this->get(route('product-warehouses.index', ['sort' => '-min_stock']))->assertOk();
        $this->get(route('product-warehouses.index', ['sort' => 'created_at']))->assertStatus(400);
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// store
// ─────────────────────────────────────────────────────────────────────────────

describe('store', function () {
    beforeEach(fn () => $this->actingAs(userWith('ver product-warehouses', 'crear product-warehouses')));

    it('crea el registro de stock por almacén exitosamente', function () {
        $branch = testBranch();
        $warehouse = Warehouse::factory()->create(['branche_id' => $branch->id]);
        $product = Product::factory()->create();

        $this->post(route('product-warehouses.store'), [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'min_stock' => 10,
        ])
            ->assertRedirect(route('product-warehouses.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('product_warehouses', [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'min_stock' => 10,
        ]);
    });

    it('no permite registrar el mismo producto dos veces en el mismo almacén', function () {
        $branch = testBranch();
        $warehouse = Warehouse::factory()->create(['branche_id' => $branch->id]);
        $product = Product::factory()->create();

        createProductWarehouseRecord($product, $warehouse);

        $this->from(route('product-warehouses.index'))
            ->post(route('product-warehouses.store'), [
                'product_id' => $product->id,
                'warehouse_id' => $warehouse->id,
            ])
            ->assertRedirect(route('product-warehouses.index'))
            ->assertSessionHasErrors(['warehouse_id']);
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// show, edit, update y destroy
// ─────────────────────────────────────────────────────────────────────────────

describe('show, edit, update y destroy', function () {
    it('muestra el detalle y el formulario de edición por clave compuesta', function () {
        $this->actingAs(userWith('ver product-warehouses', 'editar product-warehouses'));
        $branch = testBranch();
        $warehouse = Warehouse::factory()->create(['branche_id' => $branch->id]);
        $product = Product::factory()->create();

        createProductWarehouseRecord($product, $warehouse);
        $combo = "{$product->id}-{$warehouse->id}";

        $this->get(route('product-warehouses.show', $combo))
            ->assertOk()
            ->assertViewIs('product-warehouses.show');

        $this->get(route('product-warehouses.edit', $combo))
            ->assertOk()
            ->assertViewIs('product-warehouses.edit');
    });

    it('actualiza el stock mínimo del producto en el almacén', function () {
        $this->actingAs(userWith('ver product-warehouses', 'editar product-warehouses'));
        $branch = testBranch();
        $warehouse = Warehouse::factory()->create(['branche_id' => $branch->id]);
        $product = Product::factory()->create();

        createProductWarehouseRecord($product, $warehouse, ['min_stock' => 2]);
        $combo = "{$product->id}-{$warehouse->id}";

        $this->put(route('product-warehouses.update', $combo), ['min_stock' => 15])
            ->assertRedirect(route('product-warehouses.index'))
            ->assertSessionHas('success');

        expect(DB::table('product_warehouses')
            ->where('product_id', $product->id)
            ->where('warehouse_id', $warehouse->id)
            ->value('min_stock'))->toBe(15);
    });

    it('elimina el registro de stock cuando no queda mercadería (stock 0)', function () {
        $this->actingAs(userWith('ver product-warehouses', 'eliminar product-warehouses'));
        $branch = testBranch();
        $warehouse = Warehouse::factory()->create(['branche_id' => $branch->id]);
        $product = Product::factory()->create();

        createProductWarehouseRecord($product, $warehouse, ['stock' => 0]);
        $combo = "{$product->id}-{$warehouse->id}";

        $this->delete(route('product-warehouses.destroy', $combo))
            ->assertRedirect(route('product-warehouses.index'))
            ->assertSessionHas('success');

        expect(DB::table('product_warehouses')
            ->where('product_id', $product->id)
            ->where('warehouse_id', $warehouse->id)
            ->exists())->toBeFalse();
    });

    it('impide eliminar el registro si todavía existe stock físico', function () {
        $this->actingAs(userWith('ver product-warehouses', 'eliminar product-warehouses'));
        $branch = testBranch();
        $warehouse = Warehouse::factory()->create(['branche_id' => $branch->id]);
        $product = Product::factory()->create();

        createProductWarehouseRecord($product, $warehouse, ['stock' => 10]);
        $combo = "{$product->id}-{$warehouse->id}";

        $this->delete(route('product-warehouses.destroy', $combo))
            ->assertRedirect(route('product-warehouses.index'))
            ->assertSessionHas('error', 'No se puede eliminar: aún hay stock en lotes. Sácalo con un movimiento de salida primero.');

        expect(DB::table('product_warehouses')
            ->where('product_id', $product->id)
            ->where('warehouse_id', $warehouse->id)
            ->exists())->toBeTrue();
    });

    it('responde 404 si la clave compuesta tiene formato inválido o no existe', function () {
        $this->actingAs(userWith('ver product-warehouses'));
        $this->get(route('product-warehouses.show', 'formato-invalido-sin-guion'))->assertNotFound();
        $this->get(route('product-warehouses.show', '99999-88888'))->assertNotFound();
    });
});
