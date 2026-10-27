<?php

use App\Models\Branch;
use App\Models\Cashbox;
use App\Models\Category;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\ProductWarehouse;
use App\Models\Warehouse;
use App\Services\OrderService;
use App\Services\PurchaseService;
use App\Services\SaleService;

/*
|--------------------------------------------------------------------------
| Sucursales que comparten almacén
|--------------------------------------------------------------------------
| Un almacén tiene una sucursal dueña pero puede atender a varias: la venta,
| la compra y el pedido quedan anotados en la sucursal que los hace, y el
| stock sale/entra del mismo almacén compartido.
*/

/** Tres sucursales usando un solo almacén (dueña: la primera), con 10 unidades en stock. */
function sharedWarehouseWorld(): array
{
    $central = testBranch();
    $norte = Branch::factory()->create(['name' => 'Norte']);
    $sur = Branch::factory()->create(['name' => 'Sur']);
    $warehouse = Warehouse::factory()->create(['branche_id' => $central->id, 'name' => 'Almacén Compartido']);
    $warehouse->branches()->syncWithoutDetaching([$norte->id, $sur->id]);

    $user = userWith('ver sales', 'crear sales', 'ver orders', 'crear orders', 'editar orders');
    $product = Product::factory()->create(['category_id' => Category::factory()->create()->id]);
    $unit = ProductUnit::factory()->create(['product_id' => $product->id, 'factor' => 1, 'is_base' => true, 'price' => 50]);
    app(PurchaseService::class)->register(['warehouse_id' => $warehouse->id], [['product_unit_id' => $unit->id, 'quantity' => 10, 'unit_cost' => 20]], $user);

    return compact('central', 'norte', 'sur', 'warehouse', 'user', 'product', 'unit') + [
        'cash' => PaymentMethod::factory()->create(['is_cash' => true]),
        'boxNorte' => Cashbox::factory()->create(['branche_id' => $norte->id, 'balance' => 0, 'cash' => 0]),
    ];
}

beforeEach(function () {
    $this->seed(Database\Seeders\TypeSeeder::class);
});

it('la sucursal dueña siempre usa su almacén', function () {
    $warehouse = Warehouse::factory()->create(['branche_id' => testBranch()->id]);

    expect($warehouse->branchIds())->toBe([testBranch()->id])
        ->and(Warehouse::forBranch(testBranch()->id)->pluck('id')->all())->toContain($warehouse->id);
});

it('vende desde otra sucursal sobre el almacén compartido: la venta es de esa sucursal', function () {
    $w = sharedWarehouseWorld();

    $sale = app(SaleService::class)->register([
        'warehouse_id' => $w['warehouse']->id,
        'branche_id' => $w['norte']->id,
        'payment_id' => $w['cash']->id,
        'cashbox_id' => $w['boxNorte']->id,
    ], [['product_unit_id' => $w['unit']->id, 'quantity' => 3]], $w['user']);

    expect($sale->branche_id)->toBe($w['norte']->id)
        ->and((float) ProductWarehouse::where('product_id', $w['product']->id)->where('warehouse_id', $w['warehouse']->id)->value('stock'))->toBe(7.0)
        ->and((float) $w['boxNorte']->fresh()->balance)->toBe(150.0);
});

it('sin sucursal elegida, toma la de la caja si usa el almacén', function () {
    $w = sharedWarehouseWorld();

    $sale = app(SaleService::class)->register([
        'warehouse_id' => $w['warehouse']->id,
        'payment_id' => $w['cash']->id,
        'cashbox_id' => $w['boxNorte']->id,
    ], [['product_unit_id' => $w['unit']->id, 'quantity' => 1]], $w['user']);

    expect($sale->branche_id)->toBe($w['norte']->id);
});

it('rechaza una sucursal que no usa el almacén y una caja de otra sucursal', function () {
    $w = sharedWarehouseWorld();
    $otra = Branch::factory()->create(['name' => 'Otra']);
    $data = ['warehouse_id' => $w['warehouse']->id, 'payment_id' => $w['cash']->id, 'cashbox_id' => $w['boxNorte']->id];
    $items = [['product_unit_id' => $w['unit']->id, 'quantity' => 1]];

    expect(fn () => app(SaleService::class)->register($data + ['branche_id' => $otra->id], $items, $w['user']))
        ->toThrow(RuntimeException::class, 'no usa el almacén');

    expect(fn () => app(SaleService::class)->register($data + ['branche_id' => $w['sur']->id], $items, $w['user']))
        ->toThrow(RuntimeException::class, 'no es de la sucursal');
});

it('pedidos y compras también quedan en la sucursal elegida', function () {
    $w = sharedWarehouseWorld();

    $pedido = app(OrderService::class)->create(
        ['warehouse_id' => $w['warehouse']->id, 'branche_id' => $w['sur']->id, 'delivery_date' => now()->addDay()->toDateString()],
        [['product_unit_id' => $w['unit']->id, 'quantity' => 2]],
        $w['user']
    );
    $purchase = app(PurchaseService::class)->register(
        ['warehouse_id' => $w['warehouse']->id, 'branche_id' => $w['norte']->id],
        [['product_unit_id' => $w['unit']->id, 'quantity' => 5, 'unit_cost' => 20]],
        $w['user']
    );

    expect($pedido->branche_id)->toBe($w['sur']->id)
        ->and($purchase->branche_id)->toBe($w['norte']->id)
        ->and((float) ProductWarehouse::where('product_id', $w['product']->id)->where('warehouse_id', $w['warehouse']->id)->value('stock'))->toBe(15.0);
});

it('desde la pantalla de almacenes se eligen las sucursales que lo usan (la dueña siempre queda)', function () {
    $norte = Branch::factory()->create(['name' => 'Norte']);
    $warehouse = Warehouse::factory()->create(['branche_id' => testBranch()->id]);

    $this->actingAs(userWith('ver warehouses', 'editar warehouses'))
        ->put(route('warehouses.update', $warehouse), [
            'branche_id' => testBranch()->id,
            'name' => $warehouse->name,
            'code' => $warehouse->code,
            'branch_ids' => [$norte->id],
        ])->assertRedirect(route('warehouses.index'));

    expect($warehouse->fresh()->branchIds())->toEqualCanonicalizing([testBranch()->id, $norte->id]);
});
