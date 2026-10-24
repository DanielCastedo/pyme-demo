<?php

use App\Models\Customer;
use App\Models\Order;

/*
|--------------------------------------------------------------------------
| ReceivableController: Cuentas por cobrar (ventas a crédito pendientes de pago)
|--------------------------------------------------------------------------
*/

describe('acceso', function () {
    it('redirige al login a un visitante', function () {
        $this->get(route('receivables.index'))->assertRedirect(route('login'));
    });

    it('rechaza (403) a un usuario sin permiso "ver sales"', function () {
        $this->actingAs(userWith());

        $this->get(route('receivables.index'))->assertForbidden();
    });

    it('permite el acceso con el permiso "ver sales"', function () {
        $this->actingAs(userWith('ver sales'));

        $this->get(route('receivables.index'))->assertOk();
    });
});

describe('listado y filtros de cuentas por cobrar', function () {
    beforeEach(fn () => $this->actingAs(userWith('ver sales')));

    it('lista únicamente ventas a crédito completadas con saldo pendiente', function () {
        $customer = Customer::factory()->create();

        // Venta a crédito con saldo pendiente -> DEBE aparecer
        $creditoPendiente = orderFor($customer, [
            'payment_type' => Order::PAYMENT_CREDIT,
            'status' => Order::STATUS_COMPLETED,
            'amount' => 500,
            'paid_amount' => 200,
        ]);

        // Venta al contado -> NO debe aparecer
        $contado = orderFor($customer, [
            'payment_type' => Order::PAYMENT_CASH,
            'status' => Order::STATUS_COMPLETED,
            'amount' => 300,
            'paid_amount' => 300,
        ]);

        // Venta a crédito ya pagada por completo -> NO debe aparecer
        $creditoPagado = orderFor($customer, [
            'payment_type' => Order::PAYMENT_CREDIT,
            'status' => Order::STATUS_COMPLETED,
            'amount' => 150,
            'paid_amount' => 150,
        ]);

        // Venta anulada -> NO debe aparecer
        $anulada = orderFor($customer, [
            'payment_type' => Order::PAYMENT_CREDIT,
            'status' => Order::STATUS_CANCELED,
            'amount' => 200,
            'paid_amount' => 0,
        ]);

        $response = $this->get(route('receivables.index'))
            ->assertOk()
            ->assertViewIs('receivables.index');

        $ids = $response->viewData('receivables')->pluck('id')->all();

        expect($ids)->toContain($creditoPendiente->id)
            ->and($ids)->not->toContain($contado->id)
            ->and($ids)->not->toContain($creditoPagado->id)
            ->and($ids)->not->toContain($anulada->id);
    });

    it('calcula los totales adeudados y vencidos correctamente', function () {
        $customer = Customer::factory()->create();

        // 1. Crédito vigente (no vencido): debe 300
        orderFor($customer, [
            'payment_type' => Order::PAYMENT_CREDIT,
            'status' => Order::STATUS_COMPLETED,
            'amount' => 500,
            'paid_amount' => 200,
            'due_date' => now()->addDays(10)->toDateString(),
        ]);

        // 2. Crédito vencido: debe 400
        orderFor($customer, [
            'payment_type' => Order::PAYMENT_CREDIT,
            'status' => Order::STATUS_COMPLETED,
            'amount' => 400,
            'paid_amount' => 0,
            'due_date' => now()->subDays(5)->toDateString(),
        ]);

        $response = $this->get(route('receivables.index'))->assertOk();

        expect((float) $response->viewData('totalOwed'))->toBe(700.0)
            ->and((float) $response->viewData('totalOverdue'))->toBe(400.0);
    });

    it('filtra cuentas vencidas con filter[vencidas]=1', function () {
        $customer = Customer::factory()->create();

        $alDia = orderFor($customer, [
            'payment_type' => Order::PAYMENT_CREDIT,
            'status' => Order::STATUS_COMPLETED,
            'amount' => 200,
            'paid_amount' => 0,
            'due_date' => now()->addDays(15)->toDateString(),
        ]);

        $vencida = orderFor($customer, [
            'payment_type' => Order::PAYMENT_CREDIT,
            'status' => Order::STATUS_COMPLETED,
            'amount' => 100,
            'paid_amount' => 0,
            'due_date' => now()->subDays(2)->toDateString(),
        ]);

        $ids = $this->get(route('receivables.index', ['filter' => ['vencidas' => '1']]))
            ->viewData('receivables')->pluck('id')->all();

        expect($ids)->toContain($vencida->id)
            ->and($ids)->not->toContain($alDia->id);
    });

    it('busca por cliente o número de venta (filter[buscar])', function () {
        $c1 = Customer::factory()->create(['name' => 'Comercializadora Illimani']);
        $c2 = Customer::factory()->create(['name' => 'Transportes Del Sur']);

        orderFor($c1, [
            'payment_type' => Order::PAYMENT_CREDIT,
            'status' => Order::STATUS_COMPLETED,
            'amount' => 100,
            'paid_amount' => 0,
        ]);

        orderFor($c2, [
            'payment_type' => Order::PAYMENT_CREDIT,
            'status' => Order::STATUS_COMPLETED,
            'amount' => 100,
            'paid_amount' => 0,
        ]);

        $names = $this->get(route('receivables.index', ['filter' => ['buscar' => 'Illimani']]))
            ->viewData('receivables')->pluck('customer.name')->all();

        expect($names)->toContain('Comercializadora Illimani')
            ->and($names)->not->toContain('Transportes Del Sur');
    });

    it('ordena por los campos permitidos y rechaza los no permitidos con 400', function () {
        $this->get(route('receivables.index', ['sort' => 'due_date']))->assertOk();
        $this->get(route('receivables.index', ['sort' => '-amount']))->assertOk();
        $this->get(route('receivables.index', ['sort' => 'created_at']))->assertStatus(400);
    });
});
