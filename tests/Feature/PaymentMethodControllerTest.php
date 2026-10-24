<?php

use App\Models\PaymentMethod;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| PaymentMethodController: CRUD, fotos QR, validaciones y permisos
|--------------------------------------------------------------------------
*/

/** Datos válidos de un método de pago. */
function paymentMethodPayload(array $overrides = []): array
{
    return $overrides + [
        'name' => 'Transferencia Bancaria BNB',
        'number' => '150-12345678',
        'is_cash' => '0',
        'note' => 'Enviar comprobante al WhatsApp',
    ];
}

/** Casos inválidos para store y update. */
dataset('invalid payment method fields', [
    'sin nombre' => ['name', ''],
    'nombre de más de 255 caracteres' => ['name', str_repeat('a', 256)],
    'número de cuenta de más de 100 caracteres' => ['number', str_repeat('1', 101)],
    'nota de más de 1000 caracteres' => ['note', str_repeat('n', 1001)],
    'is_cash no booleano' => ['is_cash', 'invalido'],
]);

// ─────────────────────────────────────────────────────────────────────────────
// Autenticación y permisos
// ─────────────────────────────────────────────────────────────────────────────

describe('acceso', function () {
    it('redirige al login a un visitante', function () {
        $this->get(route('payments.index'))->assertRedirect(route('login'));
    });

    it('rechaza (403) a un usuario sin permisos en todas las acciones', function () {
        $this->actingAs(userWith());
        $method = PaymentMethod::factory()->create();

        $this->get(route('payments.index'))->assertForbidden();
        $this->get(route('payments.show', $method))->assertForbidden();
        $this->get(route('payments.edit', $method))->assertForbidden();
        $this->post(route('payments.store'), paymentMethodPayload())->assertForbidden();
        $this->put(route('payments.update', $method), paymentMethodPayload())->assertForbidden();
        $this->delete(route('payments.destroy', $method))->assertForbidden();
    });

    it('aplica el permiso específico de cada acción (la policy) aunque pueda ver métodos de pago', function () {
        $this->actingAs(userWith('ver payments'));
        $method = PaymentMethod::factory()->create();

        $this->get(route('payments.index'))->assertOk();
        $this->get(route('payments.show', $method))->assertOk();
        $this->get(route('payments.edit', $method))->assertForbidden();
        $this->post(route('payments.store'), paymentMethodPayload())->assertForbidden();
        $this->put(route('payments.update', $method), paymentMethodPayload())->assertForbidden();
        $this->delete(route('payments.destroy', $method))->assertForbidden();

        expect(PaymentMethod::count())->toBe(1);
    });

    it('deja pasar todo al permiso "admin" (bypass de las policies)', function () {
        $this->actingAs(userWith('ver payments', 'admin'));

        $this->post(route('payments.store'), paymentMethodPayload(['name' => 'Pago QR Admin']))
            ->assertRedirect(route('payments.index'));

        $method = PaymentMethod::where('name', 'Pago QR Admin')->firstOrFail();
        $this->put(route('payments.update', $method), paymentMethodPayload(['name' => 'Pago QR Editado']))
            ->assertRedirect();
        $this->delete(route('payments.destroy', $method))->assertRedirect();

        expect(PaymentMethod::where('name', 'Pago QR Editado')->exists())->toBeFalse();
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// index
// ─────────────────────────────────────────────────────────────────────────────

describe('index', function () {
    beforeEach(fn () => $this->actingAs(userWith('ver payments')));

    it('lista los métodos de pago ordenados por nombre y pagina de a 10', function () {
        foreach (range(1, 12) as $i) {
            PaymentMethod::factory()->create(['name' => sprintf('Método %02d', $i)]);
        }

        $response = $this->get(route('payments.index'))->assertOk()->assertViewIs('payments.index');

        $methods = $response->viewData('paymentMethods');

        expect($methods->total())->toBe(12)
            ->and($methods->perPage())->toBe(10)
            ->and($methods->first()->name)->toBe('Método 01');
    });

    it('busca por nombre (filter[buscar])', function () {
        PaymentMethod::factory()->create(['name' => 'Tigo Money QR']);
        PaymentMethod::factory()->create(['name' => 'Efectivo Caja']);

        $names = $this->get(route('payments.index', ['filter' => ['buscar' => 'Tigo']]))
            ->viewData('paymentMethods')->pluck('name')->all();

        expect($names)->toBe(['Tigo Money QR']);
    });

    it('ordena por campos permitidos y rechaza con 400 los no permitidos', function () {
        $this->get(route('payments.index', ['sort' => 'name']))->assertOk();
        $this->get(route('payments.index', ['sort' => '-id']))->assertOk();
        $this->get(route('payments.index', ['sort' => 'number']))->assertStatus(400);
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// store
// ─────────────────────────────────────────────────────────────────────────────

describe('store', function () {
    beforeEach(fn () => $this->actingAs(userWith('ver payments', 'crear payments')));

    it('crea el método de pago, redirige al listado y avisa', function () {
        $this->post(route('payments.store'), paymentMethodPayload())
            ->assertRedirect(route('payments.index'))
            ->assertSessionHas('success', 'Método de pago registrado exitosamente.');

        $this->assertDatabaseHas('payments', [
            'name' => 'Transferencia Bancaria BNB',
            'number' => '150-12345678',
            'is_cash' => false,
            'note' => 'Enviar comprobante al WhatsApp',
        ]);
    });

    it('convierte correctamente is_cash a booleano', function () {
        $this->post(route('payments.store'), ['name' => 'Efectivo', 'is_cash' => '1'])
            ->assertSessionHasNoErrors();

        $this->post(route('payments.store'), ['name' => 'Tarjeta'])
            ->assertSessionHasNoErrors();

        expect(PaymentMethod::where('name', 'Efectivo')->value('is_cash'))->toBeTrue()
            ->and(PaymentMethod::where('name', 'Tarjeta')->value('is_cash'))->toBeFalse();
    });

    it('sube y guarda la foto o código QR en el disco uploads', function () {
        Storage::fake('uploads');

        $photo = UploadedFile::fake()->image('qr-bnb.png', 300, 300);

        $this->post(route('payments.store'), paymentMethodPayload(['photo' => $photo]))
            ->assertRedirect(route('payments.index'))
            ->assertSessionHasNoErrors();

        $method = PaymentMethod::where('name', 'Transferencia Bancaria BNB')->firstOrFail();

        expect($method->photo)->not->toBeNull();
        Storage::disk('uploads')->assertExists($method->photo);
    });

    it('rechaza archivos que no sean imágenes válidas', function () {
        Storage::fake('uploads');

        $fakeFile = UploadedFile::fake()->create('documento.pdf', 500, 'application/pdf');

        $this->from(route('payments.index'))
            ->post(route('payments.store'), paymentMethodPayload(['photo' => $fakeFile]))
            ->assertRedirect(route('payments.index'))
            ->assertSessionHasErrors(['photo' => 'El archivo debe ser una imagen.']);
    });

    it('valida los campos y no crea nada', function (string $field, mixed $invalidValue) {
        $this->from(route('payments.index'))
            ->post(route('payments.store'), paymentMethodPayload([$field => $invalidValue]))
            ->assertRedirect(route('payments.index'))
            ->assertSessionHasErrors($field);

        expect(PaymentMethod::count())->toBe(0);
    })->with('invalid payment method fields');
});

// ─────────────────────────────────────────────────────────────────────────────
// show y edit
// ─────────────────────────────────────────────────────────────────────────────

describe('show y edit', function () {
    it('muestra el detalle del método de pago', function () {
        $this->actingAs(userWith('ver payments'));
        $method = PaymentMethod::factory()->create(['name' => 'QR Simple']);

        $this->get(route('payments.show', $method))
            ->assertOk()
            ->assertViewIs('payments.show')
            ->assertViewHas('paymentMethod', fn ($p) => $p->id === $method->id);
    });

    it('muestra el formulario de edición con los datos actuales', function () {
        $this->actingAs(userWith('ver payments', 'editar payments'));
        $method = PaymentMethod::factory()->create(['name' => 'QR Editar']);

        $this->get(route('payments.edit', $method))
            ->assertOk()
            ->assertViewIs('payments.edit')
            ->assertViewHas('paymentMethod', fn ($p) => $p->name === 'QR Editar');
    });

    it('responde 404 si el método de pago no existe', function () {
        $this->actingAs(userWith('ver payments', 'editar payments'));
        $this->get(route('payments.show', 99999))->assertNotFound();
        $this->get(route('payments.edit', 99999))->assertNotFound();
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// update
// ─────────────────────────────────────────────────────────────────────────────

describe('update', function () {
    beforeEach(fn () => $this->actingAs(userWith('ver payments', 'editar payments')));

    it('actualiza los datos, redirige al listado y avisa', function () {
        $method = PaymentMethod::factory()->create(['name' => 'Nombre Antiguo']);

        $this->put(route('payments.update', $method), [
            'name' => 'Nombre Actualizado',
            'number' => '9999-0000',
            'is_cash' => '0',
            'note' => 'Nota editada',
        ])
            ->assertRedirect(route('payments.index'))
            ->assertSessionHas('success', 'Método de pago actualizado exitosamente.');

        $method->refresh();
        expect($method->name)->toBe('Nombre Actualizado')
            ->and($method->number)->toBe('9999-0000');
    });

    it('reemplaza la foto QR y borra la anterior del disco', function () {
        Storage::fake('uploads');

        $oldPhoto = UploadedFile::fake()->image('old-qr.jpg');
        $oldPath = $oldPhoto->store('payment-methods', 'uploads');

        $method = PaymentMethod::factory()->create(['photo' => $oldPath]);
        Storage::disk('uploads')->assertExists($oldPath);

        $newPhoto = UploadedFile::fake()->image('new-qr.png');

        $this->put(route('payments.update', $method), paymentMethodPayload(['photo' => $newPhoto]))
            ->assertRedirect(route('payments.index'))
            ->assertSessionHasNoErrors();

        $method->refresh();

        expect($method->photo)->not->toBe($oldPath);
        Storage::disk('uploads')->assertMissing($oldPath);
        Storage::disk('uploads')->assertExists($method->photo);
    });

    it('valida los campos y no modifica el método de pago', function (string $field, mixed $invalidValue) {
        $method = PaymentMethod::factory()->create(['name' => 'Original']);

        $this->from(route('payments.edit', $method))
            ->put(route('payments.update', $method), paymentMethodPayload([$field => $invalidValue]))
            ->assertRedirect(route('payments.edit', $method))
            ->assertSessionHasErrors($field);

        expect($method->refresh()->name)->toBe('Original');
    })->with('invalid payment method fields');
});

// ─────────────────────────────────────────────────────────────────────────────
// destroy
// ─────────────────────────────────────────────────────────────────────────────

describe('destroy', function () {
    beforeEach(fn () => $this->actingAs(userWith('ver payments', 'eliminar payments')));

    it('elimina un método de pago y borra su foto asociada del disco', function () {
        Storage::fake('uploads');

        $photo = UploadedFile::fake()->image('qr.png');
        $path = $photo->store('payment-methods', 'uploads');
        $method = PaymentMethod::factory()->create(['photo' => $path]);

        $this->delete(route('payments.destroy', $method))
            ->assertRedirect(route('payments.index'))
            ->assertSessionHas('success', 'Método de pago eliminado exitosamente.');

        expect(PaymentMethod::count())->toBe(0);
        Storage::disk('uploads')->assertMissing($path);
    });

    it('responde 404 si el método de pago no existe', function () {
        $this->delete(route('payments.destroy', 99999))->assertNotFound();
    });
});
