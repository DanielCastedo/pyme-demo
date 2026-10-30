<?php

use App\Models\Branch;
use App\Models\Cashbox;
use App\Models\PaymentMethod;
use App\Models\Transaction;
use App\Models\Type;
use App\Services\TransactionService;

/*
|--------------------------------------------------------------------------
| CashboxController: CRUD, validaciones, restricciones de negocio y permisos
|--------------------------------------------------------------------------
*/

/** Datos válidos de una caja; cada prueba sobrescribe solo lo que le interesa. */
function cashboxPayload(Branch $branch, array $overrides = []): array
{
    return $overrides + [
        'name' => 'Caja Principal Ventas',
        'branche_id' => $branch->id,
    ];
}

/** Casos inválidos para store y update. */
dataset('invalid cashbox fields', [
    'sin nombre' => ['name', ''],
    'nombre de más de 255 caracteres' => ['name', str_repeat('a', 256)],
    'sin sucursal' => ['branche_id', ''],
    'sucursal inexistente' => ['branche_id', 99999],
]);

// ─────────────────────────────────────────────────────────────────────────────
// Autenticación y permisos
// ─────────────────────────────────────────────────────────────────────────────

describe('acceso', function () {
    it('redirige al login a un visitante', function () {
        $this->get(route('cashboxes.index'))->assertRedirect(route('login'));
    });

    it('rechaza (403) a un usuario sin permisos en todas las acciones', function () {
        $this->actingAs(userWith());
        $branch = testBranch();
        $cashbox = Cashbox::factory()->create(['branche_id' => $branch->id]);

        $this->get(route('cashboxes.index'))->assertForbidden();
        $this->get(route('cashboxes.show', $cashbox))->assertForbidden();
        $this->get(route('cashboxes.edit', $cashbox))->assertForbidden();
        $this->post(route('cashboxes.store'), cashboxPayload($branch))->assertForbidden();
        $this->put(route('cashboxes.update', $cashbox), cashboxPayload($branch))->assertForbidden();
        $this->delete(route('cashboxes.destroy', $cashbox))->assertForbidden();
    });

    it('aplica el permiso específico de cada acción (la policy) aunque pueda ver cajas', function () {
        $this->actingAs(userWith('ver cashboxes'));
        $branch = testBranch();
        $cashbox = Cashbox::factory()->create(['branche_id' => $branch->id]);

        $this->get(route('cashboxes.index'))->assertOk();
        $this->get(route('cashboxes.show', $cashbox))->assertOk();
        $this->get(route('cashboxes.edit', $cashbox))->assertForbidden();
        $this->post(route('cashboxes.store'), cashboxPayload($branch))->assertForbidden();
        $this->put(route('cashboxes.update', $cashbox), cashboxPayload($branch))->assertForbidden();
        $this->delete(route('cashboxes.destroy', $cashbox))->assertForbidden();

        expect(Cashbox::where('id', $cashbox->id)->exists())->toBeTrue();
    });

    it('deja pasar todo al permiso "admin" (bypass de las policies)', function () {
        $this->actingAs(userWith('ver cashboxes', 'admin'));
        $branch = testBranch();

        $this->post(route('cashboxes.store'), cashboxPayload($branch, ['name' => 'Caja Admin 1']))
            ->assertRedirect(route('cashboxes.index'));

        $cashbox = Cashbox::where('name', 'Caja Admin 1')->firstOrFail();
        $this->put(route('cashboxes.update', $cashbox), cashboxPayload($branch, ['name' => 'Caja Admin Modificada']))
            ->assertRedirect();
        $this->delete(route('cashboxes.destroy', $cashbox))->assertRedirect();

        expect(Cashbox::where('name', 'Caja Admin Modificada')->exists())->toBeFalse();
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// index
// ─────────────────────────────────────────────────────────────────────────────

describe('index', function () {
    beforeEach(fn () => $this->actingAs(userWith('ver cashboxes')));

    it('lista las cajas ordenadas por nombre y pagina de a 10', function () {
        $branch = testBranch();
        foreach (range(1, 12) as $i) {
            Cashbox::factory()->create([
                'branche_id' => $branch->id,
                'name' => sprintf('Caja %02d', $i),
            ]);
        }

        $response = $this->get(route('cashboxes.index'))->assertOk()->assertViewIs('cashboxes.index');

        $cashboxes = $response->viewData('cashboxes');

        expect($cashboxes->total())->toBeGreaterThanOrEqual(12)
            ->and($cashboxes->perPage())->toBe(10);
    });

    it('busca por nombre de caja o nombre de sucursal (filter[buscar])', function (string $term, string $expected) {
        $branchA = Branch::factory()->create(['name' => 'Sucursal Pampa']);
        $branchB = Branch::factory()->create(['name' => 'Sucursal Chaco']);

        Cashbox::factory()->create(['branche_id' => $branchA->id, 'name' => 'Caja Mostrador']);
        Cashbox::factory()->create(['branche_id' => $branchB->id, 'name' => 'Caja Garaje']);

        $names = $this->get(route('cashboxes.index', ['filter' => ['buscar' => $term]]))
            ->viewData('cashboxes')->pluck('name')->all();

        expect($names)->toBe([$expected]);
    })->with([
        'por nombre de caja' => ['Mostrador', 'Caja Mostrador'],
        'por nombre de sucursal' => ['Chaco', 'Caja Garaje'],
    ]);

    it('filtra por sucursal específica (filter[currentBranch])', function () {
        $b1 = Branch::factory()->create();
        $b2 = Branch::factory()->create();

        Cashbox::factory()->create(['branche_id' => $b1->id, 'name' => 'Caja B1']);
        Cashbox::factory()->create(['branche_id' => $b2->id, 'name' => 'Caja B2']);

        $results = $this->get(route('cashboxes.index', ['filter' => ['currentBranch' => $b1->id]]))
            ->viewData('cashboxes')->pluck('name')->all();

        expect($results)->toContain('Caja B1')
            ->and($results)->not->toContain('Caja B2');
    });

    it('muestra el total cobrado por QR de cada caja (entradas - salidas, sin las anuladas)', function () {
        $branch = testBranch();
        $cashbox = Cashbox::factory()->create(['branche_id' => $branch->id, 'name' => 'Caja QR']);
        $other = Cashbox::factory()->create(['branche_id' => $branch->id, 'name' => 'Caja Sin QR']);
        $qr = PaymentMethod::factory()->create(['name' => 'QR Simple', 'is_cash' => false]);
        $cash = PaymentMethod::factory()->create(['name' => 'Efectivo', 'is_cash' => true]);
        $in = Type::factory()->create(['sign' => '+']);
        $out = Type::factory()->create(['sign' => '-']);
        $user = userWith();

        $register = fn (Cashbox $box, PaymentMethod $method, Type $type, $amount) => app(TransactionService::class)->register([
            'type_id' => $type->id, 'payment_id' => $method->id, 'cashbox_id' => $box->id, 'amount' => $amount, 'user_id' => $user->id,
        ]);

        $register($cashbox, $qr, $in, 150);
        $register($cashbox, $qr, $out, 30);
        app(TransactionService::class)->cancel($register($cashbox, $qr, $in, 500));
        $register($cashbox, $cash, $in, 80); // efectivo: no cuenta como QR
        $register($other, $cash, $in, 40);

        $rows = $this->get(route('cashboxes.index'))->assertOk()
            ->assertSee('120.00')
            ->viewData('cashboxes')->keyBy('name');

        expect($rows['Caja QR']->qr_label)->toBe('120.00')
            ->and($rows['Caja Sin QR']->qr_label)->toBe('0.00');
    });

    it('ordena por los campos permitidos y rechaza los no permitidos con 400', function () {
        $this->get(route('cashboxes.index', ['sort' => 'name']))->assertOk();
        $this->get(route('cashboxes.index', ['sort' => '-cash']))->assertOk();
        $this->get(route('cashboxes.index', ['sort' => 'created_at']))->assertStatus(400);
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// store
// ─────────────────────────────────────────────────────────────────────────────

describe('store', function () {
    beforeEach(fn () => $this->actingAs(userWith('ver cashboxes', 'crear cashboxes')));

    it('crea la caja, redirige al listado y avisa', function () {
        $branch = testBranch();

        $this->post(route('cashboxes.store'), cashboxPayload($branch))
            ->assertRedirect(route('cashboxes.index'))
            ->assertSessionHas('success', 'Caja registrada exitosamente.');

        $this->assertDatabaseHas('cashboxes', [
            'name' => 'Caja Principal Ventas',
            'branche_id' => $branch->id,
            'cash' => 0.00,
            'balance' => 0.00,
        ]);
    });

    it('no permite nombres repetidos dentro de la misma sucursal', function () {
        $branch = testBranch();
        Cashbox::factory()->create(['branche_id' => $branch->id, 'name' => 'Caja Uno']);

        $this->from(route('cashboxes.index'))
            ->post(route('cashboxes.store'), cashboxPayload($branch, ['name' => 'Caja Uno']))
            ->assertRedirect(route('cashboxes.index'))
            ->assertSessionHasErrors(['name' => 'Ya existe una caja con ese nombre en la sucursal seleccionada.']);
    });

    it('permite el mismo nombre de caja en sucursales distintas', function () {
        $b1 = Branch::factory()->create();
        $b2 = Branch::factory()->create();

        Cashbox::factory()->create(['branche_id' => $b1->id, 'name' => 'Caja Recepción']);

        $this->post(route('cashboxes.store'), ['branche_id' => $b2->id, 'name' => 'Caja Recepción'])
            ->assertRedirect(route('cashboxes.index'))
            ->assertSessionHasNoErrors();

        expect(Cashbox::where('name', 'Caja Recepción')->count())->toBe(2);
    });

    it('valida los campos y no crea nada', function (string $field, mixed $invalidValue) {
        $branch = testBranch();
        $initialCount = Cashbox::count();

        $this->from(route('cashboxes.index'))
            ->post(route('cashboxes.store'), cashboxPayload($branch, [$field => $invalidValue]))
            ->assertRedirect(route('cashboxes.index'))
            ->assertSessionHasErrors($field);

        expect(Cashbox::count())->toBe($initialCount);
    })->with('invalid cashbox fields');
});

// ─────────────────────────────────────────────────────────────────────────────
// show y edit
// ─────────────────────────────────────────────────────────────────────────────

describe('show y edit', function () {
    it('muestra el detalle de la caja y valida filtros de fecha', function () {
        $this->actingAs(userWith('ver cashboxes'));
        $branch = testBranch();
        $cashbox = Cashbox::factory()->create(['branche_id' => $branch->id]);

        $this->get(route('cashboxes.show', $cashbox))
            ->assertOk()
            ->assertViewIs('cashboxes.show')
            ->assertViewHas('cashbox')
            ->assertViewHas('totals');

        // to anterior a from debe fallar la validación
        $this->get(route('cashboxes.show', [$cashbox, 'from' => '2026-05-10', 'to' => '2026-05-01']))
            ->assertSessionHasErrors('to');
    });

    it('muestra el formulario de edición con la lista de sucursales', function () {
        $this->actingAs(userWith('ver cashboxes', 'editar cashboxes'));
        $branch = testBranch();
        $cashbox = Cashbox::factory()->create(['branche_id' => $branch->id]);

        $this->get(route('cashboxes.edit', $cashbox))
            ->assertOk()
            ->assertViewIs('cashboxes.edit')
            ->assertViewHas('cashbox')
            ->assertViewHas('branches');
    });

    it('responde 404 si la caja no existe', function () {
        $this->actingAs(userWith('ver cashboxes', 'editar cashboxes'));
        $this->get(route('cashboxes.show', 99999))->assertNotFound();
        $this->get(route('cashboxes.edit', 99999))->assertNotFound();
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// update
// ─────────────────────────────────────────────────────────────────────────────

describe('update', function () {
    beforeEach(fn () => $this->actingAs(userWith('ver cashboxes', 'editar cashboxes')));

    it('actualiza los datos, redirige al listado y avisa', function () {
        $branch = testBranch();
        $cashbox = Cashbox::factory()->create(['branche_id' => $branch->id, 'name' => 'Nombre Viejo']);

        $this->put(route('cashboxes.update', $cashbox), [
            'branche_id' => $branch->id,
            'name' => 'Nombre Nuevo',
        ])
            ->assertRedirect(route('cashboxes.index'))
            ->assertSessionHas('success', 'Caja actualizada exitosamente.');

        expect($cashbox->refresh()->name)->toBe('Nombre Nuevo');
    });

    it('bloquea cambiar de sucursal a una caja que ya tiene transacciones registradas', function () {
        $b1 = Branch::factory()->create();
        $b2 = Branch::factory()->create();
        $cashbox = Cashbox::factory()->create(['branche_id' => $b1->id]);

        $type = Type::create(['name' => 'Ingreso', 'code' => 'ING', 'is_active' => true]);

        Transaction::create([
            'number' => 'TRX-001',
            'date' => now(),
            'amount' => 100,
            'canceled' => false,
            'type_id' => $type->id,
            'cashbox_id' => $cashbox->id,
            'branche_id' => $b1->id,
            'user_id' => auth()->id(),
        ]);

        $this->from(route('cashboxes.edit', $cashbox))
            ->put(route('cashboxes.update', $cashbox), [
                'branche_id' => $b2->id,
                'name' => $cashbox->name,
            ])
            ->assertRedirect(route('cashboxes.edit', $cashbox))
            ->assertSessionHas('error', 'No se puede cambiar la sucursal de una caja que ya tiene transacciones.');

        expect($cashbox->refresh()->branche_id)->toBe($b1->id);
    });

    it('valida los campos y no modifica la caja', function (string $field, mixed $invalidValue) {
        $branch = testBranch();
        $cashbox = Cashbox::factory()->create(['branche_id' => $branch->id, 'name' => 'Original']);

        $this->from(route('cashboxes.edit', $cashbox))
            ->put(route('cashboxes.update', $cashbox), cashboxPayload($branch, [$field => $invalidValue]))
            ->assertRedirect(route('cashboxes.edit', $cashbox))
            ->assertSessionHasErrors($field);

        expect($cashbox->refresh()->name)->toBe('Original');
    })->with('invalid cashbox fields');
});

// ─────────────────────────────────────────────────────────────────────────────
// destroy
// ─────────────────────────────────────────────────────────────────────────────

describe('destroy', function () {
    beforeEach(fn () => $this->actingAs(userWith('ver cashboxes', 'eliminar cashboxes')));

    it('elimina una caja vacía y sin transacciones', function () {
        $branch = testBranch();
        $cashbox = Cashbox::factory()->create(['branche_id' => $branch->id, 'cash' => 0, 'balance' => 0]);

        $this->delete(route('cashboxes.destroy', $cashbox))
            ->assertRedirect(route('cashboxes.index'))
            ->assertSessionHas('success', 'Caja eliminada exitosamente.');

        expect(Cashbox::where('id', $cashbox->id)->exists())->toBeFalse();
    });

    it('no elimina una caja si todavía tiene dinero en efectivo o saldo', function () {
        $branch = testBranch();
        $cashbox = Cashbox::factory()->create(['branche_id' => $branch->id, 'cash' => 500, 'balance' => 0]);

        $this->delete(route('cashboxes.destroy', $cashbox))
            ->assertRedirect(route('cashboxes.index'))
            ->assertSessionHas('error', 'No se puede eliminar la caja: todavía tiene dinero.');

        expect(Cashbox::where('id', $cashbox->id)->exists())->toBeTrue();
    });

    it('no elimina una caja con transacciones registradas', function () {
        $branch = testBranch();
        $cashbox = Cashbox::factory()->create(['branche_id' => $branch->id, 'cash' => 0, 'balance' => 0]);
        $type = Type::create(['name' => 'Venta', 'code' => 'VNT', 'is_active' => true]);

        Transaction::create([
            'number' => 'TRX-999',
            'date' => now(),
            'amount' => 50,
            'canceled' => false,
            'type_id' => $type->id,
            'cashbox_id' => $cashbox->id,
            'branche_id' => $branch->id,
            'user_id' => auth()->id(),
        ]);

        $this->delete(route('cashboxes.destroy', $cashbox))
            ->assertRedirect(route('cashboxes.index'))
            ->assertSessionHas('error', 'No se puede eliminar la caja: tiene transacciones registradas.');

        expect(Cashbox::where('id', $cashbox->id)->exists())->toBeTrue();
    });

    it('responde 404 si la caja no existe', function () {
        $this->delete(route('cashboxes.destroy', 99999))->assertNotFound();
    });
});
