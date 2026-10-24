<?php

use App\Models\Cashbox;
use App\Models\Category;
use App\Models\Consignment;
use App\Models\ConsignmentItem;
use App\Models\Customer;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\ProductWarehouse;
use App\Models\StockBatch;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\ConsignmentService;
use App\Services\SaleService;
use Database\Seeders\TypeSeeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

/*
|--------------------------------------------------------------------------
| Consignación de salida: entrega sin cobrar → liquidación (venta + devolución)
|--------------------------------------------------------------------------
*/

/**
 * Escenario base: almacén con 100 unidades de un producto a costo 60 (precio de catálogo 100),
 * una caja con fondos, efectivo como método de pago y un cliente.
 *
 * @return array{warehouse: Warehouse, cashbox: Cashbox, cash: PaymentMethod, customer: Customer, unit: ProductUnit, product: Product, user: User}
 */
function consignmentWorld(int $stock = 100, float $cost = 60.0): array
{
    test()->seed(TypeSeeder::class);

    $branch = testBranch();
    $warehouse = Warehouse::create(['name' => 'Almacén Central', 'code' => 'ALM-CEN', 'is_active' => true, 'branche_id' => $branch->id]);
    $cashbox = Cashbox::create(['name' => 'Caja Principal', 'branche_id' => $branch->id, 'cash' => 0, 'balance' => 0]);
    $cash = PaymentMethod::create(['name' => 'Efectivo', 'is_cash' => true]);

    $category = Category::create(['name' => 'Herramientas']);
    $product = Product::create(['sku' => 'HM-0001', 'name' => 'Martillo', 'category_id' => $category->id, 'is_active' => true]);
    $unit = $product->units()->create(['name' => 'Unidad', 'factor' => 1, 'price' => 100, 'is_base' => true, 'barcode' => 'HM-0001']);

    ProductWarehouse::receiveStock($product->id, $warehouse->id, $stock, $cost, null, now()->subDays(5));

    $user = User::factory()->create(['branche_id' => $branch->id]);
    foreach (['ventas.editar-precio', 'ventas.credito', 'consignaciones.liquidar', 'ver consignments', 'crear consignments', 'eliminar consignments'] as $permission) {
        Permission::findOrCreate($permission, 'web');
    }
    $user->givePermissionTo(['ventas.editar-precio', 'ventas.credito', 'consignaciones.liquidar', 'ver consignments', 'crear consignments', 'eliminar consignments']);

    return [
        'warehouse' => $warehouse,
        'cashbox' => $cashbox,
        'cash' => $cash,
        'customer' => Customer::factory()->create(),
        'unit' => $unit,
        'product' => $product,
        'user' => $user,
    ];
}

function stockOf(Product $product, Warehouse $warehouse): int
{
    return (int) DB::table('product_warehouses')->where('product_id', $product->id)->where('warehouse_id', $warehouse->id)->value('stock');
}

/** Crea una consignación de `$quantity` unidades del producto del escenario. */
function dispatchConsignment(array $w, float $quantity = 10, array $data = [], ?float $price = null): Consignment
{
    return app(ConsignmentService::class)->create(
        ['customer_id' => $w['customer']->id, 'warehouse_id' => $w['warehouse']->id] + $data,
        [['product_unit_id' => $w['unit']->id, 'quantity' => $quantity] + ($price !== null ? ['unit_price' => $price] : [])],
        $w['user']
    );
}

/** Líneas de liquidación para el primer ítem de la consignación. */
function settleLines(Consignment $consignment, float $sold, float $returned): array
{
    return [['consignment_item_id' => $consignment->items()->first()->id, 'sold' => $sold, 'returned' => $returned]];
}

// ─────────────────────────────────────────────────────────────────────────────
// Entrega (create)
// ─────────────────────────────────────────────────────────────────────────────

