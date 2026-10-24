<?php

use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use App\Models\Warehouse;

/*
|--------------------------------------------------------------------------
| PedidoController: Órdenes en espera con fecha de entrega, permisos y confirmación
|--------------------------------------------------------------------------
*/

/** Crea un pedido (orden con fecha de entrega y estado pendiente). */
function pedidoFor(Customer $customer, array $overrides = []): Order
{
    $branch = testBranch();
    $warehouse = Warehouse::firstOrCreate(
        ['code' => 'ALM-TEST'],
        ['name' => 'Almacén de prueba', 'is_active' => true, 'branche_id' => $branch->id]
    );

    return Order::create($overrides + [
        'user_id' => User::query()->value('id') ?? User::factory()->create(['branche_id' => $branch->id])->id,
        'customer_id' => $customer->id,
        'warehouse_id' => $warehouse->id,
        'branche_id' => $branch->id,
        'date' => now()->toDateString(),
        'delivery_date' => now()->addDays(5)->toDateString(),
        'amount' => 250,
        'paid_amount' => 0,
        'payment_type' => Order::PAYMENT_CASH,
        'status' => Order::STATUS_PENDING,
    ]);
}

// ─────────────────────────────────────────────────────────────────────────────
// Autenticación y permisos
// ─────────────────────────────────────────────────────────────────────────────

describe('acceso', function () {
    it('redirige al login a un visitante', function () {
        $this->get(route('pedidos.index'))->assertRedirect(route('login'));
    });

    it('rechaza (403) a un usuario sin permisos de pedidos', function () {
        $this->actingAs(userWith());
        $customer = Customer::factory()->create();
        $pedido = pedidoFor($customer);

        $this->get(route('pedidos.index'))->assertForbidden();
        $this->get(route('pedidos.show', $pedido))->assertForbidden();
        $this->get(route('pedidos.create'))->assertForbidden();
        $this->get(route('pedidos.edit', $pedido))->assertForbidden();
        $this->delete(route('pedidos.destroy', $pedido))->assertForbidden();
    });

    it('permite ver pedidos con el permiso "ver orders"', function () {
        $this->actingAs(userWith('ver orders'));
        $customer = Customer::factory()->create();
        $pedido = pedidoFor($customer);

        $this->get(route('pedidos.index'))->assertOk();
        $this->get(route('pedidos.show', $pedido))->assertOk();
        $this->get(route('pedidos.create'))->assertForbidden();
        $this->get(route('pedidos.edit', $pedido))->assertForbidden();
        $this->delete(route('pedidos.destroy', $pedido))->assertForbidden();
    });

    it('permite acceder al formulario de creación con "ver orders" y "crear orders"', function () {
        $this->actingAs(userWith('ver orders', 'crear orders'));

        $this->get(route('pedidos.create'))
            ->assertOk()
            ->assertViewIs('pedidos.create');
    });

    it('permite acceder a edición con "ver orders" y "editar orders" cuando el pedido está pendiente', function () {
        $this->actingAs(userWith('ver orders', 'editar orders'));
        $customer = Customer::factory()->create();
        $pedido = pedidoFor($customer, ['status' => Order::STATUS_PENDING]);

        $this->get(route('pedidos.edit', $pedido))
            ->assertOk()
            ->assertViewIs('pedidos.edit');
    });

    it('bloquea editar o eliminar si el pedido ya fue confirmado/completado', function () {
        $this->actingAs(userWith('ver orders', 'editar orders', 'eliminar orders'));
        $customer = Customer::factory()->create();
        $pedido = pedidoFor($customer, ['status' => Order::STATUS_COMPLETED]);

        $this->get(route('pedidos.edit', $pedido))->assertForbidden();
        $this->delete(route('pedidos.destroy', $pedido))->assertForbidden();
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// index
// ─────────────────────────────────────────────────────────────────────────────

describe('index', function () {
    beforeEach(fn () => $this->actingAs(userWith('ver orders')));

    it('lista solo órdenes con fecha de entrega (pedidos) y no ventas directas', function () {
        $customer = Customer::factory()->create();

        $pedido = pedidoFor($customer);
        $ventaDirecta = orderFor($customer, ['delivery_date' => null]);

        $response = $this->get(route('pedidos.index'))->assertOk()->assertViewIs('pedidos.index');

        $ids = $response->viewData('pedidos')->pluck('id')->all();

        expect($ids)->toContain($pedido->id)
            ->and($ids)->not->toContain($ventaDirecta->id);
    });

    it('busca por cliente o ID (filter[buscar])', function (string $term, string $expectedName) {
        $c1 = Customer::factory()->create(['name' => 'Ferretería El Tornillo', 'document' => '99112233']);
        $c2 = Customer::factory()->create(['name' => 'Agropecuaria Chaco', 'document' => '77334455']);

        pedidoFor($c1);
        pedidoFor($c2);

        $results = $this->get(route('pedidos.index', ['filter' => ['buscar' => $term]]))
            ->viewData('pedidos');

        $names = $results->pluck('customer.name')->all();

        expect($names)->toContain($expectedName);
    })->with([
        'por nombre cliente' => ['Tornillo', 'Ferretería El Tornillo'],
        'por documento cliente' => ['77334455', 'Agropecuaria Chaco'],
    ]);

    it('filtra por estado (filter[currentStatus])', function () {
        $customer = Customer::factory()->create();
        $p1 = pedidoFor($customer, ['status' => Order::STATUS_PENDING]);
        $p2 = pedidoFor($customer, ['status' => Order::STATUS_COMPLETED]);

        $pendingIds = $this->get(route('pedidos.index', ['filter' => ['currentStatus' => Order::STATUS_PENDING]]))
            ->viewData('pedidos')->pluck('id')->all();

        expect($pendingIds)->toContain($p1->id)
            ->and($pendingIds)->not->toContain($p2->id);
    });

    it('ordena por los campos permitidos y rechaza los no permitidos con 400', function () {
        $this->get(route('pedidos.index', ['sort' => 'id']))->assertOk();
        $this->get(route('pedidos.index', ['sort' => 'delivery_date']))->assertOk();
        $this->get(route('pedidos.index', ['sort' => 'user_id']))->assertStatus(400);
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// show
// ─────────────────────────────────────────────────────────────────────────────

describe('show', function () {
    beforeEach(fn () => $this->actingAs(userWith('ver orders')));

    it('muestra el detalle del pedido con información de disponibilidad de stock', function () {
        $customer = Customer::factory()->create();
        $pedido = pedidoFor($customer);

        $this->get(route('pedidos.show', $pedido))
            ->assertOk()
            ->assertViewIs('pedidos.show')
            ->assertViewHas('pedido', fn ($p) => $p->id === $pedido->id)
            ->assertViewHas('availability');
    });

    it('responde 404 si la orden no es un pedido (no tiene fecha de entrega)', function () {
        $customer = Customer::factory()->create();
        $ventaDirecta = orderFor($customer, ['delivery_date' => null]);

        $this->get(route('pedidos.show', $ventaDirecta))->assertNotFound();
    });

    it('responde 404 si el pedido no existe', function () {
        $this->get(route('pedidos.show', 99999))->assertNotFound();
    });
});
