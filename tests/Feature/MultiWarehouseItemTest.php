<?php

use App\Models\Branch;
use App\Models\Cashbox;
use App\Models\Category;
use App\Models\Customer;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\ProductWarehouse;
use App\Models\Type;
use App\Models\Warehouse;
use App\Services\ConsignmentService;
use App\Services\OrderService;
use App\Services\PurchaseService;
use App\Services\SaleService;

/*
|--------------------------------------------------------------------------
| Cada línea de venta/pedido/consignación elige su propio almacén
|--------------------------------------------------------------------------
| El caso real que motivó esto: un producto con stock repartido entre dos
| almacenes (incluso de sucursales distintas) — antes solo se podía vender
| desde uno solo. Ahora cada línea del carrito puede salir de un almacén
| distinto, y el sistema arma un movimiento de stock por cada almacén usado.
*/

/**
 * Dos almacenes (de sucursales DISTINTAS, el caso real) con el mismo producto en stock
 * repartido entre ambos, un usuario con todos los permisos de venta y una caja de cobro.
 */
function twoWarehouseWorld(): array
{
    $branch1 = testBranch();
    $branch2 = Branch::factory()->create(['name' => 'Sucursal 2']);
    $wh1 = Warehouse::factory()->create(['branche_id' => $branch1->id, 'name' => 'Almacén Central']);
    $wh2 = Warehouse::factory()->create(['branche_id' => $branch2->id, 'name' => 'Almacén Norte']);

    $user = userWith(
        'ver sales', 'crear sales', 'ventas.credito', 'ventas.anular',
        'ver orders', 'crear orders', 'editar orders',
        'ver consignments', 'crear consignments', 'consignaciones.liquidar'
    );

    $product = Product::factory()->create(['category_id' => Category::factory()->create()->id, 'name' => 'Grifo de lavamano', 'sku' => 'FV-100']);
    $unit = ProductUnit::factory()->create(['product_id' => $product->id, 'name' => 'Unidad', 'factor' => 1, 'is_base' => true, 'price' => 100]);

    // 5 en Almacén Central, 5 en Almacén Norte (a costos distintos, para poder distinguir de dónde salió cada uno).
    app(PurchaseService::class)->register(['warehouse_id' => $wh1->id], [['product_unit_id' => $unit->id, 'quantity' => 5, 'unit_cost' => 40]], $user);
    app(PurchaseService::class)->register(['warehouse_id' => $wh2->id], [['product_unit_id' => $unit->id, 'quantity' => 5, 'unit_cost' => 60]], $user);

    return [
        'branch1' => $branch1, 'branch2' => $branch2, 'wh1' => $wh1, 'wh2' => $wh2,
        'user' => $user, 'product' => $product, 'unit' => $unit,
        'customer' => Customer::factory()->create(),
        'cash' => PaymentMethod::factory()->create(['is_cash' => true]),
        'box' => Cashbox::factory()->create(['branche_id' => $branch1->id, 'balance' => 0, 'cash' => 0]),
    ];
}

beforeEach(function () {
    $this->seed(Database\Seeders\TypeSeeder::class);
});

