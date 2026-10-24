<?php

use App\Models\Cashbox;
use App\Models\PaymentMethod;
use App\Models\Purchase;
use App\Models\PurchasePayment;
use App\Models\Supplier;
use App\Models\Transaction;
use App\Models\Type;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\PurchasePaymentService;

/*
|--------------------------------------------------------------------------
| PurchasePaymentController: Pagos de compras a crédito (abonos), anulación y políticas
|--------------------------------------------------------------------------
*/

beforeEach(function () {
    $this->seed(\Database\Seeders\TypeSeeder::class);
});

function creditPurchaseFor(array $overrides = []): Purchase
{
    $branch = testBranch();
    $warehouse = Warehouse::factory()->create(['branche_id' => $branch->id]);
    $supplier = Supplier::create(['name' => 'Proveedor Test '.uniqid(), 'document' => uniqid()]);

    return Purchase::create($overrides + [
        'warehouse_id' => $warehouse->id,
        'branche_id' => $branch->id,
        'user_id' => User::factory()->create(['branche_id' => $branch->id])->id,
        'supplier_id' => $supplier->id,
        'amount' => 500,
        'status' => Purchase::STATUS_RECEIVED,
        'payment_type' => Purchase::PAYMENT_CREDIT,
        'paid_amount' => 100,
        'date' => now()->toDateString(),
    ]);
}

function validPurchasePaymentPayload(Purchase $purchase): array
{
    return [
        'amount' => '50.00',
        'payment_id' => PaymentMethod::factory()->cash()->create()->id,
        'cashbox_id' => Cashbox::factory()->create(['branche_id' => $purchase->branche_id])->id,
    ];
}

function createPurchasePayment(Purchase $purchase, User $user): PurchasePayment
{
    $type = Type::firstOrCreate(['code' => 'COM'], ['name' => 'Compra', 'sign' => '-']);
    $cashbox = Cashbox::factory()->create(['branche_id' => $purchase->branche_id]);
    $method = PaymentMethod::factory()->cash()->create();

    $trx = Transaction::create([
        'number' => 'TRX-'.uniqid(),
        'date' => now(),
        'amount' => 50,
        'canceled' => false,
        'type_id' => $type->id,
        'payment_id' => $method->id,
        'cashbox_id' => $cashbox->id,
        'branche_id' => $purchase->branche_id,
        'user_id' => $user->id,
    ]);

    return PurchasePayment::create([
        'purchase_id' => $purchase->id,
        'transaction_id' => $trx->id,
        'user_id' => $user->id,
        'amount' => 50,
        'canceled' => false,
    ]);
}

describe('pago de compras a crédito (store)', function () {
    it('rechaza (403) a un usuario sin permiso compras.pagar', function () {
        $user = userWith('ver purchases');
        $this->actingAs($user);
        $purchase = creditPurchaseFor();

        $this->post(route('purchases.payments.store', $purchase), validPurchasePaymentPayload($purchase))
            ->assertForbidden();
    });

    it('bloquea el pago (403) si la compra ya está totalmente pagada (saldo 0)', function () {
        $this->actingAs(userWith('ver purchases', 'compras.pagar'));
        $purchase = creditPurchaseFor(['amount' => 200, 'paid_amount' => 200]);

        $this->post(route('purchases.payments.store', $purchase), validPurchasePaymentPayload($purchase))
            ->assertForbidden();
    });

    it('bloquea el pago (403) si la compra es al contado', function () {
        $this->actingAs(userWith('ver purchases', 'compras.pagar'));
        $purchase = creditPurchaseFor(['payment_type' => Purchase::PAYMENT_CASH, 'paid_amount' => 0]);

        $this->post(route('purchases.payments.store', $purchase), validPurchasePaymentPayload($purchase))
            ->assertForbidden();
    });

    it('valida campos obligatorios (monto, método de pago, caja)', function () {
        $this->actingAs(userWith('ver purchases', 'compras.pagar'));
        $purchase = creditPurchaseFor();

        $this->post(route('purchases.payments.store', $purchase), [])
            ->assertSessionHasErrors(['amount', 'payment_id', 'cashbox_id']);
    });

    it('registra el pago exitosamente cuando los datos son válidos', function () {
        $user = userWith('ver purchases', 'compras.pagar');
        $this->actingAs($user);
        $purchase = creditPurchaseFor();
        $payload = validPurchasePaymentPayload($purchase);

        $mockService = mock(PurchasePaymentService::class);
        $mockService->shouldReceive('pay')->once()->with(
            Mockery::on(fn ($arg) => $arg->id === $purchase->id),
            50.0,
            Mockery::on(fn ($arg) => $arg->id === (int) $payload['payment_id']),
            Mockery::on(fn ($arg) => $arg->id === (int) $payload['cashbox_id']),
            Mockery::on(fn ($arg) => $arg->id === $user->id)
        );
        $this->app->instance(PurchasePaymentService::class, $mockService);

        $this->post(route('purchases.payments.store', $purchase), $payload)
            ->assertRedirect(route('purchases.show', $purchase))
            ->assertSessionHas('success');
    });
});

describe('anulación de pago (destroy)', function () {
    it('exige el permiso específico "compras.anular" para anular un pago', function () {
        $user = userWith('ver purchases', 'compras.pagar'); // sin "compras.anular"
        $this->actingAs($user);
        $purchase = creditPurchaseFor();
        $payment = createPurchasePayment($purchase, $user);

        $this->delete(route('purchases.payments.destroy', [$purchase, $payment]))
            ->assertForbidden();
    });

    it('anula el pago cuando el usuario tiene permiso "compras.anular"', function () {
        $user = userWith('ver purchases', 'compras.anular');
        $this->actingAs($user);
        $purchase = creditPurchaseFor();
        $payment = createPurchasePayment($purchase, $user);

        $mockService = mock(PurchasePaymentService::class);
        $mockService->shouldReceive('cancelPayment')->once()->with(Mockery::on(fn ($arg) => $arg->id === $payment->id));
        $this->app->instance(PurchasePaymentService::class, $mockService);

        $this->delete(route('purchases.payments.destroy', [$purchase, $payment]))
            ->assertRedirect(route('purchases.show', $purchase))
            ->assertSessionHas('success');
    });

    it('responde 404 si el pago no pertenece a la compra indicada', function () {
        $user = userWith('ver purchases', 'compras.anular');
        $this->actingAs($user);
        $purchase1 = creditPurchaseFor();
        $purchase2 = creditPurchaseFor();
        $payment = createPurchasePayment($purchase1, $user);

        $this->delete(route('purchases.payments.destroy', [$purchase2, $payment]))
            ->assertNotFound();
    });
});
