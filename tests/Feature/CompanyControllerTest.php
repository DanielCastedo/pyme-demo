<?php

use App\Models\Company;

/*
|--------------------------------------------------------------------------
| CompanyController: Configuración de empresa y permisos
|--------------------------------------------------------------------------
*/

/** Datos válidos de una empresa. */
function companyPayload(array $overrides = []): array
{
    return $overrides + [
        'name' => 'Brazil Importaciones S.R.L.',
        'legal_name' => 'Comercializadora e Importadora Brazil S.R.L.',
        'tax_id' => '1029384756',
        'phone' => '33445566',
        'email' => 'contacto@brazilimportaciones.bo',
        'address' => 'Av. Cristo Redentor #450',
        'city' => 'Santa Cruz de la Sierra',
        'country' => 'Bolivia',
        'currency' => 'BOB',
    ];
}

/** Casos inválidos para store y update. */
dataset('invalid company fields', [
    'sin nombre' => ['name', ''],
    'nombre de más de 255 caracteres' => ['name', str_repeat('a', 256)],
    'razón social de más de 255 caracteres' => ['legal_name', str_repeat('b', 256)],
    'NIT de más de 50 caracteres' => ['tax_id', str_repeat('1', 51)],
    'correo inválido' => ['email', 'no-es-correo'],
    'teléfono de más de 30 caracteres' => ['phone', str_repeat('9', 31)],
]);

// ─────────────────────────────────────────────────────────────────────────────
// Autenticación y permisos
// ─────────────────────────────────────────────────────────────────────────────