describe('entrega de la mercadería', function () {
    it('saca el stock del almacén sin cobrar ni facturar nada', function () {
        $w = consignmentWorld();

        $consignment = dispatchConsignment($w, 10);

        expect($consignment->status)->toBe(Consignment::STATUS_OPEN)
            ->and(stockOf($w['product'], $w['warehouse']))->toBe(90)
            ->and(Order::count())->toBe(0)
            ->and(Transaction::count())->toBe(0)
            ->and((float) Cashbox::first()->balance)->toBe(0.0);
    });

    it('guarda el precio acordado y el costo FIFO de cada línea', function () {
        $w = consignmentWorld(stock: 0);
        ProductWarehouse::receiveStock($w['product']->id, $w['warehouse']->id, 5, 40, null, now()->subDays(3)); // más antiguo
        ProductWarehouse::receiveStock($w['product']->id, $w['warehouse']->id, 5, 60, null, now()->subDay());

        $consignment = dispatchConsignment($w, 8); // 5 a 40 + 3 a 60 = 380 → 47,50 por unidad
        $item = $consignment->items->first();

        expect((float) $item->quantity_sent)->toBe(8.0)
            ->and((float) $item->unit_price)->toBe(100.0)
            ->and((float) $item->unit_cost)->toBe(47.5)
            ->and($item->pending)->toBe(8.0);
    });

    it('enlaza la salida de stock con la consignación', function () {
        $w = consignmentWorld();

        $consignment = dispatchConsignment($w, 10);

        expect($consignment->dispatchMovements)->toHaveCount(1)
            ->and($consignment->dispatchMovements->first()->type->code)->toBe('SAL')
            ->and($consignment->dispatchMovements->first()->items)->toHaveCount(1);
    });

    it('rechaza la entrega si no hay stock suficiente y no deja nada guardado', function () {
        $w = consignmentWorld(stock: 5);

        expect(fn () => dispatchConsignment($w, 10))->toThrow(RuntimeException::class, 'Stock insuficiente');

        expect(Consignment::count())->toBe(0)
            ->and(stockOf($w['product'], $w['warehouse']))->toBe(5);
    });

    it('permite acordar un precio distinto al del catálogo si el usuario tiene permiso', function () {
        $w = consignmentWorld();

        $consignment = dispatchConsignment($w, 10, price: 85);

        expect((float) $consignment->items->first()->unit_price)->toBe(85.0);
    });

    it('no deja cambiar el precio a quien no tiene el permiso', function () {
        $w = consignmentWorld();
        $w['user']->revokePermissionTo('ventas.editar-precio');

        expect(fn () => dispatchConsignment($w, 10, price: 85))->toThrow(RuntimeException::class, 'No tienes permiso para cambiar el precio');

        expect(stockOf($w['product'], $w['warehouse']))->toBe(100);
    });

    it('maneja presentaciones: una caja de 12 descuenta 12 unidades base', function () {
        $w = consignmentWorld();
        $box = $w['product']->units()->create(['name' => 'Caja x12', 'factor' => 12, 'price' => 1000, 'is_base' => false]);

        $consignment = app(ConsignmentService::class)->create(
            ['customer_id' => $w['customer']->id, 'warehouse_id' => $w['warehouse']->id],
            [['product_unit_id' => $box->id, 'quantity' => 2]],
            $w['user']
        );

        expect(stockOf($w['product'], $w['warehouse']))->toBe(76)
            ->and((float) $consignment->items->first()->unit_cost)->toBe(60.0); // costo por unidad BASE
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// Liquidación (settle)
// ─────────────────────────────────────────────────────────────────────────────

describe('liquidación', function () {
    it('lo vendido se factura como venta y lo devuelto vuelve al stock', function () {
        $w = consignmentWorld();
        $consignment = dispatchConsignment($w, 10);

        $settlement = app(ConsignmentService::class)->settle(
            $consignment,
            settleLines($consignment, sold: 6, returned: 4),
            ['payment_type' => 'cash', 'payment_id' => $w['cash']->id, 'cashbox_id' => $w['cashbox']->id],
            $w['user']
        );

        $order = $settlement->order;

        expect($order)->not->toBeNull()
            ->and((float) $order->amount)->toBe(600.0)
            ->and($order->status)->toBe(Order::STATUS_COMPLETED)
            ->and($order->customer_id)->toBe($w['customer']->id)
            ->and($order->items)->toHaveCount(1)
            ->and((float) $order->paid_amount)->toBe(600.0)
            ->and(stockOf($w['product'], $w['warehouse']))->toBe(94) // 100 - 10 + 4 devueltas
            ->and((float) Cashbox::first()->cash)->toBe(600.0)
            ->and($consignment->fresh()->status)->toBe(Consignment::STATUS_SETTLED);
    });

    it('la venta de consignación no descuenta stock una segunda vez', function () {
        $w = consignmentWorld();
        $consignment = dispatchConsignment($w, 10);

        app(ConsignmentService::class)->settle($consignment, settleLines($consignment, 10, 0),
            ['payment_type' => 'cash', 'payment_id' => $w['cash']->id, 'cashbox_id' => $w['cashbox']->id], $w['user']);

        expect(stockOf($w['product'], $w['warehouse']))->toBe(90)
            ->and(DB::table('stock_movements')->count())->toBe(1); // solo la salida original
    });

    it('lo devuelto regresa al stock con el costo con el que salió', function () {
        $w = consignmentWorld(stock: 0);
        ProductWarehouse::receiveStock($w['product']->id, $w['warehouse']->id, 10, 40, null, now()->subDays(3));
        ProductWarehouse::receiveStock($w['product']->id, $w['warehouse']->id, 10, 80, null, now()->subDay());

        $consignment = dispatchConsignment($w, 10); // consume el lote de 40
        app(ConsignmentService::class)->settle($consignment, settleLines($consignment, 0, 4), [], $w['user']);

        $returnedBatch = StockBatch::where('product_id', $w['product']->id)->orderByDesc('id')->first();

        expect((float) $returnedBatch->unit_cost)->toBe(40.0)
            ->and((float) $returnedBatch->qty_remaining)->toBe(4.0)
            ->and(stockOf($w['product'], $w['warehouse']))->toBe(14);
    });

    it('permite liquidar por partes hasta agotar lo pendiente', function () {
        $w = consignmentWorld();
        $consignment = dispatchConsignment($w, 10);
        $terms = ['payment_type' => 'cash', 'payment_id' => $w['cash']->id, 'cashbox_id' => $w['cashbox']->id];

        app(ConsignmentService::class)->settle($consignment, settleLines($consignment, 3, 2), $terms, $w['user']);

        $consignment->refresh();
        $item = $consignment->items()->first();
        expect($consignment->status)->toBe(Consignment::STATUS_OPEN)
            ->and($item->pending)->toBe(5.0)
            ->and((float) $item->quantity_sold)->toBe(3.0)
            ->and((float) $item->quantity_returned)->toBe(2.0);

        app(ConsignmentService::class)->settle($consignment, settleLines($consignment, 5, 0), $terms, $w['user']);

        expect($consignment->fresh()->status)->toBe(Consignment::STATUS_SETTLED)
            ->and($consignment->settlements)->toHaveCount(2)
            ->and(Order::count())->toBe(2);
    });

    it('una liquidación solo con devoluciones no crea venta', function () {
        $w = consignmentWorld();
        $consignment = dispatchConsignment($w, 10);

        $settlement = app(ConsignmentService::class)->settle($consignment, settleLines($consignment, 0, 10), [], $w['user']);

        expect($settlement->order_id)->toBeNull()
            ->and($settlement->return_movement_id)->not->toBeNull()
            ->and(Order::count())->toBe(0)
            ->and(stockOf($w['product'], $w['warehouse']))->toBe(100)
            ->and($consignment->fresh()->status)->toBe(Consignment::STATUS_SETTLED);
    });

    it('una liquidación solo con ventas no toca el stock', function () {
        $w = consignmentWorld();
        $consignment = dispatchConsignment($w, 10);

        $settlement = app(ConsignmentService::class)->settle($consignment, settleLines($consignment, 4, 0),
            ['payment_type' => 'cash', 'payment_id' => $w['cash']->id, 'cashbox_id' => $w['cashbox']->id], $w['user']);

        expect($settlement->return_movement_id)->toBeNull()
            ->and($settlement->order_id)->not->toBeNull()
            ->and(stockOf($w['product'], $w['warehouse']))->toBe(90);
    });

    it('lo vendido puede quedar a crédito con vencimiento y anticipo', function () {
        $w = consignmentWorld();
        $consignment = dispatchConsignment($w, 10);

        $settlement = app(ConsignmentService::class)->settle($consignment, settleLines($consignment, 5, 5), [
            'payment_type' => 'credit', 'due_date' => now()->addDays(20)->toDateString(), 'initial_payment' => 100,
            'payment_id' => $w['cash']->id, 'cashbox_id' => $w['cashbox']->id,
        ], $w['user']);

        $order = $settlement->order;

        expect($order->isCredit())->toBeTrue()
            ->and((float) $order->amount)->toBe(500.0)
            ->and((float) $order->paid_amount)->toBe(100.0)
            ->and($order->balance)->toBe(400.0)
            ->and($w['customer']->receivableBalance())->toBe(400.0);
    });

    it('calcula el costo y la ganancia de la venta con el costo del despacho', function () {
        $w = consignmentWorld(cost: 60);
        $consignment = dispatchConsignment($w, 10);

        $settlement = app(ConsignmentService::class)->settle($consignment, settleLines($consignment, 5, 0),
            ['payment_type' => 'cash', 'payment_id' => $w['cash']->id, 'cashbox_id' => $w['cashbox']->id], $w['user']);

        $order = Order::with('items')->find($settlement->order_id);

        expect($order->cost_of_goods)->toBe(300.0)   // 5 × 60
            ->and($order->profit)->toBe(200.0);      // 500 - 300
    });

    it('rechaza pasarse de lo pendiente y no guarda nada', function () {
        $w = consignmentWorld();
        $consignment = dispatchConsignment($w, 10);

        expect(fn () => app(ConsignmentService::class)->settle($consignment, settleLines($consignment, 8, 3),
            ['payment_type' => 'cash', 'payment_id' => $w['cash']->id, 'cashbox_id' => $w['cashbox']->id], $w['user']))
            ->toThrow(RuntimeException::class, 'solo quedan');

        expect(Order::count())->toBe(0)
            ->and($consignment->settlements()->count())->toBe(0)
            ->and($consignment->items()->first()->pending)->toBe(10.0);
    });

    it('rechaza una liquidación vacía o con cantidades negativas', function () {
        $w = consignmentWorld();
        $consignment = dispatchConsignment($w, 10);

        expect(fn () => app(ConsignmentService::class)->settle($consignment, settleLines($consignment, 0, 0), [], $w['user']))
            ->toThrow(RuntimeException::class, 'Indica cuánto vendió');

        expect(fn () => app(ConsignmentService::class)->settle($consignment, settleLines($consignment, -1, 0), [], $w['user']))
            ->toThrow(RuntimeException::class, 'negativas');
    });

    it('rechaza líneas que no son de esta consignación', function () {
        $w = consignmentWorld();
        $consignment = dispatchConsignment($w, 10);
        $other = dispatchConsignment($w, 5);

        expect(fn () => app(ConsignmentService::class)->settle($consignment, settleLines($other, 1, 0), [], $w['user']))
            ->toThrow(RuntimeException::class, 'no pertenece');
    });

    it('no deja liquidar una consignación ya liquidada o anulada', function () {
        $w = consignmentWorld();
        $settled = dispatchConsignment($w, 2);
        app(ConsignmentService::class)->settle($settled, settleLines($settled, 0, 2), [], $w['user']);
        $canceled = dispatchConsignment($w, 2);
        app(ConsignmentService::class)->cancel($canceled);

        expect(fn () => app(ConsignmentService::class)->settle($settled, settleLines($settled, 0, 1), [], $w['user']))
            ->toThrow(RuntimeException::class, 'Solo se puede liquidar');
        expect(fn () => app(ConsignmentService::class)->settle($canceled, settleLines($canceled, 0, 1), [], $w['user']))
            ->toThrow(RuntimeException::class, 'Solo se puede liquidar');
    });

    it('lo vendido al contado exige método de pago y caja, y si falla no se registra nada', function () {
        $w = consignmentWorld();
        $consignment = dispatchConsignment($w, 10);

        expect(fn () => app(ConsignmentService::class)->settle($consignment, settleLines($consignment, 5, 5), ['payment_type' => 'cash'], $w['user']))
            ->toThrow(RuntimeException::class, 'método de pago y la caja');

        expect($consignment->items()->first()->pending)->toBe(10.0)
            ->and(stockOf($w['product'], $w['warehouse']))->toBe(90) // las 5 devueltas NO quedaron registradas
            ->and(DB::table('stock_movements')->count())->toBe(1);
    });

    it('a crédito exige permiso, fecha de vencimiento futura y respeta el límite del cliente', function () {
        $w = consignmentWorld();
        $consignment = dispatchConsignment($w, 10);
        $credit = fn (array $extra = []) => ['payment_type' => 'credit', 'due_date' => now()->addDays(10)->toDateString()] + $extra;

        $w['user']->revokePermissionTo('ventas.credito');
        expect(fn () => app(ConsignmentService::class)->settle($consignment, settleLines($consignment, 5, 0), $credit(), $w['user']))
            ->toThrow(RuntimeException::class, 'permiso para vender a crédito');
        $w['user']->givePermissionTo('ventas.credito');

        expect(fn () => app(ConsignmentService::class)->settle($consignment, settleLines($consignment, 5, 0), ['payment_type' => 'credit', 'due_date' => now()->toDateString()], $w['user']))
            ->toThrow(RuntimeException::class, 'posterior a hoy');

        $w['customer']->update(['credit_limit' => 300]);
        expect(fn () => app(ConsignmentService::class)->settle($consignment, settleLines($consignment, 5, 0), $credit(), $w['user']))
            ->toThrow(RuntimeException::class, 'límite de crédito');
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// Anulación
// ─────────────────────────────────────────────────────────────────────────────

describe('anulación', function () {
    it('devuelve la mercadería exactamente a los lotes de donde salió', function () {
        $w = consignmentWorld(stock: 0);
        $old = ProductWarehouse::receiveStock($w['product']->id, $w['warehouse']->id, 10, 40, null, now()->subDays(3));
        $new = ProductWarehouse::receiveStock($w['product']->id, $w['warehouse']->id, 10, 80, null, now()->subDay());

        $consignment = dispatchConsignment($w, 12); // 10 del lote viejo + 2 del nuevo

        expect((float) $old->fresh()->qty_remaining)->toBe(0.0)
            ->and((float) $new->fresh()->qty_remaining)->toBe(8.0);

        app(ConsignmentService::class)->cancel($consignment);

        expect((float) $old->fresh()->qty_remaining)->toBe(10.0)
            ->and((float) $new->fresh()->qty_remaining)->toBe(10.0)
            ->and(stockOf($w['product'], $w['warehouse']))->toBe(20)
            ->and($consignment->fresh()->status)->toBe(Consignment::STATUS_CANCELED);
    });

    it('no se puede anular si ya tuvo liquidaciones', function () {
        $w = consignmentWorld();
        $consignment = dispatchConsignment($w, 10);
        app(ConsignmentService::class)->settle($consignment, settleLines($consignment, 0, 3), [], $w['user']);

        expect(fn () => app(ConsignmentService::class)->cancel($consignment))->toThrow(RuntimeException::class, 'ya tiene liquidaciones');
    });

    it('no se puede anular dos veces', function () {
        $w = consignmentWorld();
        $consignment = dispatchConsignment($w, 10);
        app(ConsignmentService::class)->cancel($consignment);

        expect(fn () => app(ConsignmentService::class)->cancel($consignment))->toThrow(RuntimeException::class, 'consignación abierta');
    });

    it('la venta que salió de una consignación no se puede anular por separado', function () {
        $w = consignmentWorld();
        $consignment = dispatchConsignment($w, 10);
        $settlement = app(ConsignmentService::class)->settle($consignment, settleLines($consignment, 5, 0),
            ['payment_type' => 'cash', 'payment_id' => $w['cash']->id, 'cashbox_id' => $w['cashbox']->id], $w['user']);

        expect(fn () => app(SaleService::class)->cancel($settlement->order))
            ->toThrow(RuntimeException::class, 'consignación #'.$consignment->id);

        expect($settlement->order->fresh()->status)->toBe(Order::STATUS_COMPLETED);
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// HTTP: permisos, formularios y pantallas
// ─────────────────────────────────────────────────────────────────────────────

describe('pantallas y permisos', function () {
    it('redirige al login a un visitante y bloquea a quien no tiene permisos', function () {
        $this->get(route('consignments.index'))->assertRedirect(route('login'));

        $this->actingAs(userWith());
        $this->get(route('consignments.index'))->assertForbidden();
    });

    it('cada acción exige su permiso', function () {
        $w = consignmentWorld();
        $consignment = dispatchConsignment($w, 10);

        $this->actingAs(userWith('ver consignments'));

        $this->get(route('consignments.index'))->assertOk();
        $this->get(route('consignments.show', $consignment))->assertOk();
        $this->get(route('consignments.create'))->assertForbidden();
        $this->post(route('consignments.store'), [])->assertForbidden();
        $this->post(route('consignments.settle', $consignment), [])->assertForbidden();
        $this->post(route('consignments.cancel', $consignment))->assertForbidden();
    });

    it('lista, filtra por cliente y muestra el detalle', function () {
        $w = consignmentWorld();
        dispatchConsignment($w, 10);
        $this->actingAs($w['user']);

        $this->get(route('consignments.index'))->assertOk()->assertSee($w['customer']->name);

        $found = $this->get(route('consignments.index', ['filter' => ['buscar' => $w['customer']->name]]))->viewData('consignments');
        $none = $this->get(route('consignments.index', ['filter' => ['buscar' => 'nadie-con-este-nombre']]))->viewData('consignments');

        expect($found->total())->toBe(1)->and($none->total())->toBe(0);
    });

    it('registra una consignación desde el formulario', function () {
        $w = consignmentWorld();
        $this->actingAs($w['user']);

        $this->get(route('consignments.create'))->assertOk()->assertSee('Registrar consignación');

        $this->post(route('consignments.store'), [
            'customer_id' => $w['customer']->id,
            'warehouse_id' => $w['warehouse']->id,
            'due_date' => now()->addDays(15)->toDateString(),
            'note' => 'Para la feria',
            'items' => [['product_unit_id' => $w['unit']->id, 'quantity' => 10]],
        ])->assertRedirect(route('consignments.show', Consignment::firstOrFail()));

        $consignment = Consignment::firstOrFail();
        expect($consignment->note)->toBe('Para la feria')
            ->and($consignment->due_date->toDateString())->toBe(now()->addDays(15)->toDateString())
            ->and(stockOf($w['product'], $w['warehouse']))->toBe(90);
    });

    it('valida el formulario de entrega', function (array $override, string $field) {
        $w = consignmentWorld();
        $this->actingAs($w['user']);

        $payload = array_replace_recursive([
            'customer_id' => $w['customer']->id,
            'warehouse_id' => $w['warehouse']->id,
            'items' => [['product_unit_id' => $w['unit']->id, 'quantity' => 1]],
        ], $override);

        // null explícito = quitar el campo
        foreach ($override as $key => $value) {
            if ($value === null) {
                unset($payload[$key]);
            }
        }

        $this->post(route('consignments.store'), $payload)->assertSessionHasErrors($field);

        expect(Consignment::count())->toBe(0);
    })->with([
        'sin cliente' => [['customer_id' => null], 'customer_id'],
        'sin almacén' => [['warehouse_id' => null], 'warehouse_id'],
        'sin productos' => [['items' => null], 'items'],
        'cantidad 0' => [['items' => [['quantity' => 0]]], 'items.0.quantity'],
        'fecha límite pasada' => [['due_date' => '2020-01-01'], 'due_date'],
        'precio negativo' => [['items' => [['unit_price' => -1]]], 'items.0.unit_price'],
    ]);

    it('muestra el error del servicio cuando no hay stock y conserva lo escrito', function () {
        $w = consignmentWorld(stock: 3);
        $this->actingAs($w['user']);

        $this->from(route('consignments.create'))->post(route('consignments.store'), [
            'customer_id' => $w['customer']->id,
            'warehouse_id' => $w['warehouse']->id,
            'items' => [['product_unit_id' => $w['unit']->id, 'quantity' => 50]],
        ])->assertRedirect(route('consignments.create'))
            ->assertSessionHas('error', fn ($message) => str_contains($message, 'Stock insuficiente'))
            ->assertSessionHasInput('customer_id', $w['customer']->id);
    });

    it('liquida desde el formulario: venta al contado y devolución', function () {
        $w = consignmentWorld();
        $consignment = dispatchConsignment($w, 10);
        $this->actingAs($w['user']);

        $this->post(route('consignments.settle', $consignment), [
            'lines' => [['consignment_item_id' => $consignment->items()->first()->id, 'sold' => 7, 'returned' => 3]],
            'payment_type' => 'cash',
            'payment_id' => $w['cash']->id,
            'cashbox_id' => $w['cashbox']->id,
            'note' => 'Liquidación de la feria',
        ])->assertRedirect(route('consignments.show', $consignment))
            ->assertSessionHas('success', fn ($m) => str_contains($m, 'venta #') && str_contains($m, 'liquidada por completo'));

        expect(Order::firstOrFail()->amount)->toEqual('700.00')
            ->and($consignment->fresh()->status)->toBe(Consignment::STATUS_SETTLED)
            ->and($consignment->settlements()->first()->note)->toBe('Liquidación de la feria');
    });

    it('la liquidación por HTTP muestra el error si se pasa de lo pendiente', function () {
        $w = consignmentWorld();
        $consignment = dispatchConsignment($w, 10);
        $this->actingAs($w['user']);

        $this->post(route('consignments.settle', $consignment), [
            'lines' => [['consignment_item_id' => $consignment->items()->first()->id, 'sold' => 11, 'returned' => 0]],
            'payment_type' => 'cash', 'payment_id' => $w['cash']->id, 'cashbox_id' => $w['cashbox']->id,
        ])->assertSessionHas('error');

        expect(Order::count())->toBe(0);
    });

    it('anula desde la pantalla y devuelve el stock', function () {
        $w = consignmentWorld();
        $consignment = dispatchConsignment($w, 10);
        $this->actingAs($w['user']);

        $this->post(route('consignments.cancel', $consignment))
            ->assertRedirect(route('consignments.show', $consignment))
            ->assertSessionHas('success');

        expect(stockOf($w['product'], $w['warehouse']))->toBe(100);
    });

    it('el detalle de una abierta ofrece liquidar y anular', function () {
        $w = consignmentWorld();
        $open = dispatchConsignment($w, 10);
        $this->actingAs($w['user']);

        $this->get(route('consignments.show', $open))
            ->assertOk()
            ->assertSee('Liquidar consignación')
            ->assertSee('Anular consignación')
            ->assertSee('Todavía no se liquidó nada.');
    });
    it('el detalle de una liquidada enlaza a la venta y no ofrece liquidar', function () {
        $w = consignmentWorld();
        $consignment = dispatchConsignment($w, 10);
        app(ConsignmentService::class)->settle($consignment, settleLines($consignment, 4, 6),
            ['payment_type' => 'cash', 'payment_id' => $w['cash']->id, 'cashbox_id' => $w['cashbox']->id], $w['user']);
        $this->actingAs($w['user']);

        $this->get(route('consignments.show', $consignment))
            ->assertOk()
            ->assertDontSee('Liquidar consignación')
            ->assertDontSee('Anular consignación')
            ->assertSee('Venta #');
    });

    it('la pantalla de venta de una consignación muestra su costo y ganancia', function () {
        $w = consignmentWorld();
        $consignment = dispatchConsignment($w, 10);
        $settlement = app(ConsignmentService::class)->settle($consignment, settleLines($consignment, 4, 0),
            ['payment_type' => 'cash', 'payment_id' => $w['cash']->id, 'cashbox_id' => $w['cashbox']->id], $w['user']);

        $admin = userWith('ver sales', 'admin');
        $this->actingAs($admin);

        $this->get(route('sales.show', $settlement->order))
            ->assertOk()
            ->assertSee('Ganancia');
    });
});
