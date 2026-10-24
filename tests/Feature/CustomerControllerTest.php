<?php

use App\Models\Customer;

/*
|--------------------------------------------------------------------------
| CustomerController: CRUD, validaciones, permisos y reglas de borrado
|--------------------------------------------------------------------------
*/

/** Datos válidos de un cliente; cada prueba sobrescribe solo lo que le interesa. */
function customerPayload(array $overrides = []): array
{
    return $overrides + [
        'name' => 'Constructora Andina S.R.L.',
        'document_type' => 'nit',
        'document' => '1023456029',
        'phone' => '33221100',
        'email' => 'compras@andina.test',
        'address' => 'Av. San Martín 123',
        'city' => 'Santa Cruz',
        'credit_limit' => '5000',
    ];
}

/** Casos inválidos compartidos por store y update: [campo, valor inválido]. */
dataset('invalid customer fields', [
    'sin nombre' => ['name', ''],
    'nombre de más de 255 caracteres' => ['name', str_repeat('a', 256)],
    'tipo de documento desconocido' => ['document_type', 'pasaporte'],
    'documento de más de 50 caracteres' => ['document', str_repeat('9', 51)],
    'teléfono de más de 50 caracteres' => ['phone', str_repeat('7', 51)],
    'correo inválido' => ['email', 'no-es-un-correo'],
    'dirección de más de 255 caracteres' => ['address', str_repeat('a', 256)],
    'ciudad de más de 100 caracteres' => ['city', str_repeat('a', 101)],
    'límite de crédito negativo' => ['credit_limit', '-1'],
    'límite de crédito no numérico' => ['credit_limit', 'mucho'],
    'is_active no booleano' => ['is_active', 'quizas'],
]);

// ─────────────────────────────────────────────────────────────────────────────
// Autenticación y permisos
// ─────────────────────────────────────────────────────────────────────────────