describe('ventas: una línea por almacén', function () {
    it('vende 3 de un almacén y 4 del otro en una sola venta, descontando cada uno de su almacén', function () {
        $w = twoWarehouseWorld();

        $sale = app(SaleService::class)->register([
            'warehouse_id' => $w['wh1']->id, // almacén por defecto de la venta
            'customer_id' => $w['customer']->id,
            'payment_id' => $w['cash']->id,
            'cashbox_id' => $w['box']->id,
        ], [
            ['product_unit_id' => $w['unit']->id, 'warehouse_id' => $w['wh1']->id, 'quantity' => 3],
            ['product_unit_id' => $w['unit']->id, 'warehouse_id' => $w['wh2']->id, 'quantity' => 4],
        ], $w['user']);

        expect((float) $sale->amount)->toBe(700.0) // 7 unidades × 100
            ->and($sale->items)->toHaveCount(2)
            ->and($sale->stockMovements)->toHaveCount(2);

        $stock1 = ProductWarehouse::where('product_id', $w['product']->id)->where('warehouse_id', $w['wh1']->id)->first();
        $stock2 = ProductWarehouse::where('product_id', $w['product']->id)->where('warehouse_id', $w['wh2']->id)->first();
        expect((float) $stock1->stock)->toBe(2.0) // 5 - 3
            ->and((float) $stock2->stock)->toBe(1.0); // 5 - 4

        // El costo real (FIFO) de cada línea debe venir del almacén del que realmente salió.
        expect((float) $sale->cost_of_goods)->toBe(3 * 40.0 + 4 * 60.0);
    });

    it('sin indicar almacén por línea, usa el almacén por defecto de la venta (compatible con lo de antes)', function () {
        $w = twoWarehouseWorld();

        $sale = app(SaleService::class)->register([
            'warehouse_id' => $w['wh1']->id,
            'customer_id' => $w['customer']->id,
            'payment_id' => $w['cash']->id,
            'cashbox_id' => $w['box']->id,
        ], [
            ['product_unit_id' => $w['unit']->id, 'quantity' => 2], // sin warehouse_id
        ], $w['user']);

        expect($sale->items->first()->warehouse_id)->toBe($w['wh1']->id)
            ->and($sale->stockMovements)->toHaveCount(1);
    });

    it('al anular, revierte los movimientos de AMBOS almacenes', function () {
        $w = twoWarehouseWorld();

        $sale = app(SaleService::class)->register([
            'warehouse_id' => $w['wh1']->id,
            'customer_id' => $w['customer']->id,
            'payment_id' => $w['cash']->id,
            'cashbox_id' => $w['box']->id,
        ], [
            ['product_unit_id' => $w['unit']->id, 'warehouse_id' => $w['wh1']->id, 'quantity' => 3],
            ['product_unit_id' => $w['unit']->id, 'warehouse_id' => $w['wh2']->id, 'quantity' => 4],
        ], $w['user']);

        app(SaleService::class)->cancel($sale);

        $stock1 = ProductWarehouse::where('product_id', $w['product']->id)->where('warehouse_id', $w['wh1']->id)->first();
        $stock2 = ProductWarehouse::where('product_id', $w['product']->id)->where('warehouse_id', $w['wh2']->id)->first();
        expect((float) $stock1->stock)->toBe(5.0)
            ->and((float) $stock2->stock)->toBe(5.0);
    });

    it('por HTTP, rechaza dos líneas del mismo producto y el mismo almacén (hay que sumar la cantidad, no repetir)', function () {
        $w = twoWarehouseWorld();

        $response = $this->actingAs($w['user'])->post(route('sales.store'), [
            'warehouse_id' => $w['wh1']->id,
            'customer_id' => $w['customer']->id,
            'payment_id' => $w['cash']->id,
            'cashbox_id' => $w['box']->id,
            'items' => [
                ['product_unit_id' => $w['unit']->id, 'warehouse_id' => $w['wh1']->id, 'quantity' => 2],
                ['product_unit_id' => $w['unit']->id, 'warehouse_id' => $w['wh1']->id, 'quantity' => 1],
            ],
        ]);

        $response->assertSessionHasErrors();
        expect(\App\Models\Order::count())->toBe(0);
    });

    it('por HTTP, sí permite dos líneas del mismo producto si van a almacenes distintos', function () {
        $w = twoWarehouseWorld();

        $response = $this->actingAs($w['user'])->post(route('sales.store'), [
            'warehouse_id' => $w['wh1']->id,
            'customer_id' => $w['customer']->id,
            'payment_id' => $w['cash']->id,
            'cashbox_id' => $w['box']->id,
            'items' => [
                ['product_unit_id' => $w['unit']->id, 'warehouse_id' => $w['wh1']->id, 'quantity' => 2],
                ['product_unit_id' => $w['unit']->id, 'warehouse_id' => $w['wh2']->id, 'quantity' => 3],
            ],
        ]);

        $response->assertSessionDoesntHaveErrors();
        expect(\App\Models\Order::first()->items)->toHaveCount(2);
    });
});

describe('pedidos: cada línea guarda su almacén para cuando se confirme', function () {
    it('crea el pedido con el almacén de cada línea, y al confirmarlo descuenta de cada almacén correcto', function () {
        $w = twoWarehouseWorld();

        $pedido = app(OrderService::class)->create([
            'warehouse_id' => $w['wh1']->id,
            'delivery_date' => now()->addDays(3)->toDateString(),
        ], [
            ['product_unit_id' => $w['unit']->id, 'warehouse_id' => $w['wh1']->id, 'quantity' => 2],
            ['product_unit_id' => $w['unit']->id, 'warehouse_id' => $w['wh2']->id, 'quantity' => 3],
        ], $w['user']);

        expect($pedido->items->pluck('warehouse_id')->sort()->values()->all())
            ->toBe(collect([$w['wh1']->id, $w['wh2']->id])->sort()->values()->all());

        $sale = app(SaleService::class)->confirm($pedido, [
            'payment_id' => $w['cash']->id,
            'cashbox_id' => $w['box']->id,
        ], $w['user']);

        expect($sale->stockMovements)->toHaveCount(2);
        $stock1 = ProductWarehouse::where('product_id', $w['product']->id)->where('warehouse_id', $w['wh1']->id)->first();
        $stock2 = ProductWarehouse::where('product_id', $w['product']->id)->where('warehouse_id', $w['wh2']->id)->first();
        expect((float) $stock1->stock)->toBe(3.0) // 5 - 2
            ->and((float) $stock2->stock)->toBe(2.0); // 5 - 3
    });
});

