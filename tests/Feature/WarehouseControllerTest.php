<?php

use App\Models\Branch;
use App\Models\Warehouse;

/*
|--------------------------------------------------------------------------
| WarehouseController: CRUD, validaciones, códigos únicos y permisos
|--------------------------------------------------------------------------
*/

/** Datos válidos de un almacén; cada prueba sobrescribe solo lo que le interesa. */
function warehousePayload(Branch $branch, array $overrides = []): array
{
    return $overrides + [
        'branche_id' => $branch->id,
        'name' => 'Almacén Central',
        'code' => 'ALM-CENTRAL',
        'is_active' => '1',
    ];
}

/** Casos inválidos compartidos por store y update: [campo, valor inválido]. */
dataset('invalid warehouse fields', [
    'sin sucursal' => ['branche_id', ''],
    'sucursal inexistente' => ['branche_id', 99999],
    'sin nombre' => ['name', ''],
    'nombre de más de 255 caracteres' => ['name', str_repeat('a', 256)],
    'sin código' => ['code', ''],
    'código de más de 100 caracteres' => ['code', str_repeat('c', 101)],
    'is_active no booleano' => ['is_active', 'invalido'],
]);

// ─────────────────────────────────────────────────────────────────────────────
// Autenticación y permisos
// ─────────────────────────────────────────────────────────────────────────────

