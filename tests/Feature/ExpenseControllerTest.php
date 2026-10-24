<?php

use App\Models\Cashbox;
use App\Models\Company;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\PaymentMethod;
use App\Models\SubscriptionPlan;
use App\Models\Supplier;

/*
|--------------------------------------------------------------------------
| ExpenseControllerTest: Pruebas de Gastos Operativos y Categorías
|--------------------------------------------------------------------------
| Gestión de gastos, egresos de caja, edición, anulación con reintegro,
| categorías de gasto, exportación a PDF y botón superior derecho.
*/

beforeEach(function () {
    $this->seed(\Database\Seeders\TypeSeeder::class);

    $company = Company::firstOrCreate(['id' => 1], [
        'name' => 'Ferretería Brazil Importaciones',
        'nit' => '1029384756',
        'currency' => 'Bs',
    ]);

    $plan = SubscriptionPlan::firstOrCreate(['slug' => 'empresa'], [
        'name' => 'Plan Empresa',
        'price' => 100,
        'billing_cycle' => 'monthly',
        'is_active' => true,
    ]);

    $company->subscriptions()->firstOrCreate(['company_id' => $company->id], [
        'subscription_plan_id' => $plan->id,
        'status' => 'active',
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addMonth(),
    ]);
});

describe('Acceso y Permisos de Gastos', function () {
    it('redirige al login a un visitante no autenticado', function () {
        $this->get(route('expenses.index'))->assertRedirect(route('login'));
    });

    it('deniega con 403 a un usuario sin permiso ver expenses', function () {
        $user = userWith();
        $this->actingAs($user)->get(route('expenses.index'))->assertForbidden();
        $this->actingAs($user)->get(route('expenses.create'))->assertForbidden();
    });

    it('permite ver index y show con ver expenses, pero bloquea crear/editar si no tiene el permiso', function () {
        $user = userWith('ver expenses');
        $branch = testBranch();
        $cat = ExpenseCategory::create(['name' => 'Servicios Básicos']);

        $expense = Expense::create([
            'date' => now()->toDateString(),
            'concept' => 'Pago de Agua Potable',
            'amount' => 80.00,
            'status' => Expense::STATUS_PAID,
            'expense_category_id' => $cat->id,
            'branche_id' => $branch->id,
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)->get(route('expenses.index'))->assertOk();
        $this->actingAs($user)->get(route('expenses.show', $expense))->assertOk();
        $this->actingAs($user)->get(route('expenses.create'))->assertForbidden();
        $this->actingAs($user)->get(route('expenses.edit', $expense))->assertForbidden();
    });
});

describe('Registro, Edición y Anulación de Gastos', function () {
    it('valida campos obligatorios como fecha, concepto, monto y caja', function () {
        $user = userWith('ver expenses', 'crear expenses');

        $response = $this->actingAs($user)->post(route('expenses.store'), []);
        $response->assertSessionHasErrors(['date', 'concept', 'amount', 'cashbox_id', 'payment_id']);
    });

    it('registra un gasto descontando dinero de la caja', function () {
        $user = userWith('ver expenses', 'crear expenses');
        $branch = testBranch();
        $cashbox = Cashbox::factory()->create([
            'branche_id' => $branch->id,
            'balance' => 500.00,
            'cash' => 500.00,
        ]);
        $paymentMethod = PaymentMethod::factory()->cash()->create();
        $cat = ExpenseCategory::create(['name' => 'Mantenimiento']);

        $payload = [
            'date' => now()->toDateString(),
            'concept' => 'Reparación de cerradura',
            'amount' => 120.00,
            'expense_category_id' => $cat->id,
            'cashbox_id' => $cashbox->id,
            'payment_id' => $paymentMethod->id,
            'receipt_number' => 'REC-456',
        ];

        $response = $this->actingAs($user)->post(route('expenses.store'), $payload);

        $expense = Expense::where('concept', 'Reparación de cerradura')->first();
        expect($expense)->not->toBeNull();
        expect((float) $expense->amount)->toEqual(120.00);
        expect($expense->status)->toBe(Expense::STATUS_PAID);

        $response->assertRedirect(route('expenses.show', $expense));

        $cashbox->refresh();
        expect((float) $cashbox->balance)->toEqual(380.00);
        expect((float) $cashbox->cash)->toEqual(380.00);
    });

    it('permite actualizar un gasto y ajusta el saldo de la caja', function () {
        $user = userWith('ver expenses', 'crear expenses', 'editar expenses');
        $branch = testBranch();
        $cashbox = Cashbox::factory()->create([
            'branche_id' => $branch->id,
            'balance' => 1000.00,
            'cash' => 1000.00,
        ]);
        $paymentMethod = PaymentMethod::factory()->cash()->create();
        $cat = ExpenseCategory::create(['name' => 'Papelería']);

        $service = app(\App\Services\ExpenseService::class);
        $expense = $service->register([
            'date' => now()->toDateString(),
            'concept' => 'Compra de hojas bond',
            'amount' => 50.00,
            'expense_category_id' => $cat->id,
            'cashbox_id' => $cashbox->id,
            'payment_id' => $paymentMethod->id,
        ], $user);

        $cashbox->refresh();
        expect((float) $cashbox->balance)->toEqual(950.00);

        // Actualizamos el gasto a 70.00 (debe descontar 20 adicionales)
        $response = $this->actingAs($user)->put(route('expenses.update', $expense), [
            'date' => now()->toDateString(),
            'concept' => 'Compra de hojas bond y tinta',
            'amount' => 70.00,
            'expense_category_id' => $cat->id,
            'cashbox_id' => $cashbox->id,
            'payment_id' => $paymentMethod->id,
        ]);

        $response->assertRedirect(route('expenses.show', $expense));
        $cashbox->refresh();
        expect((float) $cashbox->balance)->toEqual(930.00);
    });

    it('anula un gasto devolviendo el dinero a la caja', function () {
        $user = userWith('ver expenses', 'crear expenses', 'eliminar expenses');
        $branch = testBranch();
        $cashbox = Cashbox::factory()->create([
            'branche_id' => $branch->id,
            'balance' => 500.00,
            'cash' => 500.00,
        ]);
        $paymentMethod = PaymentMethod::factory()->cash()->create();
        $cat = ExpenseCategory::create(['name' => 'Transporte']);

        $service = app(\App\Services\ExpenseService::class);
        $expense = $service->register([
            'date' => now()->toDateString(),
            'concept' => 'Flete de encomienda',
            'amount' => 100.00,
            'expense_category_id' => $cat->id,
            'cashbox_id' => $cashbox->id,
            'payment_id' => $paymentMethod->id,
        ], $user);

        $cashbox->refresh();
        expect((float) $cashbox->balance)->toEqual(400.00);

        // Anulación vía destroy
        $response = $this->actingAs($user)->delete(route('expenses.destroy', $expense));
        $response->assertRedirect(route('expenses.index'));

        $expense->refresh();
        expect($expense->status)->toBe(Expense::STATUS_CANCELED);

        $cashbox->refresh();
        expect((float) $cashbox->balance)->toEqual(500.00);
    });
});

describe('Categorías de Gasto', function () {
    it('permite gestionar categorías y ofrece alta rápida JSON', function () {
        $user = userWith('ver expense-categories', 'crear expense-categories');

        // Alta web regular
        $this->actingAs($user)->post(route('expense-categories.store'), [
            'name' => 'Alquiler',
            'is_active' => true,
        ])->assertRedirect(route('expense-categories.index'));

        expect(ExpenseCategory::where('name', 'Alquiler')->exists())->toBeTrue();

        // Alta vía JSON para selector modal
        $jsonResponse = $this->actingAs($user)
            ->postJson(route('expense-categories.store'), [
                'name' => 'Publicidad y Marketing',
                'is_active' => true,
            ]);

        $jsonResponse->assertStatus(201)
            ->assertJsonPath('name', 'Publicidad y Marketing');
    });

    it('impide eliminar una categoría que contiene gastos asociados', function () {
        $user = userWith('ver expense-categories', 'eliminar expense-categories', 'ver expenses');
        $branch = testBranch();
        $cat = ExpenseCategory::create(['name' => 'Impuestos']);

        Expense::create([
            'date' => now()->toDateString(),
            'concept' => 'Pago mensual tributario',
            'amount' => 300,
            'status' => Expense::STATUS_PAID,
            'expense_category_id' => $cat->id,
            'branche_id' => $branch->id,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->delete(route('expense-categories.destroy', $cat));
        $response->assertRedirect(route('expense-categories.index'));
        $response->assertSessionHas('error');

        expect(ExpenseCategory::where('id', $cat->id)->exists())->toBeTrue();
    });
});

describe('PDF y Botón Superior Derecho en Gastos', function () {
    it('genera el PDF del gasto y ubica el botón de impresión en la parte superior derecha', function () {
        $user = userWith('ver expenses', 'gastos.exportar');
        $branch = testBranch();
        $cat = ExpenseCategory::create(['name' => 'Servicios']);

        $expense = Expense::create([
            'date' => now()->toDateString(),
            'concept' => 'Energía Eléctrica',
            'amount' => 150.00,
            'status' => Expense::STATUS_PAID,
            'expense_category_id' => $cat->id,
            'branche_id' => $branch->id,
            'user_id' => $user->id,
        ]);

        // Verificación de la vista show con botón superior derecho
        $responseShow = $this->actingAs($user)->get(route('expenses.show', $expense));
        $responseShow->assertOk();
        $responseShow->assertSee(route('expenses.pdf', $expense));

        // Descarga de PDF
        $responsePdf = $this->actingAs($user)->get(route('expenses.pdf', $expense));
        $responsePdf->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    });
});