describe('consignaciones: cada línea sale de su propio almacén', function () {
    it('despacha dos líneas del mismo producto desde almacenes distintos, creando un movimiento por cada uno', function () {
        $w = twoWarehouseWorld();

        $consignment = app(ConsignmentService::class)->create([
            'warehouse_id' => $w['wh1']->id,
            'customer_id' => $w['customer']->id,
        ], [
            ['product_unit_id' => $w['unit']->id, 'warehouse_id' => $w['wh1']->id, 'quantity' => 2],
            ['product_unit_id' => $w['unit']->id, 'warehouse_id' => $w['wh2']->id, 'quantity' => 3],
        ], $w['user']);

        expect($consignment->dispatchMovements)->toHaveCount(2)
            ->and($consignment->items->firstWhere('warehouse_id', $w['wh1']->id)->unit_cost)->toEqual(40.0)
            ->and($consignment->items->firstWhere('warehouse_id', $w['wh2']->id)->unit_cost)->toEqual(60.0);

        $stock1 = ProductWarehouse::where('product_id', $w['product']->id)->where('warehouse_id', $w['wh1']->id)->first();
        $stock2 = ProductWarehouse::where('product_id', $w['product']->id)->where('warehouse_id', $w['wh2']->id)->first();
        expect((float) $stock1->stock)->toBe(3.0)
            ->and((float) $stock2->stock)->toBe(2.0);
    });

    it('al anular, revierte los movimientos de ambos almacenes', function () {
        $w = twoWarehouseWorld();

        $consignment = app(ConsignmentService::class)->create([
            'warehouse_id' => $w['wh1']->id,
            'customer_id' => $w['customer']->id,
        ], [
            ['product_unit_id' => $w['unit']->id, 'warehouse_id' => $w['wh1']->id, 'quantity' => 2],
            ['product_unit_id' => $w['unit']->id, 'warehouse_id' => $w['wh2']->id, 'quantity' => 3],
        ], $w['user']);

        app(ConsignmentService::class)->cancel($consignment);

        $stock1 = ProductWarehouse::where('product_id', $w['product']->id)->where('warehouse_id', $w['wh1']->id)->first();
        $stock2 = ProductWarehouse::where('product_id', $w['product']->id)->where('warehouse_id', $w['wh2']->id)->first();
        expect((float) $stock1->stock)->toBe(5.0)
            ->and((float) $stock2->stock)->toBe(5.0);
    });

    it('al devolver en la liquidación, el stock vuelve al almacén del que había salido cada línea', function () {
        $w = twoWarehouseWorld();

        $consignment = app(ConsignmentService::class)->create([
            'warehouse_id' => $w['wh1']->id,
            'customer_id' => $w['customer']->id,
        ], [
            ['product_unit_id' => $w['unit']->id, 'warehouse_id' => $w['wh1']->id, 'quantity' => 2],
            ['product_unit_id' => $w['unit']->id, 'warehouse_id' => $w['wh2']->id, 'quantity' => 3],
        ], $w['user']);

        $items = $consignment->items()->get();

        app(ConsignmentService::class)->settle($consignment, [
            ['consignment_item_id' => $items->firstWhere('warehouse_id', $w['wh1']->id)->id, 'returned' => 2],
            ['consignment_item_id' => $items->firstWhere('warehouse_id', $w['wh2']->id)->id, 'returned' => 3],
        ], [], $w['user']);

        $stock1 = ProductWarehouse::where('product_id', $w['product']->id)->where('warehouse_id', $w['wh1']->id)->first();
        $stock2 = ProductWarehouse::where('product_id', $w['product']->id)->where('warehouse_id', $w['wh2']->id)->first();
        expect((float) $stock1->stock)->toBe(5.0) // volvió completo
            ->and((float) $stock2->stock)->toBe(5.0);
    });
});
