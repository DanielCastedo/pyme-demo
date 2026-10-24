<?php

use App\Models\Cashbox;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\PaymentMethod;
use App\Models\Transaction;
use App\Models\Type;
use App\Models\User;
use App\Services\OrderPaymentService;

/*
|--------------------------------------------------------------------------
| SalePaymentController: Cobros de ventas a crédito (abonos), anulación y políticas
|--------------------------------------------------------------------------
*/

function validPaymentPayload(Order $sale): array
{
    $cashbox = Cashbox::factory()->create(['branche_id' => $sale->branche_id]);
    $method = PaymentMethod::factory()->cash()->create();

    return [
        'amount' => '50.00',
        'payment_id' => $method->id,
        'cashbox_id' => $cashbox->id,
    ];
}

function createOrderPayment(Order $sale, User $user): OrderPayment
{
    $type = Type::firstOrCreate(['code' => 'ING_TEST'], ['name' => 'Ingreso Test', 'sign' => '+']);
    $cashbox = Cashbox::factory()->create(['branche_id' => $sale->branche_id]);
    $method = PaymentMethod::factory()->cash()->create();

    $trx = Transaction::create([
        'number' => 'TRX-' . uniqid(),
        'date' => now(),
        'amount' => 50,
        'canceled' => false,
        'type_id' => $type->id,
        'payment_id' => $method->id,
        'cashbox_id' => $cashbox->id,
        'branche_id' => $sale->branche_id,
        'user_id' => $user->id,
    ]);

    return OrderPayment::create([
        'order_id' => $sale->id,
        'transaction_id' => $trx->id,
        'user_id' => $user->id,
        'amount' => 50,
        'canceled' => false,
    ]);
}

describe('cobro de ventas a crédito (store)', function () {
    it('rechaza (403) a un usuario sin permiso de ventas', function () {
        $this->actingAs(userWith());
        $customer = Customer::factory()->create();
        $sale = orderFor($customer, [
            'payment_type' => Order::PAYMENT_CREDIT,
            'status' => Order::STATUS_COMPLETED,
            'amount' => 500,
            'paid_amount' => 100,
        ]);

        $this->post(route('sales.payments.store', $sale), validPaymentPayload($sale))
            ->assertForbidden();
    });

    it('bloquea el cobro (403) si la venta ya está totalmente pagada (saldo 0)', function () {
        $this->actingAs(userWith('ver sales', 'crear sales'));
        $customer = Customer::factory()->create();
        $sale = orderFor($customer, [
            'payment_type' => Order::PAYMENT_CREDIT,
            'status' => Order::STATUS_COMPLETED,
            'amount' => 200,
            'paid_amount' => 200, // Saldo = 0
        ]);

        $this->post(route('sales.payments.store', $sale), validPaymentPayload($sale))
            ->assertForbidden();
    });

    it('responde 404 si la orden es un pedido pendiente y no una venta', function () {
        $this->actingAs(userWith('ver sales', 'crear sales'));
        $customer = Customer::factory()->create();
        $pendingOrder = orderFor($customer, [
            'status' => Order::STATUS_PENDING,
        ]);

        $this->post(route('sales.payments.store', $pendingOrder), validPaymentPayload($pendingOrder))
            ->assertNotFound();
    });

    it('valida campos obligatorios de cobro (monto, método de pago, caja)', function () {
        $this->actingAs(userWith('ver sales', 'crear sales'));
        $customer = Customer::factory()->create();
        $sale = orderFor($customer, [
            'payment_type' => Order::PAYMENT_CREDIT,
            'status' => Order::STATUS_COMPLETED,
            'amount' => 500,
            'paid_amount' => 100,
        ]);

        $this->post(route('sales.payments.store', $sale), [])
            ->assertSessionHasErrors(['amount', 'payment_id', 'cashbox_id']);
    });

    it('registra el abono exitosamente cuando los datos son válidos', function () {
        $user = userWith('ver sales', 'crear sales', 'ventas.cobrar');
        $this->actingAs($user);

        $customer = Customer::factory()->create();
        $sale = orderFor($customer, [
            'payment_type' => Order::PAYMENT_CREDIT,
            'status' => Order::STATUS_COMPLETED,
            'amount' => 500,
            'paid_amount' => 100,
        ]);

        $payload = validPaymentPayload($sale);

        $mockService = mock(OrderPaymentService::class);
        $mockService->shouldReceive('collect')->once()->with(
            Mockery::on(fn ($arg) => $arg->id === $sale->id),
            50.0,
            Mockery::on(fn ($arg) => $arg->id === (int) $payload['payment_id']),
            Mockery::on(fn ($arg) => $arg->id === (int) $payload['cashbox_id']),
            Mockery::on(fn ($arg) => $arg->id === $user->id)
        );
        $this->app->instance(OrderPaymentService::class, $mockService);

        $this->post(route('sales.payments.store', $sale), $payload)
            ->assertRedirect(route('sales.show', $sale))
            ->assertSessionHas('success');
    });
});

describe('anulación de abono (destroy)', function () {
    it('exige el permiso específico "ventas.anular" para anular un cobro', function () {
        $user = userWith('ver sales', 'crear sales'); // sin "ventas.anular"
        $this->actingAs($user);
        $customer = Customer::factory()->create();
        $sale = orderFor($customer, ['status' => Order::STATUS_COMPLETED]);

        $payment = createOrderPayment($sale, $user);

        $this->delete(route('sales.payments.destroy', [$sale, $payment]))
            ->assertForbidden();
    });

    it('anula el cobro cuando el usuario tiene permiso "ventas.anular"', function () {
        $user = userWith('ver sales', 'ventas.anular');
        $this->actingAs($user);

        $customer = Customer::factory()->create();
        $sale = orderFor($customer, ['status' => Order::STATUS_COMPLETED]);

        $payment = createOrderPayment($sale, $user);

        $mockService = mock(OrderPaymentService::class);
        $mockService->shouldReceive('cancelPayment')->once()->with(Mockery::on(fn ($arg) => $arg->id === $payment->id));
        $this->app->instance(OrderPaymentService::class, $mockService);

        $this->delete(route('sales.payments.destroy', [$sale, $payment]))
            ->assertRedirect(route('sales.show', $sale))
            ->assertSessionHas('success');
    });

    it('responde 404 si el cobro no pertenece a la venta indicada', function () {
        $user = userWith('ver sales', 'ventas.anular');
        $this->actingAs($user);

        $customer = Customer::factory()->create();
        $sale1 = orderFor($customer, ['status' => Order::STATUS_COMPLETED]);
        $sale2 = orderFor($customer, ['status' => Order::STATUS_COMPLETED]);

        $payment = createOrderPayment($sale1, $user);

        $this->delete(route('sales.payments.destroy', [$sale2, $payment]))
            ->assertNotFound();
    });
});