describe('acceso', function () {
    it('redirige al login a un visitante', function () {
        $this->get(route('customers.index'))->assertRedirect(route('login'));
    });

    it('rechaza (403) a un usuario sin permisos en todas las acciones', function () {
        $this->actingAs(userWith());
        $customer = Customer::factory()->create();

        // Sin "ver customers" el grupo de rutas completo queda bloqueado por el middleware de permisos.
        $this->get(route('customers.index'))->assertForbidden();
        $this->get(route('customers.show', $customer))->assertForbidden();
        $this->get(route('customers.edit', $customer))->assertForbidden();
        $this->post(route('customers.store'), customerPayload())->assertForbidden();
        $this->put(route('customers.update', $customer), customerPayload())->assertForbidden();
        $this->delete(route('customers.destroy', $customer))->assertForbidden();
    });

    it('aplica el permiso específico de cada acción (la policy) aunque pueda ver clientes', function () {
        $this->actingAs(userWith('ver customers'));
        $customer = Customer::factory()->create();

        $this->get(route('customers.index'))->assertOk();
        $this->get(route('customers.show', $customer))->assertOk();
        $this->post(route('customers.store'), customerPayload())->assertForbidden();
        $this->get(route('customers.edit', $customer))->assertForbidden();
        $this->put(route('customers.update', $customer), customerPayload())->assertForbidden();
        $this->delete(route('customers.destroy', $customer))->assertForbidden();

        expect(Customer::count())->toBe(1);
    });

    it('deja pasar todo al permiso "admin" (bypass de las policies)', function () {
        $this->actingAs(userWith('ver customers', 'admin'));

        $this->post(route('customers.store'), customerPayload())->assertRedirect(route('customers.index'));

        $customer = Customer::firstOrFail();
        $this->put(route('customers.update', $customer), customerPayload(['name' => 'Editado por admin']))->assertRedirect();
        $this->delete(route('customers.destroy', $customer))->assertRedirect();

        expect(Customer::count())->toBe(0);
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// index
// ─────────────────────────────────────────────────────────────────────────────

describe('index', function () {
    beforeEach(fn () => $this->actingAs(userWith('ver customers')));

    it('lista los clientes ordenados por nombre y pagina de a 10', function () {
        foreach (range(1, 12) as $i) {
            Customer::factory()->create(['name' => sprintf('Cliente %02d', $i)]);
        }

        $response = $this->get(route('customers.index'))->assertOk()->assertViewIs('customers.index');

        $customers = $response->viewData('customers');

        expect($customers->total())->toBe(12)
            ->and($customers->count())->toBe(10)
            ->and($customers->perPage())->toBe(10)
            ->and($customers->first()->name)->toBe('Cliente 01')
            ->and($customers->last()->name)->toBe('Cliente 10');

        $second = $this->get(route('customers.index', ['page' => 2]))->viewData('customers');
        expect($second->pluck('name')->all())->toBe(['Cliente 11', 'Cliente 12']);
    });

    it('muestra el mensaje vacío cuando no hay clientes', function () {
        $this->get(route('customers.index'))
            ->assertOk()
            ->assertSee('No hay clientes registrados.');
    });

    it('incluye la cantidad de proformas de cada cliente', function () {
        $withQuotes = Customer::factory()->create(['name' => 'Con proformas']);
        Customer::factory()->create(['name' => 'Sin proformas']);
        quotationFor($withQuotes, 'COT-0001');
        quotationFor($withQuotes, 'COT-0002');

        $customers = $this->get(route('customers.index'))->viewData('customers')->keyBy('name');

        expect($customers['Con proformas']->quotations_count)->toBe(2)
            ->and($customers['Sin proformas']->quotations_count)->toBe(0);
    });

    it('busca por nombre, documento, teléfono o correo (filter[buscar])', function (string $term, string $expected) {
        Customer::factory()->create(['name' => 'Ferretería El Oriente', 'document' => '204918230', 'phone' => '33445566', 'email' => 'contacto@eloriente.test']);
        Customer::factory()->create(['name' => 'María Fernández', 'document' => '7788990', 'phone' => '76543210', 'email' => 'maria@correo.test']);
        Customer::factory()->create(['name' => 'Juan Pérez', 'document' => '6543211', 'phone' => '70012345', 'email' => 'juan@otro.test']);

        $names = $this->get(route('customers.index', ['filter' => ['buscar' => $term]]))
            ->viewData('customers')->pluck('name')->all();

        expect($names)->toBe([$expected]);
    })->with([
        'por nombre' => ['Oriente', 'Ferretería El Oriente'],
        'por documento' => ['7788990', 'María Fernández'],
        'por teléfono' => ['70012', 'Juan Pérez'],
        'por correo' => ['maria@correo', 'María Fernández'],
    ]);

    it('devuelve una lista vacía si la búsqueda no encuentra nada', function () {
        Customer::factory()->count(2)->create();

        $customers = $this->get(route('customers.index', ['filter' => ['buscar' => 'zzz-inexistente']]))->viewData('customers');

        expect($customers->total())->toBe(0);
    });

    it('filtra por estado (filter[currentStatus])', function () {
        Customer::factory()->create(['name' => 'Activo']);
        Customer::factory()->inactive()->create(['name' => 'Inactivo']);

        $inactive = $this->get(route('customers.index', ['filter' => ['currentStatus' => 0]]))->viewData('customers');
        $active = $this->get(route('customers.index', ['filter' => ['currentStatus' => 1]]))->viewData('customers');

        expect($inactive->pluck('name')->all())->toBe(['Inactivo'])
            ->and($active->pluck('name')->all())->toBe(['Activo']);
    });

    it('ordena por los campos permitidos y rechaza los demás con 400', function () {
        Customer::factory()->create(['name' => 'Ana', 'city' => 'Montero']);
        Customer::factory()->create(['name' => 'Beto', 'city' => 'Cochabamba']);

        $byNameDesc = $this->get(route('customers.index', ['sort' => '-name']))->viewData('customers')->pluck('name')->all();
        $byCity = $this->get(route('customers.index', ['sort' => 'city']))->viewData('customers')->pluck('name')->all();

        expect($byNameDesc)->toBe(['Beto', 'Ana'])
            ->and($byCity)->toBe(['Beto', 'Ana']);

        $this->get(route('customers.index', ['sort' => 'credit_limit']))->assertStatus(400);
    });

    it('conserva la búsqueda en los enlaces de paginación', function () {
        Customer::factory()->count(12)->create(['city' => 'Santa Cruz']);

        $paginator = $this->get(route('customers.index', ['filter' => ['buscar' => 'Santa']]))->viewData('customers');

        // buscar solo en nombre/documento/teléfono/correo: no coincide con la ciudad, así que 0 resultados
        expect($paginator->total())->toBe(0);

        Customer::factory()->count(12)->create(['name' => 'Cliente Norte']);

        $paginator = $this->get(route('customers.index', ['filter' => ['buscar' => 'Norte']]))->viewData('customers');

        expect($paginator->total())->toBe(12)
            ->and(urldecode($paginator->url(2)))->toContain('filter[buscar]=Norte');
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// store
// ─────────────────────────────────────────────────────────────────────────────

describe('store', function () {
    beforeEach(fn () => $this->actingAs(userWith('ver customers', 'crear customers')));

    it('crea el cliente, redirige al listado y avisa', function () {
        $this->post(route('customers.store'), customerPayload())
            ->assertRedirect(route('customers.index'))
            ->assertSessionHas('success', 'Cliente registrado exitosamente.');

        $this->assertDatabaseHas('customers', [
            'name' => 'Constructora Andina S.R.L.',
            'document_type' => 'nit',
            'document' => '1023456029',
            'phone' => '33221100',
            'email' => 'compras@andina.test',
            'city' => 'Santa Cruz',
            'credit_limit' => 5000,
            'is_active' => true,
        ]);
    });

    it('solo exige el nombre: todo lo demás es opcional', function () {
        $this->post(route('customers.store'), ['name' => 'Cliente mínimo'])
            ->assertRedirect(route('customers.index'))
            ->assertSessionHasNoErrors();

        $customer = Customer::firstOrFail();

        expect($customer->document)->toBeNull()
            ->and($customer->phone)->toBeNull()
            ->and($customer->email)->toBeNull()
            ->and($customer->credit_limit)->toBeNull();
    });

    it('usa CI como tipo de documento cuando no se envía (alta rápida de proformas)', function () {
        $this->post(route('customers.store'), ['name' => 'Sin tipo'])->assertSessionHasNoErrors();

        expect(Customer::firstOrFail()->document_type)->toBe('ci');
    });

    it('crea activo por defecto y respeta is_active=0', function () {
        $this->post(route('customers.store'), ['name' => 'Activo por defecto']);
        $this->post(route('customers.store'), ['name' => 'Creado inactivo', 'is_active' => 0]);

        expect(Customer::where('name', 'Activo por defecto')->value('is_active'))->toBeTrue()
            ->and(Customer::where('name', 'Creado inactivo')->value('is_active'))->toBeFalse();
    });

    it('guarda un límite de crédito vacío como sin límite (null)', function () {
        $this->post(route('customers.store'), ['name' => 'Sin límite', 'credit_limit' => '']);

        expect(Customer::firstOrFail()->credit_limit)->toBeNull();
    });

    it('acepta un límite de crédito de 0', function () {
        $this->post(route('customers.store'), ['name' => 'Límite cero', 'credit_limit' => 0])->assertSessionHasNoErrors();

        expect((float) Customer::firstOrFail()->credit_limit)->toBe(0.0);
    });

    it('no acepta un documento repetido', function () {
        Customer::factory()->create(['document' => '1023456029']);

        $this->post(route('customers.store'), customerPayload())
            ->assertSessionHasErrors(['document' => 'Ya existe un cliente registrado con ese documento.']);

        expect(Customer::count())->toBe(1);
    });

    it('permite varios clientes sin documento (el índice único ignora los nulos)', function () {
        $this->post(route('customers.store'), ['name' => 'Uno'])->assertSessionHasNoErrors();
        $this->post(route('customers.store'), ['name' => 'Dos'])->assertSessionHasNoErrors();

        expect(Customer::count())->toBe(2);
    });

    it('valida los campos y no crea nada', function (string $field, mixed $invalid) {
        $this->post(route('customers.store'), customerPayload([$field => $invalid]))
            ->assertSessionHasErrors($field);

        expect(Customer::count())->toBe(0);
    })->with('invalid customer fields');

    it('muestra el mensaje en español cuando falta el nombre', function () {
        $this->post(route('customers.store'), customerPayload(['name' => '']))
            ->assertSessionHasErrors(['name' => 'El nombre o razón social del cliente es obligatorio.']);
    });

    it('vuelve al formulario con lo escrito cuando hay errores (old input)', function () {
        $this->from(route('customers.index'))
            ->post(route('customers.store'), customerPayload(['email' => 'malo']))
            ->assertRedirect(route('customers.index'))
            ->assertSessionHasInput('name', 'Constructora Andina S.R.L.');
    });
});


// ─────────────────────────────────────────────────────────────────────────────
// store: alta rápida (JSON)
// ─────────────────────────────────────────────────────────────────────────────

describe('store: alta rápida (JSON, desde la pantalla de ventas)', function () {
    beforeEach(fn () => $this->actingAs(userWith('ver customers', 'crear customers')));

    it('responde 201 con los datos que necesita el selector (también de Proforma: teléfono, correo y dirección)', function () {
        $this->postJson(route('customers.store'), [
            'name' => 'Cliente rápido', 'document' => '555', 'document_type' => 'ci',
            'phone' => '700', 'email' => 'cliente@example.com', 'address' => 'Av. Siempre Viva',
        ])
            ->assertCreated()
            ->assertExactJson([
                'id' => Customer::firstOrFail()->id,
                'name' => 'Cliente rápido',
                'document' => '555',
                'document_type' => 'ci',
                'phone' => '700',
                'email' => 'cliente@example.com',
                'address' => 'Av. Siempre Viva',
            ]);
    });

    it('devuelve 422 con los errores por campo', function () {
        Customer::factory()->create(['document' => '555']);

        $this->postJson(route('customers.store'), ['name' => '', 'document' => '555'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'document']);
    });

    it('sigue exigiendo permiso de crear', function () {
        $this->actingAs(userWith('ver customers'));

        $this->postJson(route('customers.store'), ['name' => 'X'])->assertForbidden();
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// show
// ─────────────────────────────────────────────────────────────────────────────

describe('show', function () {
    beforeEach(fn () => $this->actingAs(userWith('ver customers')));

    it('muestra el detalle con la cantidad de órdenes', function () {
        $customer = Customer::factory()->create(['name' => 'Detalle Cliente']);
        orderFor($customer);
        orderFor($customer);

        $this->get(route('customers.show', $customer))
            ->assertOk()
            ->assertViewIs('customers.show')
            ->assertSee('Detalle Cliente')
            ->assertViewHas('customer', fn (Customer $c) => $c->orders_count === 2);
    });

    it('carga solo las 15 proformas más recientes', function () {
        $customer = Customer::factory()->create();

        // Cada una se creó un minuto después que la anterior (latest() ordena por created_at).
        foreach (range(1, 17) as $i) {
            quotationFor($customer, sprintf('COT-%04d', $i), ['created_at' => now()->subMinutes(100 - $i)]);
        }

        $loaded = $this->get(route('customers.show', $customer))->viewData('customer')->quotations;

        expect($loaded)->toHaveCount(15)
            ->and($loaded->first()->number)->toBe('COT-0017')
            ->and($loaded->last()->number)->toBe('COT-0003');
    });

    it('avisa cuando el cliente no tiene proformas', function () {
        $this->get(route('customers.show', Customer::factory()->create()))
            ->assertOk()
            ->assertSee('Este cliente no tiene proformas registradas aún.');
    });

    it('responde 404 si el cliente no existe', function () {
        $this->get(route('customers.show', 999))->assertNotFound();
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// edit
// ─────────────────────────────────────────────────────────────────────────────

describe('edit', function () {
    it('muestra el formulario con los datos actuales', function () {
        $this->actingAs(userWith('ver customers', 'editar customers'));
        $customer = Customer::factory()->create(['name' => 'Nombre Actual', 'city' => 'Montero']);

        $this->get(route('customers.edit', $customer))
            ->assertOk()
            ->assertViewIs('customers.edit')
            ->assertSee('Nombre Actual')
            ->assertSee('Montero');
    });

    it('responde 404 si el cliente no existe', function () {
        $this->actingAs(userWith('ver customers', 'editar customers'));

        $this->get(route('customers.edit', 999))->assertNotFound();
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// update
// ─────────────────────────────────────────────────────────────────────────────

describe('update', function () {
    beforeEach(function () {
        $this->actingAs(userWith('ver customers', 'editar customers'));
        $this->customer = Customer::factory()->create([
            'name' => 'Original', 'document_type' => 'ci', 'document' => '111', 'city' => 'Santa Cruz', 'is_active' => true,
        ]);
    });

    it('actualiza los datos, redirige al listado y avisa', function () {
        $this->put(route('customers.update', $this->customer), customerPayload(['name' => 'Actualizado', 'document' => '222']))
            ->assertRedirect(route('customers.index'))
            ->assertSessionHas('success', 'Cliente actualizado exitosamente.');

        $this->assertDatabaseHas('customers', [
            'id' => $this->customer->id,
            'name' => 'Actualizado',
            'document_type' => 'nit',
            'document' => '222',
            'credit_limit' => 5000,
        ]);
    });

    it('permite guardar sin cambiar el documento (la regla única ignora al propio cliente)', function () {
        $this->put(route('customers.update', $this->customer), customerPayload(['document' => '111']))
            ->assertSessionHasNoErrors();

        expect($this->customer->fresh()->document)->toBe('111');
    });

    it('no permite quedarse con el documento de otro cliente', function () {
        Customer::factory()->create(['document' => '999']);

        $this->put(route('customers.update', $this->customer), customerPayload(['document' => '999']))
            ->assertSessionHasErrors(['document' => 'Ya existe un cliente registrado con ese documento.']);

        expect($this->customer->fresh()->document)->toBe('111');
    });

    it('conserva el tipo de documento si el formulario no lo envía', function () {
        $this->put(route('customers.update', $this->customer), ['name' => 'Sin tipo', 'document' => '111'])
            ->assertSessionHasNoErrors();

        expect($this->customer->fresh()->document_type)->toBe('ci');
    });

    it('permite quitar el límite de crédito dejándolo vacío', function () {
        $this->customer->update(['credit_limit' => 800]);

        $this->put(route('customers.update', $this->customer), ['name' => 'Original', 'credit_limit' => ''])
            ->assertSessionHasNoErrors();

        expect($this->customer->fresh()->credit_limit)->toBeNull();
    });

    it('desactiva al cliente cuando llega is_active=0 y lo reactiva con is_active=1', function () {
        $this->put(route('customers.update', $this->customer), ['name' => 'Original', 'is_active' => 0]);
        expect($this->customer->fresh()->is_active)->toBeFalse();

        $this->put(route('customers.update', $this->customer), ['name' => 'Original', 'is_active' => 1]);
        expect($this->customer->fresh()->is_active)->toBeTrue();
    });

    it('desactiva al cliente al desmarcar "Cliente activo" en el formulario (no se envía ningún valor)', function () {
        // El controlador usa boolean('is_active', true): un checkbox desmarcado no manda el campo, así que hoy
        // el cliente sigue activo. Se deja registrado como pendiente hasta decidir la corrección.
        $this->put(route('customers.update', $this->customer), ['name' => 'Original']);

        expect($this->customer->fresh()->is_active)->toBeFalse();
    })->todo();

    it('valida los campos y no modifica al cliente', function (string $field, mixed $invalid) {
        $this->put(route('customers.update', $this->customer), customerPayload([$field => $invalid]))
            ->assertSessionHasErrors($field);

        expect($this->customer->fresh()->name)->toBe('Original');
    })->with('invalid customer fields');

    it('responde 404 si el cliente no existe', function () {
        $this->put(route('customers.update', 999), customerPayload())->assertNotFound();
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// destroy
// ─────────────────────────────────────────────────────────────────────────────

describe('destroy', function () {
    beforeEach(fn () => $this->actingAs(userWith('ver customers', 'eliminar customers')));

    it('elimina un cliente sin movimientos, redirige y avisa', function () {
        $customer = Customer::factory()->create();

        $this->delete(route('customers.destroy', $customer))
            ->assertRedirect(route('customers.index'))
            ->assertSessionHas('success', 'Cliente eliminado exitosamente.');

        $this->assertDatabaseMissing('customers', ['id' => $customer->id]);
    });

    it('no elimina a un cliente con ventas o pedidos', function () {
        $customer = Customer::factory()->create();
        orderFor($customer);

        $this->delete(route('customers.destroy', $customer))
            ->assertRedirect(route('customers.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('customers', ['id' => $customer->id]);
    });

    it('no elimina a un cliente con proformas', function () {
        $customer = Customer::factory()->create();
        quotationFor($customer, 'COT-0001');

        $this->delete(route('customers.destroy', $customer))
            ->assertRedirect(route('customers.index'))
            ->assertSessionHas('error', fn (string $message) => str_contains($message, 'proformas'));

        $this->assertDatabaseHas('customers', ['id' => $customer->id]);
    });

    it('elimina solo al cliente indicado', function () {
        [$keep, $remove] = Customer::factory()->count(2)->create();

        $this->delete(route('customers.destroy', $remove));

        expect(Customer::pluck('id')->all())->toBe([$keep->id]);
    });

    it('responde 404 si el cliente no existe', function () {
        $this->delete(route('customers.destroy', 999))->assertNotFound();
    });
});
