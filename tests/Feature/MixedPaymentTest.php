<?php

use App\Models\Cashbox;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductWarehouse;
use App\Models\Warehouse;
use App\Services\ConsignmentService;
use App\Services\OrderService;
use App\Services\SaleService;

/*
|--------------------------------------------------------------------------
| Pagos mixtos en ventas
|--------------------------------------------------------------------------
| Una venta se cobra combinando métodos: Bs 50 = Bs 20 por QR + Bs 30 en
| efectivo. Cada parte queda como un cobro propio con su método, en la
| misma caja, en la venta, el anticipo a crédito, los abonos, al confirmar
| un pedido y al liquidar una consignación.
*/

beforeEach(function () {
    $this->seed(Database\Seeders\TypeSeeder::class);
    $this->warehouse = Warehouse::factory()->create(['branche_id' => testBranch()->id]);
    $this->cashbox = Cashbox::create(['name' => 'Caja Principal', 'branche_id' => testBranch()->id, 'cash' => 0, 'balance' => 0]);
    $this->cash = PaymentMethod::create(['name' => 'Efectivo', 'is_cash' => true]);
    $this->qr = PaymentMethod::create(['name' => 'QR Simple', 'is_cash' => false]);

    $product = Product::create(['sku' => 'MED-0001', 'name' => 'Paracetamol', 'category_id' => Category::factory()->create()->id, 'is_active' => true]);
    $this->unit = $product->units()->create(['name' => 'Caja', 'factor' => 1, 'price' => 25, 'is_base' => true, 'barcode' => 'MED-0001']);
    ProductWarehouse::receiveStock($product->id, $this->warehouse->id, 100, 10);

    $this->user = userWith('ver sales', 'crear sales', 'ventas.credito', 'ventas.cobrar', 'ver orders', 'crear orders', 'editar orders',
        'ver consignments', 'crear consignments', 'consignaciones.liquidar');
});

/** El ejemplo del cliente: Bs 20 por QR + Bs 30 en efectivo. */
function qrPlusCash(): array
{
    return [['payment_id' => test()->qr->id, 'amount' => 20], ['payment_id' => test()->cash->id, 'amount' => 30]];
}

/** Método y monto de cada cobro vigente de la venta, ej. ['QR Simple' => 20.0, 'Efectivo' => 30.0]. */
function paymentsByMethod(Order $order): array
{
    return $order->payments()->where('canceled', false)->with('transaction.paymentMethod')->get()
        ->mapWithKeys(fn ($p) => [$p->transaction->paymentMethod->name => (float) $p->amount])->all();
}

it('cobra una venta de Bs 50 con Bs 20 por QR y Bs 30 en efectivo: dos cobros en la misma caja', function () {
    $this->actingAs($this->user)->post(route('sales.store'), [
        'warehouse_id' => $this->warehouse->id,
        'cashbox_id' => $this->cashbox->id,
        'payment_type' => 'cash',
        'payments' => qrPlusCash(),
        'items' => [['product_unit_id' => $this->unit->id, 'quantity' => 2]], // 2 × 25 = 50
    ])->assertSessionHasNoErrors()->assertSessionMissing('error');

    $sale = Order::sole();

    expect((float) $sale->amount)->toBe(50.0)
        ->and((float) $sale->paid_amount)->toBe(50.0)
        ->and(paymentsByMethod($sale))->toEqual(['QR Simple' => 20.0, 'Efectivo' => 30.0])
        ->and((float) $this->cashbox->fresh()->balance)->toBe(50.0);
});

it('rechaza un pago mixto que no suma el total y no registra nada', function () {
    $this->actingAs($this->user)->post(route('sales.store'), [
        'warehouse_id' => $this->warehouse->id,
        'cashbox_id' => $this->cashbox->id,
        'payment_type' => 'cash',
        'payments' => [['payment_id' => $this->qr->id, 'amount' => 20], ['payment_id' => $this->cash->id, 'amount' => 20]],
        'items' => [['product_unit_id' => $this->unit->id, 'quantity' => 2]],
    ])->assertSessionHas('error', fn ($e) => str_contains($e, 'suman 40.00 y hay que cobrar 50.00'));

    expect(Order::count())->toBe(0)
        ->and(OrderPayment::count())->toBe(0)
        ->and((float) ProductWarehouse::where('product_id', $this->unit->product_id)->value('stock'))->toBe(100.0);
});

