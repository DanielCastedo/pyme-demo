<?php

use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;

/*
|--------------------------------------------------------------------------
| PayableController: Cuentas por pagar (compras a crédito pendientes de pago)
|--------------------------------------------------------------------------
*/

/** Compra mínima con los campos que le interesan a cada prueba. */
function purchaseFor(Supplier $supplier, array $overrides = []): Purchase
{
    $branch = testBranch();
    $warehouse = Warehouse::factory()->create(['branche_id' => $branch->id]);

    return Purchase::create($overrides + [
        'warehouse_id' => $warehouse->id,
        'branche_id' => $branch->id,
        'user_id' => User::factory()->create(['branche_id' => $branch->id])->id,
        'supplier_id' => $supplier->id,
        'amount' => 100,
        'status' => Purchase::STATUS_RECEIVED,
        'payment_type' => Purchase::PAYMENT_CASH,
        'paid_amount' => 0,
        'date' => now()->toDateString(),
    ]);
}

describe('acceso', function () {
    it('redirige al login a un visitante', function () {
        $this->get(route('payables.index'))->assertRedirect(route('login'));
    });

    it('rechaza (403) a un usuario sin permiso "ver purchases"', function () {
        $this->actingAs(userWith());

        $this->get(route('payables.index'))->assertForbidden();
    });

    it('permite el acceso con el permiso "ver purchases"', function () {
        $this->actingAs(userWith('ver purchases'));

        $this->get(route('payables.index'))->assertOk();
    });
});

describe('listado y filtros de cuentas por pagar', function () {
    beforeEach(fn () => $this->actingAs(userWith('ver purchases')));

    it('lista únicamente compras a crédito recibidas con saldo pendiente', function () {
        $supplier = Supplier::create(['name' => 'Proveedor Uno', 'document' => '111']);

        $creditoPendiente = purchaseFor($supplier, [
            'payment_type' => Purchase::PAYMENT_CREDIT, 'amount' => 500, 'paid_amount' => 200,
        ]);
        $contado = purchaseFor($supplier, [
            'payment_type' => Purchase::PAYMENT_CASH, 'amount' => 300, 'paid_amount' => 0,
        ]);
        $creditoPagado = purchaseFor($supplier, [
            'payment_type' => Purchase::PAYMENT_CREDIT, 'amount' => 150, 'paid_amount' => 150,
        ]);
        $anulada = purchaseFor($supplier, [
            'payment_type' => Purchase::PAYMENT_CREDIT, 'status' => Purchase::STATUS_CANCELED, 'amount' => 200, 'paid_amount' => 0,
        ]);

        $ids = $this->get(route('payables.index'))
            ->assertOk()->assertViewIs('payables.index')
            ->viewData('payables')->pluck('id')->all();

        expect($ids)->toContain($creditoPendiente->id)
            ->and($ids)->not->toContain($contado->id)
            ->and($ids)->not->toContain($creditoPagado->id)
            ->and($ids)->not->toContain($anulada->id);
    });

    it('calcula los totales adeudados y vencidos correctamente', function () {
        $supplier = Supplier::create(['name' => 'Proveedor Dos', 'document' => '222']);

        purchaseFor($supplier, [
            'payment_type' => Purchase::PAYMENT_CREDIT, 'amount' => 500, 'paid_amount' => 200,
            'due_date' => now()->addDays(10)->toDateString(),
        ]);
        purchaseFor($supplier, [
            'payment_type' => Purchase::PAYMENT_CREDIT, 'amount' => 400, 'paid_amount' => 0,
            'due_date' => now()->subDays(5)->toDateString(),
        ]);

        $response = $this->get(route('payables.index'))->assertOk();

        expect((float) $response->viewData('totalOwed'))->toBe(700.0)
            ->and((float) $response->viewData('totalOverdue'))->toBe(400.0);
    });

    it('filtra cuentas vencidas con filter[vencidas]=1', function () {
        $supplier = Supplier::create(['name' => 'Proveedor Tres', 'document' => '333']);

        $alDia = purchaseFor($supplier, [
            'payment_type' => Purchase::PAYMENT_CREDIT, 'amount' => 200, 'paid_amount' => 0,
            'due_date' => now()->addDays(15)->toDateString(),
        ]);
        $vencida = purchaseFor($supplier, [
            'payment_type' => Purchase::PAYMENT_CREDIT, 'amount' => 100, 'paid_amount' => 0,
            'due_date' => now()->subDays(2)->toDateString(),
        ]);

        $ids = $this->get(route('payables.index', ['filter' => ['vencidas' => '1']]))
            ->viewData('payables')->pluck('id')->all();

        expect($ids)->toContain($vencida->id)
            ->and($ids)->not->toContain($alDia->id);
    });

    it('busca por proveedor o número de compra (filter[buscar])', function () {
        $s1 = Supplier::create(['name' => 'Ferretería Illimani', 'document' => '444']);
        $s2 = Supplier::create(['name' => 'Materiales Del Sur', 'document' => '555']);

        purchaseFor($s1, ['payment_type' => Purchase::PAYMENT_CREDIT, 'amount' => 100, 'paid_amount' => 0]);
        purchaseFor($s2, ['payment_type' => Purchase::PAYMENT_CREDIT, 'amount' => 100, 'paid_amount' => 0]);

        $names = $this->get(route('payables.index', ['filter' => ['buscar' => 'Illimani']]))
            ->viewData('payables')->pluck('supplier.name')->all();

        expect($names)->toContain('Ferretería Illimani')
            ->and($names)->not->toContain('Materiales Del Sur');
    });

    it('ordena por los campos permitidos y rechaza los no permitidos con 400', function () {
        $this->get(route('payables.index', ['sort' => 'due_date']))->assertOk();
        $this->get(route('payables.index', ['sort' => '-amount']))->assertOk();
        $this->get(route('payables.index', ['sort' => 'created_at']))->assertStatus(400);
    });
});