describe('acceso', function () {
    it('redirige al login a un visitante', function () {
        $this->get(route('warehouses.index'))->assertRedirect(route('login'));
    });

    it('rechaza (403) a un usuario sin permisos en todas las acciones', function () {
        $this->actingAs(userWith());
        $branch = testBranch();
        $warehouse = Warehouse::factory()->create(['branche_id' => $branch->id]);

        $this->get(route('warehouses.index'))->assertForbidden();
        $this->get(route('warehouses.show', $warehouse))->assertForbidden();
        $this->get(route('warehouses.edit', $warehouse))->assertForbidden();
        $this->post(route('warehouses.store'), warehousePayload($branch))->assertForbidden();
        $this->put(route('warehouses.update', $warehouse), warehousePayload($branch))->assertForbidden();
        $this->delete(route('warehouses.destroy', $warehouse))->assertForbidden();
    });

    it('aplica el permiso específico de cada acción (la policy) aunque pueda ver almacenes', function () {
        $this->actingAs(userWith('ver warehouses'));
        $branch = testBranch();
        $warehouse = Warehouse::factory()->create(['branche_id' => $branch->id]);

        $this->get(route('warehouses.index'))->assertOk();
        $this->get(route('warehouses.show', $warehouse))->assertOk();
        $this->get(route('warehouses.edit', $warehouse))->assertForbidden();
        $this->post(route('warehouses.store'), warehousePayload($branch))->assertForbidden();
        $this->put(route('warehouses.update', $warehouse), warehousePayload($branch))->assertForbidden();
        $this->delete(route('warehouses.destroy', $warehouse))->assertForbidden();

        expect(Warehouse::where('id', $warehouse->id)->exists())->toBeTrue();
    });

    it('deja pasar todo al permiso "admin" (bypass de las policies)', function () {
        $this->actingAs(userWith('ver warehouses', 'admin'));
        $branch = testBranch();

        $this->post(route('warehouses.store'), warehousePayload($branch, ['code' => 'ALM-ADM-1']))
            ->assertRedirect(route('warehouses.index'));

        $warehouse = Warehouse::where('code', 'ALM-ADM-1')->firstOrFail();
        $this->put(route('warehouses.update', $warehouse), warehousePayload($branch, ['name' => 'Actualizado por Admin']))
            ->assertRedirect();
        $this->delete(route('warehouses.destroy', $warehouse))->assertRedirect();

        expect(Warehouse::where('code', 'ALM-ADM-1')->exists())->toBeFalse();
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// index
// ─────────────────────────────────────────────────────────────────────────────

describe('index', function () {
    beforeEach(fn () => $this->actingAs(userWith('ver warehouses')));

    it('lista los almacenes ordenados por nombre y pagina de a 10', function () {
        $branch = testBranch();
        foreach (range(1, 12) as $i) {
            Warehouse::factory()->create([
                'branche_id' => $branch->id,
                'name' => sprintf('Almacén %02d', $i),
                'code' => sprintf('ALM-%02d', $i),
            ]);
        }

        $response = $this->get(route('warehouses.index'))->assertOk()->assertViewIs('warehouses.index');

        $warehouses = $response->viewData('warehouses');

        expect($warehouses->total())->toBeGreaterThanOrEqual(12)
            ->and($warehouses->perPage())->toBe(10);
    });

    it('busca por nombre y código (filter[buscar])', function (string $term, string $expectedCode) {
        $branch = testBranch();
        Warehouse::factory()->create(['branche_id' => $branch->id, 'name' => 'Depósito Principal', 'code' => 'DEP-01']);
        Warehouse::factory()->create(['branche_id' => $branch->id, 'name' => 'Bodega Repuestos', 'code' => 'BOD-99']);

        $codes = $this->get(route('warehouses.index', ['filter' => ['buscar' => $term]]))
            ->viewData('warehouses')->pluck('code')->all();

        expect($codes)->toBe([$expectedCode]);
    })->with([
        'por nombre' => ['Repuestos', 'BOD-99'],
        'por código' => ['DEP-01', 'DEP-01'],
    ]);

    it('ordena por los campos permitidos y rechaza los no permitidos con 400', function () {
        $this->get(route('warehouses.index', ['sort' => 'name']))->assertOk();
        $this->get(route('warehouses.index', ['sort' => '-code']))->assertOk();
        $this->get(route('warehouses.index', ['sort' => 'created_at']))->assertStatus(400);
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// store
// ─────────────────────────────────────────────────────────────────────────────

describe('store', function () {
    beforeEach(fn () => $this->actingAs(userWith('ver warehouses', 'crear warehouses')));

    it('crea el almacén, redirige al listado y avisa', function () {
        $branch = testBranch();

        $this->post(route('warehouses.store'), warehousePayload($branch))
            ->assertRedirect(route('warehouses.index'))
            ->assertSessionHas('success', 'Almacén registrado exitosamente.');

        $this->assertDatabaseHas('warehouses', [
            'branche_id' => $branch->id,
            'name' => 'Almacén Central',
            'code' => 'ALM-CENTRAL',
            'is_active' => true,
        ]);
    });

    it('rechaza códigos duplicados', function () {
        $branch = testBranch();
        Warehouse::factory()->create(['code' => 'ALM-DUP', 'branche_id' => $branch->id]);

        $this->from(route('warehouses.index'))
            ->post(route('warehouses.store'), warehousePayload($branch, ['code' => 'ALM-DUP']))
            ->assertRedirect(route('warehouses.index'))
            ->assertSessionHasErrors(['code' => 'Ya existe un almacén con ese código.']);
    });

    it('valida los campos y no crea nada', function (string $field, mixed $invalidValue) {
        $branch = testBranch();
        $initialCount = Warehouse::count();

        $this->from(route('warehouses.index'))
            ->post(route('warehouses.store'), warehousePayload($branch, [$field => $invalidValue]))
            ->assertRedirect(route('warehouses.index'))
            ->assertSessionHasErrors($field);

        expect(Warehouse::count())->toBe($initialCount);
    })->with('invalid warehouse fields');
});

// ─────────────────────────────────────────────────────────────────────────────
// show y edit
// ─────────────────────────────────────────────────────────────────────────────

describe('show y edit', function () {
    it('muestra el detalle del almacén cargando relaciones', function () {
        $this->actingAs(userWith('ver warehouses'));
        $branch = testBranch();
        $warehouse = Warehouse::factory()->create(['branche_id' => $branch->id]);

        $this->get(route('warehouses.show', $warehouse))
            ->assertOk()
            ->assertViewIs('warehouses.show')
            ->assertViewHas('warehouse', fn ($w) => $w->id === $warehouse->id);
    });

    it('muestra el formulario de edición con la lista de sucursales', function () {
        $this->actingAs(userWith('ver warehouses', 'editar warehouses'));
        $branch = testBranch();
        $warehouse = Warehouse::factory()->create(['branche_id' => $branch->id]);

        $this->get(route('warehouses.edit', $warehouse))
            ->assertOk()
            ->assertViewIs('warehouses.edit')
            ->assertViewHas('warehouse', fn ($w) => $w->id === $warehouse->id)
            ->assertViewHas('branches');
    });

    it('responde 404 si el almacén no existe en show o edit', function () {
        $this->actingAs(userWith('ver warehouses', 'editar warehouses'));
        $this->get(route('warehouses.show', 99999))->assertNotFound();
        $this->get(route('warehouses.edit', 99999))->assertNotFound();
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// update
// ─────────────────────────────────────────────────────────────────────────────

describe('update', function () {
    beforeEach(fn () => $this->actingAs(userWith('ver warehouses', 'editar warehouses')));

    it('actualiza los datos, redirige al listado y avisa', function () {
        $branch = testBranch();
        $warehouse = Warehouse::factory()->create(['branche_id' => $branch->id, 'name' => 'Alm Viejo']);

        $this->put(route('warehouses.update', $warehouse), [
            'branche_id' => $branch->id,
            'name' => 'Alm Nuevo',
            'code' => $warehouse->code,
            'is_active' => '1',
        ])
            ->assertRedirect(route('warehouses.index'))
            ->assertSessionHas('success', 'Almacén actualizado exitosamente.');

        expect($warehouse->refresh()->name)->toBe('Alm Nuevo');
    });

    it('permite conservar el mismo código sin error de unicidad', function () {
        $branch = testBranch();
        $warehouse = Warehouse::factory()->create(['branche_id' => $branch->id, 'code' => 'ALM-PROP']);

        $this->put(route('warehouses.update', $warehouse), warehousePayload($branch, ['code' => 'ALM-PROP']))
            ->assertRedirect(route('warehouses.index'))
            ->assertSessionHasNoErrors();
    });

    it('no permite usar el código de otro almacén', function () {
        $branch = testBranch();
        Warehouse::factory()->create(['branche_id' => $branch->id, 'code' => 'ALM-OTRO']);
        $warehouse = Warehouse::factory()->create(['branche_id' => $branch->id, 'code' => 'ALM-MIO']);

        $this->from(route('warehouses.edit', $warehouse))
            ->put(route('warehouses.update', $warehouse), warehousePayload($branch, ['code' => 'ALM-OTRO']))
            ->assertRedirect(route('warehouses.edit', $warehouse))
            ->assertSessionHasErrors(['code' => 'Ya existe otro almacén con ese código.']);

        expect($warehouse->refresh()->code)->toBe('ALM-MIO');
    });

    it('valida los campos y no modifica el almacén', function (string $field, mixed $invalidValue) {
        $branch = testBranch();
        $warehouse = Warehouse::factory()->create(['branche_id' => $branch->id, 'name' => 'Original']);

        $this->from(route('warehouses.edit', $warehouse))
            ->put(route('warehouses.update', $warehouse), warehousePayload($branch, [$field => $invalidValue]))
            ->assertRedirect(route('warehouses.edit', $warehouse))
            ->assertSessionHasErrors($field);

        expect($warehouse->refresh()->name)->toBe('Original');
    })->with('invalid warehouse fields');
});

// ─────────────────────────────────────────────────────────────────────────────
// destroy
// ─────────────────────────────────────────────────────────────────────────────

describe('destroy', function () {
    beforeEach(fn () => $this->actingAs(userWith('ver warehouses', 'eliminar warehouses')));

    it('elimina un almacén, redirige y avisa', function () {
        $branch = testBranch();
        $warehouse = Warehouse::factory()->create(['branche_id' => $branch->id]);

        $this->delete(route('warehouses.destroy', $warehouse))
            ->assertRedirect(route('warehouses.index'))
            ->assertSessionHas('success', 'Almacén eliminado exitosamente.');

        expect(Warehouse::where('id', $warehouse->id)->exists())->toBeFalse();
    });

    it('responde 404 si el almacén no existe', function () {
        $this->delete(route('warehouses.destroy', 99999))->assertNotFound();
    });
});