it('el comprobante del QR queda en la parte pagada por QR', function () {
    $sale = app(SaleService::class)->register([
        'warehouse_id' => $this->warehouse->id, 'cashbox_id' => $this->cashbox->id, 'receipt_photo' => 'receipts/qr.jpg',
        'payments' => [['payment_id' => $this->cash->id, 'amount' => 30], ['payment_id' => $this->qr->id, 'amount' => 20]],
    ], [['product_unit_id' => $this->unit->id, 'quantity' => 2]], $this->user);

    $withPhoto = $sale->payments()->whereNotNull('receipt_photo')->with('transaction.paymentMethod')->sole();
    expect($withPhoto->transaction->paymentMethod->name)->toBe('QR Simple');
});

it('a crédito, el anticipo también se puede pagar combinado', function () {
    $customer = Customer::factory()->create(['credit_limit' => null]);

    $sale = app(SaleService::class)->register([
        'warehouse_id' => $this->warehouse->id, 'cashbox_id' => $this->cashbox->id, 'customer_id' => $customer->id,
        'payment_type' => 'credit', 'due_date' => now()->addDays(15)->toDateString(), 'initial_payment' => 50,
        'payments' => qrPlusCash(),
    ], [['product_unit_id' => $this->unit->id, 'quantity' => 4]], $this->user); // 100, anticipo 50

    expect((float) $sale->paid_amount)->toBe(50.0)
        ->and((float) $sale->balance)->toBe(50.0)
        ->and(paymentsByMethod($sale))->toEqual(['QR Simple' => 20.0, 'Efectivo' => 30.0]);
});

it('un abono de una venta a crédito se puede pagar combinado', function () {
    $customer = Customer::factory()->create(['credit_limit' => null]);
    $sale = app(SaleService::class)->register([
        'warehouse_id' => $this->warehouse->id, 'customer_id' => $customer->id,
        'payment_type' => 'credit', 'due_date' => now()->addDays(15)->toDateString(),
    ], [['product_unit_id' => $this->unit->id, 'quantity' => 4]], $this->user); // 100 a crédito, sin anticipo

    $this->actingAs($this->user)->post(route('sales.payments.store', $sale), [
        'amount' => 50,
        'cashbox_id' => $this->cashbox->id,
        'payments' => qrPlusCash(),
    ])->assertRedirect(route('sales.show', $sale))->assertSessionHas('success');

    expect((float) $sale->fresh()->paid_amount)->toBe(50.0)
        ->and(paymentsByMethod($sale))->toEqual(['QR Simple' => 20.0, 'Efectivo' => 30.0]);
});

it('al confirmar un pedido se puede cobrar combinado', function () {
    $pedido = app(OrderService::class)->create(
        ['warehouse_id' => $this->warehouse->id, 'delivery_date' => now()->addDay()->toDateString()],
        [['product_unit_id' => $this->unit->id, 'quantity' => 2]],
        $this->user
    );

    $this->actingAs($this->user)->post(route('pedidos.confirm', $pedido), [
        'payment_type' => 'cash',
        'cashbox_id' => $this->cashbox->id,
        'payments' => qrPlusCash(),
    ])->assertSessionMissing('error');

    expect($pedido->fresh()->status)->toBe(Order::STATUS_COMPLETED)
        ->and(paymentsByMethod($pedido))->toEqual(['QR Simple' => 20.0, 'Efectivo' => 30.0]);
});

it('al liquidar una consignación, lo vendido se puede cobrar combinado', function () {
    $consignment = app(ConsignmentService::class)->create(
        ['customer_id' => Customer::factory()->create()->id, 'warehouse_id' => $this->warehouse->id],
        [['product_unit_id' => $this->unit->id, 'quantity' => 4]],
        $this->user
    );

    $settlement = app(ConsignmentService::class)->settle($consignment, [
        ['consignment_item_id' => $consignment->items()->first()->id, 'sold' => 2, 'returned' => 2],
    ], ['payment_type' => 'cash', 'cashbox_id' => $this->cashbox->id, 'payments' => qrPlusCash()], $this->user);

    expect(paymentsByMethod($settlement->order))->toEqual(['QR Simple' => 20.0, 'Efectivo' => 30.0]);
});

it('el POS ofrece combinar métodos y el detalle de la venta muestra el pago mixto', function () {
    $this->actingAs($this->user)->get(route('sales.create'))
        ->assertOk()
        ->assertSee('Combinar métodos');

    $sale = app(SaleService::class)->register(
        ['warehouse_id' => $this->warehouse->id, 'cashbox_id' => $this->cashbox->id, 'payments' => qrPlusCash()],
        [['product_unit_id' => $this->unit->id, 'quantity' => 2]],
        $this->user
    );

    $this->get(route('sales.show', $sale))
        ->assertOk()
        ->assertSee('Pago mixto: QR Simple 20.00 · Efectivo 30.00')
        ->assertSee('Cobros (pago mixto)');
});
