<?php

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Order;
use App\Services\SaleService;

/*
|--------------------------------------------------------------------------
| SaleController: Listado de ventas, consulta contado/crédito, anulación y permisos
|--------------------------------------------------------------------------
*/

// ─────────────────────────────────────────────────────────────────────────────
// Autenticación y permisos
// ─────────────────────────────────────────────────────────────────────────────

describe('acceso', function () {
    it('redirige al login a un visitante', function () {
        $this->get(route('sales.index'))->assertRedirect(route('login'));
    });

    it('rechaza (403) a un usuario sin permisos de ventas', function () {
        $this->actingAs(userWith());
        $customer = Customer::factory()->create();
        $sale = orderFor($customer);

        $this->get(route('sales.index'))->assertForbidden();
        $this->get(route('sales.show', $sale))->assertForbidden();
        $this->get(route('sales.create'))->assertForbidden();
        $this->post(route('sales.cancel', $sale))->assertForbidden();
    });

    it('permite ver listado y detalle con el permiso "ver sales"', function () {
        $this->actingAs(userWith('ver sales'));
        $customer = Customer::factory()->create();
        $sale = orderFor($customer);

        $this->get(route('sales.index'))->assertOk();
        $this->get(route('sales.show', $sale))->assertOk();
        $this->get(route('sales.create'))->assertForbidden();
    });

    it('permite acceder al POS para registrar venta con "ver sales" y "crear sales"', function () {
        $this->actingAs(userWith('ver sales', 'crear sales'));

        $this->get(route('sales.create'))
            ->assertOk()
            ->assertViewIs('sales.create');
    });

    it('exige el permiso específico "ventas.anular" para anular una venta cobrada', function () {
        $this->actingAs(userWith('ver sales', 'crear sales')); // no tiene "ventas.anular"
        $customer = Customer::factory()->create();
        $sale = orderFor($customer, ['status' => Order::STATUS_COMPLETED]);

        $this->post(route('sales.cancel', $sale))->assertForbidden();
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// index
// ─────────────────────────────────────────────────────────────────────────────

describe('index', function () {
    beforeEach(fn () => $this->actingAs(userWith('ver sales')));

    it('lista solo ventas completadas o anuladas (no muestra pedidos pendientes)', function () {
        $customer = Customer::factory()->create();

        $completed = orderFor($customer, ['status' => Order::STATUS_COMPLETED]);
        $pendingOrder = orderFor($customer, ['status' => Order::STATUS_PENDING]);

        $response = $this->get(route('sales.index'))->assertOk()->assertViewIs('sales.index');

        $sales = $response->viewData('sales');
        $saleIds = $sales->pluck('id')->all();

        expect($saleIds)->toContain($completed->id)
            ->and($saleIds)->not->toContain($pendingOrder->id);
    });

    it('filtra por rango de fechas (from/to)', function () {
        $customer = Customer::factory()->create();

        $dentro = orderFor($customer, ['date' => '2025-06-15']);
        $antes = orderFor($customer, ['date' => '2025-05-01']);
        $despues = orderFor($customer, ['date' => '2025-07-01']);

        $sales = $this->get(route('sales.index', ['from' => '2025-06-01', 'to' => '2025-06-30']))
            ->viewData('sales')->pluck('id')->all();

        expect($sales)->toContain($dentro->id)
            ->and($sales)->not->toContain($antes->id)
            ->and($sales)->not->toContain($despues->id);
    });

    it('filtra por cliente', function () {
        $c1 = Customer::factory()->create();
        $c2 = Customer::factory()->create();

        $deC1 = orderFor($c1);
        $deC2 = orderFor($c2);

        $sales = $this->get(route('sales.index', ['customer' => $c1->id]))
            ->viewData('sales')->pluck('id')->all();

        expect($sales)->toContain($deC1->id)
            ->and($sales)->not->toContain($deC2->id);
    });

    it('filtra por el usuario que registró la venta', function () {
        $vendedor = userWith('ver sales');
        $otro = userWith('ver sales');
        $customer = Customer::factory()->create();

        $deVendedor = orderFor($customer, ['user_id' => $vendedor->id]);
        $deOtro = orderFor($customer, ['user_id' => $otro->id]);

        $sales = $this->get(route('sales.index', ['user' => $vendedor->id]))
            ->viewData('sales')->pluck('id')->all();

        expect($sales)->toContain($deVendedor->id)
            ->and($sales)->not->toContain($deOtro->id);
    });

    it('combina fecha, cliente y usuario a la vez', function () {
        $vendedor = userWith('ver sales');
        $customer = Customer::factory()->create();
        $otroCustomer = Customer::factory()->create();

        $match = orderFor($customer, ['user_id' => $vendedor->id, 'date' => '2025-06-15']);
        $otroCliente = orderFor($otroCustomer, ['user_id' => $vendedor->id, 'date' => '2025-06-15']);
        $fueraDeFecha = orderFor($customer, ['user_id' => $vendedor->id, 'date' => '2025-01-01']);

        $sales = $this->get(route('sales.index', [
            'from' => '2025-06-01', 'to' => '2025-06-30', 'customer' => $customer->id, 'user' => $vendedor->id,
        ]))->viewData('sales')->pluck('id')->all();

        expect($sales)->toContain($match->id)
            ->and($sales)->not->toContain($otroCliente->id)
            ->and($sales)->not->toContain($fueraDeFecha->id);
    });

    it('filtra por sucursal', function () {
        $branchA = testBranch();
        $branchB = Branch::factory()->create();
        $customer = Customer::factory()->create();

        $deA = orderFor($customer, ['branche_id' => $branchA->id]);
        $deB = orderFor($customer, ['branche_id' => $branchB->id]);

        $sales = $this->get(route('sales.index', ['branch' => $branchA->id]))
            ->viewData('sales')->pluck('id')->all();

        expect($sales)->toContain($deA->id)
            ->and($sales)->not->toContain($deB->id);
    });

    it('el botón "Limpiar" solo aparece cuando hay algún filtro aplicado', function () {
        $customer = Customer::factory()->create();
        orderFor($customer);

        $this->get(route('sales.index'))->assertDontSee('<span>Limpiar</span>', false);
        $this->get(route('sales.index', ['customer' => $customer->id]))->assertSee('<span>Limpiar</span>', false);
    });

    it('filtra por estado de venta (filter[currentStatus])', function () {
        $customer = Customer::factory()->create();
        $completed = orderFor($customer, ['status' => Order::STATUS_COMPLETED]);
        $canceled = orderFor($customer, ['status' => Order::STATUS_CANCELED]);

        $sales = $this->get(route('sales.index', ['filter' => ['currentStatus' => Order::STATUS_CANCELED]]))
            ->viewData('sales')->pluck('id')->all();

        expect($sales)->toContain($canceled->id)
            ->and($sales)->not->toContain($completed->id);
    });

    it('ordena por los campos permitidos y rechaza los demás con 400', function () {
        $this->get(route('sales.index', ['sort' => 'id']))->assertOk();
        $this->get(route('sales.index', ['sort' => '-amount']))->assertOk();
        $this->get(route('sales.index', ['sort' => 'user_id']))->assertStatus(400);
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// show
// ─────────────────────────────────────────────────────────────────────────────

describe('show', function () {
    beforeEach(fn () => $this->actingAs(userWith('ver sales')));

    it('muestra la vista de venta al contado para compras en efectivo', function () {
        $customer = Customer::factory()->create();
        $sale = orderFor($customer, [
            'payment_type' => Order::PAYMENT_CASH,
            'status' => Order::STATUS_COMPLETED,
        ]);

        $this->get(route('sales.show', $sale))
            ->assertOk()
            ->assertViewIs('sales.show-cash')
            ->assertViewHas('order', fn ($o) => $o->id === $sale->id);
    });

    it('muestra la vista de venta a crédito con opciones de cobro para ventas a crédito', function () {
        $customer = Customer::factory()->create();
        $sale = orderFor($customer, [
            'payment_type' => Order::PAYMENT_CREDIT,
            'status' => Order::STATUS_COMPLETED,
            'due_date' => now()->addDays(30)->toDateString(),
            'paid_amount' => 20,
            'amount' => 100,
        ]);

        $this->get(route('sales.show', $sale))
            ->assertOk()
            ->assertViewIs('sales.show-credit')
            ->assertViewHas('order', fn ($o) => $o->id === $sale->id)
            ->assertViewHas('paymentMethods')
            ->assertViewHas('cashboxes');
    });

    it('responde 404 si la orden consultada está en espera (pedido)', function () {
        $customer = Customer::factory()->create();
        $pendingOrder = orderFor($customer, ['status' => Order::STATUS_PENDING]);

        $this->get(route('sales.show', $pendingOrder))->assertNotFound();
    });

    it('responde 404 si la venta no existe', function () {
        $this->get(route('sales.show', 99999))->assertNotFound();
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// cancel
// ─────────────────────────────────────────────────────────────────────────────

describe('cancel', function () {
    it('anula una venta completada cuando el usuario tiene permiso ventas.anular', function () {
        $user = userWith('ver sales', 'ventas.anular');
        $this->actingAs($user);

        $customer = Customer::factory()->create();
        $sale = orderFor($customer, ['status' => Order::STATUS_COMPLETED]);

        // Simular SaleService::cancel
        $mockService = mock(SaleService::class);
        $mockService->shouldReceive('cancel')->once()->with(Mockery::on(fn ($arg) => $arg->id === $sale->id));
        $this->app->instance(SaleService::class, $mockService);

        $this->post(route('sales.cancel', $sale))
            ->assertRedirect(route('sales.show', $sale))
            ->assertSessionHas('success');
    });

    it('responde 404 si se intenta anular un pedido en espera desde la ruta de ventas', function () {
        $user = userWith('ver sales', 'ventas.anular');
        $this->actingAs($user);

        $customer = Customer::factory()->create();
        $pendingOrder = orderFor($customer, ['status' => Order::STATUS_PENDING]);

        $this->post(route('sales.cancel', $pendingOrder))->assertNotFound();
    });
});