describe('acceso', function () {
    it('redirige al login a un visitante', function () {
        $this->get(route('companies.index'))->assertRedirect(route('login'));
    });

    it('rechaza (403) a un usuario sin el permiso "ver companies"', function () {
        $this->actingAs(userWith());
        $company = Company::factory()->create();

        $this->get(route('companies.index'))->assertForbidden();
        $this->get(route('companies.show', $company))->assertForbidden();
        $this->get(route('companies.edit', $company))->assertForbidden();
        $this->post(route('companies.store'), companyPayload())->assertForbidden();
        $this->put(route('companies.update', $company), companyPayload())->assertForbidden();
        $this->delete(route('companies.destroy', $company))->assertForbidden();
    });

    it('permite el acceso a todas las acciones con el permiso "ver companies"', function () {
        $this->actingAs(userWith('ver companies'));
        $company = Company::factory()->create();

        $this->get(route('companies.index'))->assertOk();
        $this->get(route('companies.show', $company))->assertOk();
        $this->get(route('companies.edit', $company))->assertOk();
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// index
// ─────────────────────────────────────────────────────────────────────────────

describe('index', function () {
    beforeEach(fn () => $this->actingAs(userWith('ver companies')));

    it('lista las empresas y pagina de a 10', function () {
        foreach (range(1, 12) as $i) {
            Company::factory()->create(['name' => sprintf('Empresa %02d', $i)]);
        }

        $response = $this->get(route('companies.index'))->assertOk()->assertViewIs('companies.index');

        $companies = $response->viewData('companies');

        expect($companies->total())->toBeGreaterThanOrEqual(12)
            ->and($companies->perPage())->toBe(10);
    });

    it('busca por nombre comercial (filter[buscar])', function () {
        Company::factory()->create(['name' => 'Importadora Oriente Especial']);
        Company::factory()->create(['name' => 'Distribuidora Andina']);

        $names = $this->get(route('companies.index', ['filter' => ['buscar' => 'Oriente']]))
            ->viewData('companies')->pluck('name')->all();

        expect($names)->toContain('Importadora Oriente Especial')
            ->and($names)->not->toContain('Distribuidora Andina');
    });

    it('ordena por los campos permitidos y rechaza los no permitidos con 400', function () {
        $this->get(route('companies.index', ['sort' => 'name']))->assertOk();
        $this->get(route('companies.index', ['sort' => '-id']))->assertOk();
        $this->get(route('companies.index', ['sort' => 'tax_id']))->assertStatus(400);
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// store
// ─────────────────────────────────────────────────────────────────────────────

describe('store', function () {
    beforeEach(function () {
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'superadmin']);
        $user = userWith('ver companies');
        $user->assignRole('superadmin');
        $this->actingAs($user);
    });

    it('crea la empresa, redirige al listado y avisa', function () {
        $this->post(route('companies.store'), companyPayload())
            ->assertRedirect(route('companies.index'))
            ->assertSessionHas('success', 'Empresa registrada exitosamente.');

        $this->assertDatabaseHas('companies', [
            'name' => 'Brazil Importaciones S.R.L.',
            'tax_id' => '1029384756',
            'city' => 'Santa Cruz de la Sierra',
        ]);
    });

    it('no permite registrar empresas con NIT duplicado', function () {
        Company::factory()->create(['tax_id' => 'NIT-EXISTENTE']);

        $this->from(route('companies.index'))
            ->post(route('companies.store'), companyPayload(['tax_id' => 'NIT-EXISTENTE']))
            ->assertRedirect(route('companies.index'))
            ->assertSessionHasErrors(['tax_id' => 'Ya existe una empresa registrada con ese NIT.']);
    });

    it('valida los campos y no crea nada', function (string $field, mixed $invalidValue) {
        $initialCount = Company::count();

        $this->from(route('companies.index'))
            ->post(route('companies.store'), companyPayload([$field => $invalidValue]))
            ->assertRedirect(route('companies.index'))
            ->assertSessionHasErrors($field);

        expect(Company::count())->toBe($initialCount);
    })->with('invalid company fields');
});

// ─────────────────────────────────────────────────────────────────────────────
// show y edit
// ─────────────────────────────────────────────────────────────────────────────

describe('show y edit', function () {
    beforeEach(fn () => $this->actingAs(userWith('ver companies')));

    it('muestra el detalle de la empresa', function () {
        $company = Company::factory()->create(['name' => 'Empresa Detalle']);

        $this->get(route('companies.show', $company))
            ->assertOk()
            ->assertViewIs('companies.show')
            ->assertViewHas('company', fn ($c) => $c->id === $company->id);
    });

    it('muestra el formulario de edición con los datos actuales', function () {
        $company = Company::factory()->create(['name' => 'Empresa Edición']);

        $this->get(route('companies.edit', $company))
            ->assertOk()
            ->assertViewIs('companies.edit')
            ->assertViewHas('company', fn ($c) => $c->id === $company->id);
    });

    it('responde 404 si la empresa no existe', function () {
        $this->get(route('companies.show', 99999))->assertNotFound();
        $this->get(route('companies.edit', 99999))->assertNotFound();
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// update
// ─────────────────────────────────────────────────────────────────────────────

describe('update', function () {
    beforeEach(fn () => $this->actingAs(userWith('ver companies')));

    it('actualiza los datos, redirige al listado y avisa', function () {
        $company = Company::factory()->create(['name' => 'Nombre Previo']);

        $this->put(route('companies.update', $company), companyPayload(['name' => 'Nombre Actualizado']))
            ->assertRedirect(route('companies.index'))
            ->assertSessionHas('success', 'Empresa actualizada exitosamente.');

        expect($company->refresh()->name)->toBe('Nombre Actualizado');
    });

    it('responde 404 si la empresa no existe', function () {
        $this->put(route('companies.update', 99999), companyPayload())->assertNotFound();
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// destroy
// ─────────────────────────────────────────────────────────────────────────────

describe('destroy', function () {
    beforeEach(function () {
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'superadmin']);
        $user = userWith('ver companies');
        $user->assignRole('superadmin');
        $this->actingAs($user);
    });

    it('elimina una empresa', function () {
        $company = Company::factory()->create();

        $this->delete(route('companies.destroy', $company))
            ->assertRedirect(route('companies.index'))
            ->assertSessionHas('success', 'Empresa eliminada exitosamente.');

        expect(Company::where('id', $company->id)->exists())->toBeFalse();
    });

    it('responde 404 si la empresa no existe', function () {
        $this->delete(route('companies.destroy', 99999))->assertNotFound();
    });
});
