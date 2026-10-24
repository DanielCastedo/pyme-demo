<?php

use App\Models\Branch;
use App\Models\Cashbox;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| BranchController: CRUD, logo, QR de cobro, validaciones, permisos y reglas de borrado
|--------------------------------------------------------------------------
*/

/** Datos válidos de una sucursal; cada prueba sobrescribe solo lo que le interesa. */
function branchPayload(array $overrides = []): array
{
    return $overrides + [
        'name' => 'Sucursal Zona Norte',
        'address' => 'Av. Banzer Km 5',
        'phone' => '33445566',
        'is_active' => '1',
    ];
}

/** Casos inválidos compartidos por store y update: [campo, valor inválido]. */
dataset('invalid branch fields', [
    'sin nombre' => ['name', ''],
    'nombre de más de 255 caracteres' => ['name', str_repeat('a', 256)],
    'dirección de más de 255 caracteres' => ['address', str_repeat('a', 256)],
    'teléfono de más de 30 caracteres' => ['phone', str_repeat('8', 31)],
    'is_active no booleano' => ['is_active', 'no-booleano'],
]);

// ─────────────────────────────────────────────────────────────────────────────
// Autenticación y permisos
// ─────────────────────────────────────────────────────────────────────────────

describe('acceso', function () {
    it('redirige al login a un visitante', function () {
        $this->get(route('branches.index'))->assertRedirect(route('login'));
    });

    it('rechaza (403) a un usuario sin permisos en todas las acciones', function () {
        $this->actingAs(userWith());
        $branch = Branch::factory()->create();

        $this->get(route('branches.index'))->assertForbidden();
        $this->get(route('branches.show', $branch))->assertForbidden();
        $this->get(route('branches.edit', $branch))->assertForbidden();
        $this->post(route('branches.store'), branchPayload())->assertForbidden();
        $this->put(route('branches.update', $branch), branchPayload())->assertForbidden();
        $this->delete(route('branches.destroy', $branch))->assertForbidden();
    });

    it('aplica el permiso específico de cada acción (la policy) aunque pueda ver sucursales', function () {
        $this->actingAs(userWith('ver branches'));
        $branch = Branch::factory()->create();

        $this->get(route('branches.index'))->assertOk();
        $this->get(route('branches.show', $branch))->assertOk();
        $this->get(route('branches.edit', $branch))->assertForbidden();
        $this->post(route('branches.store'), branchPayload())->assertForbidden();
        $this->put(route('branches.update', $branch), branchPayload())->assertForbidden();
        $this->delete(route('branches.destroy', $branch))->assertForbidden();

        expect(Branch::where('id', $branch->id)->exists())->toBeTrue();
    });

    it('deja pasar todo al permiso "admin" (bypass de las policies)', function () {
        $this->actingAs(userWith('ver branches', 'admin'));

        $this->post(route('branches.store'), branchPayload(['name' => 'Sucursal Central Admin']))
            ->assertRedirect(route('branches.index'));

        $branch = Branch::where('name', 'Sucursal Central Admin')->firstOrFail();
        $this->put(route('branches.update', $branch), branchPayload(['name' => 'Sucursal Modificada Admin']))
            ->assertRedirect();
        $this->delete(route('branches.destroy', $branch))->assertRedirect();

        expect(Branch::where('name', 'Sucursal Modificada Admin')->exists())->toBeFalse();
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// index
// ─────────────────────────────────────────────────────────────────────────────

describe('index', function () {
    beforeEach(fn () => $this->actingAs(userWith('ver branches')));

    it('lista las sucursales ordenadas por nombre y pagina de a 10', function () {
        foreach (range(1, 12) as $i) {
            Branch::factory()->create(['name' => sprintf('Sucursal %02d', $i)]);
        }

        $response = $this->get(route('branches.index'))->assertOk()->assertViewIs('branches.index');

        $branches = $response->viewData('branches');

        expect($branches->total())->toBeGreaterThanOrEqual(12)
            ->and($branches->perPage())->toBe(10);
    });

    it('busca por nombre (filter[buscar])', function () {
        Branch::factory()->create(['name' => 'Sucursal Montero Especial']);
        Branch::factory()->create(['name' => 'Sucursal Warnes']);

        $names = $this->get(route('branches.index', ['filter' => ['buscar' => 'Montero']]))
            ->viewData('branches')->pluck('name')->all();

        expect($names)->toContain('Sucursal Montero Especial')
            ->and($names)->not->toContain('Sucursal Warnes');
    });

    it('devuelve una lista vacía si la búsqueda no encuentra nada', function () {
        $branches = $this->get(route('branches.index', ['filter' => ['buscar' => 'InexistenteXYZ123']]))
            ->viewData('branches');

        expect($branches->total())->toBe(0);
    });

    it('ordena por los campos permitidos y rechaza los demás con 400', function () {
        $this->get(route('branches.index', ['sort' => '-name']))->assertOk();
        $this->get(route('branches.index', ['sort' => 'id']))->assertOk();
        $this->get(route('branches.index', ['sort' => 'phone']))->assertStatus(400);
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// store
// ─────────────────────────────────────────────────────────────────────────────

describe('store', function () {
    beforeEach(fn () => $this->actingAs(userWith('ver branches', 'crear branches')));

    it('crea la sucursal, redirige al listado y avisa', function () {
        $this->post(route('branches.store'), branchPayload())
            ->assertRedirect(route('branches.index'))
            ->assertSessionHas('success', 'Sucursal registrada exitosamente.');

        $this->assertDatabaseHas('branches', [
            'name' => 'Sucursal Zona Norte',
            'address' => 'Av. Banzer Km 5',
            'phone' => '33445566',
            'is_active' => true,
        ]);
    });

    it('solo exige el nombre: dirección y teléfono son opcionales', function () {
        $this->post(route('branches.store'), ['name' => 'Sucursal Mínima'])
            ->assertRedirect(route('branches.index'))
            ->assertSessionHasNoErrors();

        $branch = Branch::where('name', 'Sucursal Mínima')->firstOrFail();

        expect($branch->address)->toBeNull()
            ->and($branch->phone)->toBeNull()
            ->and($branch->is_active)->toBeTrue();
    });

    it('crea activo por defecto y respeta is_active=0', function () {
        $this->post(route('branches.store'), ['name' => 'Sucursal Inactiva', 'is_active' => '0']);

        $branch = Branch::where('name', 'Sucursal Inactiva')->firstOrFail();
        expect($branch->is_active)->toBeFalse();
    });

    it('sube logo y QR de cobro al disco uploads', function () {
        Storage::fake('uploads');

        $logo = UploadedFile::fake()->image('logo.png', 200, 200);
        $qr = UploadedFile::fake()->image('qr.png', 200, 200);

        $this->post(route('branches.store'), branchPayload([
            'logo' => $logo,
            'payment_qr_image' => $qr,
        ]))
            ->assertRedirect(route('branches.index'))
            ->assertSessionHasNoErrors();

        $branch = Branch::where('name', 'Sucursal Zona Norte')->firstOrFail();

        expect($branch->logo)->not->toBeNull()
            ->and($branch->payment_qr_image)->not->toBeNull();

        Storage::disk('uploads')->assertExists($branch->logo);
        Storage::disk('uploads')->assertExists($branch->payment_qr_image);
    });

    it('valida los campos y no crea nada', function (string $field, mixed $invalidValue) {
        $initialCount = Branch::count();

        $this->from(route('branches.index'))
            ->post(route('branches.store'), branchPayload([$field => $invalidValue]))
            ->assertRedirect(route('branches.index'))
            ->assertSessionHasErrors($field);

        expect(Branch::count())->toBe($initialCount);
    })->with('invalid branch fields');
});

// ─────────────────────────────────────────────────────────────────────────────
// show
// ─────────────────────────────────────────────────────────────────────────────

describe('show', function () {
    beforeEach(fn () => $this->actingAs(userWith('ver branches')));

    it('muestra el detalle y carga los contadores de relaciones', function () {
        $branch = Branch::factory()->create(['name' => 'Sucursal Con Datos']);

        $response = $this->get(route('branches.show', $branch))
            ->assertOk()
            ->assertViewIs('branches.show');

        $loaded = $response->viewData('branch');
        expect($loaded->id)->toBe($branch->id)
            ->and(isset($loaded->users_count))->toBeTrue()
            ->and(isset($loaded->cashboxes_count))->toBeTrue()
            ->and(isset($loaded->warehouses_count))->toBeTrue();
    });

    it('responde 404 si la sucursal no existe', function () {
        $this->get(route('branches.show', 99999))->assertNotFound();
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// edit
// ─────────────────────────────────────────────────────────────────────────────

describe('edit', function () {
    beforeEach(fn () => $this->actingAs(userWith('ver branches', 'editar branches')));

    it('muestra el formulario con los datos actuales', function () {
        $branch = Branch::factory()->create(['name' => 'Sucursal Edición']);

        $this->get(route('branches.edit', $branch))
            ->assertOk()
            ->assertViewIs('branches.edit')
            ->assertViewHas('branch', fn ($b) => $b->id === $branch->id);
    });

    it('responde 404 si la sucursal no existe', function () {
        $this->get(route('branches.edit', 99999))->assertNotFound();
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// update
// ─────────────────────────────────────────────────────────────────────────────

describe('update', function () {
    beforeEach(fn () => $this->actingAs(userWith('ver branches', 'editar branches')));

    it('actualiza los datos, redirige al listado y avisa', function () {
        $branch = Branch::factory()->create(['name' => 'Nombre Previo']);

        $this->put(route('branches.update', $branch), [
            'name' => 'Nombre Nuevo',
            'address' => 'Nueva Dirección',
            'phone' => '77001122',
            'is_active' => '1',
        ])
            ->assertRedirect(route('branches.index'))
            ->assertSessionHas('success', 'Sucursal actualizada exitosamente.');

        $branch->refresh();
        expect($branch->name)->toBe('Nombre Nuevo')
            ->and($branch->address)->toBe('Nueva Dirección')
            ->and($branch->phone)->toBe('77001122');
    });

    it('permite desactivar la sucursal enviando is_active=0', function () {
        $branch = Branch::factory()->create(['is_active' => true]);

        $this->put(route('branches.update', $branch), branchPayload(['is_active' => '0']))
            ->assertRedirect(route('branches.index'));

        expect($branch->refresh()->is_active)->toBeFalse();
    });

    it('reemplaza el logo y elimina el anterior del disco', function () {
        Storage::fake('uploads');

        $oldLogo = UploadedFile::fake()->image('old-logo.png');
        $oldPath = $oldLogo->store('logos', 'uploads');

        $branch = Branch::factory()->create(['logo' => $oldPath]);
        Storage::disk('uploads')->assertExists($oldPath);

        $newLogo = UploadedFile::fake()->image('new-logo.png');

        $this->put(route('branches.update', $branch), branchPayload(['logo' => $newLogo]))
            ->assertRedirect(route('branches.index'))
            ->assertSessionHasNoErrors();

        $branch->refresh();
        expect($branch->logo)->not->toBe($oldPath);
        Storage::disk('uploads')->assertMissing($oldPath);
        Storage::disk('uploads')->assertExists($branch->logo);
    });

    it('valida los campos y no modifica la sucursal', function (string $field, mixed $invalidValue) {
        $branch = Branch::factory()->create(['name' => 'Original']);

        $this->from(route('branches.edit', $branch))
            ->put(route('branches.update', $branch), branchPayload([$field => $invalidValue]))
            ->assertRedirect(route('branches.edit', $branch))
            ->assertSessionHasErrors($field);

        expect($branch->refresh()->name)->toBe('Original');
    })->with('invalid branch fields');

    it('responde 404 si la sucursal no existe', function () {
        $this->put(route('branches.update', 99999), branchPayload())->assertNotFound();
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// destroy
// ─────────────────────────────────────────────────────────────────────────────

describe('destroy', function () {
    beforeEach(fn () => $this->actingAs(userWith('ver branches', 'eliminar branches')));

    it('elimina una sucursal sin registros asociados, redirige y avisa', function () {
        $branch = Branch::factory()->create();

        $this->delete(route('branches.destroy', $branch))
            ->assertRedirect(route('branches.index'))
            ->assertSessionHas('success', 'Sucursal eliminada exitosamente.');

        expect(Branch::where('id', $branch->id)->exists())->toBeFalse();
    });

    it('al eliminar una sucursal borra su logo y su QR de cobro del disco', function () {
        Storage::fake('uploads');

        $logo = UploadedFile::fake()->image('logo.png');
        $logoPath = $logo->store('logos', 'uploads');
        $qr = UploadedFile::fake()->image('qr.png');
        $qrPath = $qr->store('branch-qr', 'uploads');

        $branch = Branch::factory()->create(['logo' => $logoPath, 'payment_qr_image' => $qrPath]);

        $this->delete(route('branches.destroy', $branch))
            ->assertRedirect(route('branches.index'));

        Storage::disk('uploads')->assertMissing($logoPath);
        Storage::disk('uploads')->assertMissing($qrPath);
    });

    it('no elimina una sucursal con usuarios asociados y devuelve error', function () {
        $branch = Branch::factory()->create();
        User::factory()->create(['branche_id' => $branch->id]);

        $this->delete(route('branches.destroy', $branch))
            ->assertRedirect(route('branches.index'))
            ->assertSessionHas('error', 'No se puede eliminar la sucursal: tiene usuarios, cajas, almacenes o transacciones asociadas.');

        expect(Branch::where('id', $branch->id)->exists())->toBeTrue();
    });

    it('no elimina una sucursal con cajas asociadas y devuelve error', function () {
        $branch = Branch::factory()->create();
        Cashbox::create(['branche_id' => $branch->id, 'name' => 'Caja 1']);

        $this->delete(route('branches.destroy', $branch))
            ->assertRedirect(route('branches.index'))
            ->assertSessionHas('error', 'No se puede eliminar la sucursal: tiene usuarios, cajas, almacenes o transacciones asociadas.');

        expect(Branch::where('id', $branch->id)->exists())->toBeTrue();
    });

    it('no elimina una sucursal con almacenes asociados y devuelve error', function () {
        $branch = Branch::factory()->create();
        Warehouse::create(['branche_id' => $branch->id, 'name' => 'Almacén 1', 'code' => 'ALM-BR1']);

        $this->delete(route('branches.destroy', $branch))
            ->assertRedirect(route('branches.index'))
            ->assertSessionHas('error', 'No se puede eliminar la sucursal: tiene usuarios, cajas, almacenes o transacciones asociadas.');

        expect(Branch::where('id', $branch->id)->exists())->toBeTrue();
    });

    it('responde 404 si la sucursal no existe', function () {
        $this->delete(route('branches.destroy', 99999))->assertNotFound();
    });
});
