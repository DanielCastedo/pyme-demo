<?php

use App\Models\Type;

/*
|--------------------------------------------------------------------------
| TypeController: CRUD, código único, validaciones y permisos
|--------------------------------------------------------------------------
*/

/** Datos válidos de un tipo de movimiento. */
function typePayload(array $overrides = []): array
{
    return $overrides + [
        'name' => 'Ajuste de Inventario Positivo',
        'code' => 'AJUSTE_POS',
        'sign' => '+',
    ];
}

/** Casos inválidos para store y update. */
dataset('invalid type fields', [
    'sin nombre' => ['name', ''],
    'nombre de más de 255 caracteres' => ['name', str_repeat('a', 256)],
    'sin código' => ['code', ''],
    'código de más de 50 caracteres' => ['code', str_repeat('c', 51)],
    'signo de más de 10 caracteres' => ['sign', str_repeat('+', 11)],
]);

// ─────────────────────────────────────────────────────────────────────────────
// Autenticación y permisos
// ─────────────────────────────────────────────────────────────────────────────

describe('acceso', function () {
    it('redirige al login a un visitante', function () {
        $this->get(route('types.index'))->assertRedirect(route('login'));
    });

    it('rechaza (403) a un usuario sin permisos en todas las acciones', function () {
        $this->actingAs(userWith());
        $type = Type::factory()->create();

        $this->get(route('types.index'))->assertForbidden();
        $this->get(route('types.show', $type))->assertForbidden();
        $this->get(route('types.edit', $type))->assertForbidden();
        $this->post(route('types.store'), typePayload())->assertForbidden();
        $this->put(route('types.update', $type), typePayload())->assertForbidden();
        $this->delete(route('types.destroy', $type))->assertForbidden();
    });

    it('aplica el permiso específico de cada acción (la policy) aunque pueda ver tipos', function () {
        $this->actingAs(userWith('ver types'));
        $type = Type::factory()->create();

        $this->get(route('types.index'))->assertOk();
        $this->get(route('types.show', $type))->assertOk();
        $this->get(route('types.edit', $type))->assertForbidden();
        $this->post(route('types.store'), typePayload())->assertForbidden();
        $this->put(route('types.update', $type), typePayload())->assertForbidden();
        $this->delete(route('types.destroy', $type))->assertForbidden();

        expect(Type::where('id', $type->id)->exists())->toBeTrue();
    });

    it('deja pasar todo al permiso "admin" (bypass de las policies)', function () {
        $this->actingAs(userWith('ver types', 'admin'));

        $this->post(route('types.store'), typePayload(['code' => 'TYP_ADM_1']))
            ->assertRedirect(route('types.index'));

        $type = Type::where('code', 'TYP_ADM_1')->firstOrFail();
        $this->put(route('types.update', $type), typePayload(['name' => 'Tipo Admin Editado']))
            ->assertRedirect();
        $this->delete(route('types.destroy', $type))->assertRedirect();

        expect(Type::where('code', 'TYP_ADM_1')->exists())->toBeFalse();
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// index
// ─────────────────────────────────────────────────────────────────────────────

describe('index', function () {
    beforeEach(fn () => $this->actingAs(userWith('ver types')));

    it('lista los tipos ordenados por nombre y pagina de a 10', function () {
        foreach (range(1, 12) as $i) {
            Type::factory()->create([
                'name' => sprintf('Tipo %02d', $i),
                'code' => sprintf('COD_%02d', $i),
            ]);
        }

        $response = $this->get(route('types.index'))->assertOk()->assertViewIs('types.index');

        $types = $response->viewData('types');

        expect($types->total())->toBeGreaterThanOrEqual(12)
            ->and($types->perPage())->toBe(10);
    });

    it('busca por nombre o código (filter[buscar])', function (string $term, string $expectedCode) {
        Type::factory()->create(['name' => 'Devolución de Compra', 'code' => 'DEV_CMP']);
        Type::factory()->create(['name' => 'Merma Operativa', 'code' => 'MER_OPE']);

        $codes = $this->get(route('types.index', ['filter' => ['buscar' => $term]]))
            ->viewData('types')->pluck('code')->all();

        expect($codes)->toBe([$expectedCode]);
    })->with([
        'por nombre' => ['Devolución', 'DEV_CMP'],
        'por código' => ['MER_OPE', 'MER_OPE'],
    ]);

    it('ordena por los campos permitidos y rechaza los no permitidos con 400', function () {
        $this->get(route('types.index', ['sort' => 'name']))->assertOk();
        $this->get(route('types.index', ['sort' => '-code']))->assertOk();
        $this->get(route('types.index', ['sort' => 'sign']))->assertStatus(400);
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// store
// ─────────────────────────────────────────────────────────────────────────────

describe('store', function () {
    beforeEach(fn () => $this->actingAs(userWith('ver types', 'crear types')));

    it('crea el tipo, redirige al listado y avisa', function () {
        $this->post(route('types.store'), typePayload())
            ->assertRedirect(route('types.index'))
            ->assertSessionHas('success', 'Tipo registrado exitosamente.');

        $this->assertDatabaseHas('types', [
            'name' => 'Ajuste de Inventario Positivo',
            'code' => 'AJUSTE_POS',
            'sign' => '+',
        ]);
    });

    it('no permite registrar códigos repetidos', function () {
        Type::factory()->create(['code' => 'COD_REPETIDO']);

        $this->from(route('types.index'))
            ->post(route('types.store'), typePayload(['code' => 'COD_REPETIDO']))
            ->assertRedirect(route('types.index'))
            ->assertSessionHasErrors(['code' => 'Ya existe un tipo con ese código.']);
    });

    it('valida los campos y no crea nada', function (string $field, mixed $invalidValue) {
        $initialCount = Type::count();

        $this->from(route('types.index'))
            ->post(route('types.store'), typePayload([$field => $invalidValue]))
            ->assertRedirect(route('types.index'))
            ->assertSessionHasErrors($field);

        expect(Type::count())->toBe($initialCount);
    })->with('invalid type fields');
});

// ─────────────────────────────────────────────────────────────────────────────
// show y edit
// ─────────────────────────────────────────────────────────────────────────────

describe('show y edit', function () {
    it('muestra el detalle del tipo', function () {
        $this->actingAs(userWith('ver types'));
        $type = Type::factory()->create(['name' => 'Detalle Tipo']);

        $this->get(route('types.show', $type))
            ->assertOk()
            ->assertViewIs('types.show')
            ->assertViewHas('type', fn ($t) => $t->id === $type->id);
    });

    it('muestra el formulario de edición con los datos actuales', function () {
        $this->actingAs(userWith('ver types', 'editar types'));
        $type = Type::factory()->create(['name' => 'Tipo Edición']);

        $this->get(route('types.edit', $type))
            ->assertOk()
            ->assertViewIs('types.edit')
            ->assertViewHas('type', fn ($t) => $t->name === 'Tipo Edición');
    });

    it('responde 404 si el tipo no existe', function () {
        $this->actingAs(userWith('ver types', 'editar types'));
        $this->get(route('types.show', 99999))->assertNotFound();
        $this->get(route('types.edit', 99999))->assertNotFound();
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// update
// ─────────────────────────────────────────────────────────────────────────────

describe('update', function () {
    beforeEach(fn () => $this->actingAs(userWith('ver types', 'editar types')));

    it('actualiza los datos, redirige al listado y avisa', function () {
        $type = Type::factory()->create(['name' => 'Tipo Viejo']);

        $this->put(route('types.update', $type), [
            'name' => 'Tipo Nuevo',
            'code' => $type->code,
            'sign' => '-',
        ])
            ->assertRedirect(route('types.index'))
            ->assertSessionHas('success', 'Tipo actualizado exitosamente.');

        $type->refresh();
        expect($type->name)->toBe('Tipo Nuevo')
            ->and($type->sign)->toBe('-');
    });

    it('permite guardar manteniendo el propio código', function () {
        $type = Type::factory()->create(['code' => 'COD_MIO']);

        $this->put(route('types.update', $type), typePayload(['code' => 'COD_MIO']))
            ->assertRedirect(route('types.index'))
            ->assertSessionHasNoErrors();
    });

    it('no permite cambiar el código a uno que ya pertenece a otro tipo', function () {
        Type::factory()->create(['code' => 'COD_OTRO']);
        $type = Type::factory()->create(['code' => 'COD_ACTUAL']);

        $this->from(route('types.edit', $type))
            ->put(route('types.update', $type), typePayload(['code' => 'COD_OTRO']))
            ->assertRedirect(route('types.edit', $type))
            ->assertSessionHasErrors(['code' => 'Ya existe otro tipo con ese código.']);

        expect($type->refresh()->code)->toBe('COD_ACTUAL');
    });

    it('valida los campos y no modifica el tipo', function (string $field, mixed $invalidValue) {
        $type = Type::factory()->create(['name' => 'Original']);

        $this->from(route('types.edit', $type))
            ->put(route('types.update', $type), typePayload([$field => $invalidValue]))
            ->assertRedirect(route('types.edit', $type))
            ->assertSessionHasErrors($field);

        expect($type->refresh()->name)->toBe('Original');
    })->with('invalid type fields');
});

// ─────────────────────────────────────────────────────────────────────────────
// destroy
// ─────────────────────────────────────────────────────────────────────────────

describe('destroy', function () {
    beforeEach(fn () => $this->actingAs(userWith('ver types', 'eliminar types')));

    it('elimina un tipo, redirige y avisa', function () {
        $type = Type::factory()->create();

        $this->delete(route('types.destroy', $type))
            ->assertRedirect(route('types.index'))
            ->assertSessionHas('success', 'Tipo eliminado exitosamente.');

        expect(Type::where('id', $type->id)->exists())->toBeFalse();
    });

    it('responde 404 si el tipo no existe', function () {
        $this->delete(route('types.destroy', 99999))->assertNotFound();
    });
});
