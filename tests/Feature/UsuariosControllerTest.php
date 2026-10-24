<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    testBranch();
    Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'vendedor', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'cajero', 'guard_name' => 'web']);
});

// superAdminUser helper is loaded globally from Pest.php

// =========================================================================
// 1. AUTENTICACIÓN Y ROL SUPERADMIN
// =========================================================================

describe('acceso y rol superadmin', function () {
    it('redirige al login a usuarios no autenticados', function () {
        $user = User::factory()->create();

        $this->get(route('usuarios.index'))->assertRedirect(route('login'));
        $this->post(route('usuarios.store'), [])->assertRedirect(route('login'));
        $this->get(route('usuarios.edit', $user))->assertRedirect(route('login'));
        $this->put(route('usuarios.update', $user), [])->assertRedirect(route('login'));
        $this->delete(route('usuarios.destroy', $user))->assertRedirect(route('login'));
    });

    it('prohíbe el acceso con 403 a usuarios autenticados sin rol superadmin', function () {
        $regularUser = userWith();
        $targetUser = User::factory()->create();

        $this->actingAs($regularUser)->get(route('usuarios.index'))->assertForbidden();
        $this->actingAs($regularUser)->post(route('usuarios.store'), [])->assertForbidden();
        $this->actingAs($regularUser)->get(route('usuarios.edit', $targetUser))->assertForbidden();
        $this->actingAs($regularUser)->put(route('usuarios.update', $targetUser), [])->assertForbidden();
        $this->actingAs($regularUser)->delete(route('usuarios.destroy', $targetUser))->assertForbidden();
    });
});

// =========================================================================
// 2. INDEX Y FILTROS
// =========================================================================

describe('index y búsqueda', function () {
    it('muestra el listado de usuarios y carga los roles disponibles', function () {
        $admin = superAdminUser();
        $target = User::factory()->create(['name' => 'Carlos Vendedor']);
        $target->assignRole('vendedor');

        $response = $this->actingAs($admin)->get(route('usuarios.index'));

        $response->assertOk()
            ->assertViewIs('usuarios.index')
            ->assertSee('Carlos Vendedor');
    });

    it('permite filtrar usuarios por nombre usando filter[buscar]', function () {
        $admin = superAdminUser();
        $userA = User::factory()->create(['name' => 'Mariana Rodriguez']);
        $userB = User::factory()->create(['name' => 'Roberto Jimenez']);

        $response = $this->actingAs($admin)->get(route('usuarios.index', ['filter[buscar]' => 'Mariana']));

        $response->assertOk();
        $items = $response->viewData('usuarios');
        expect($items->pluck('id')->all())->toContain($userA->id)
            ->and($items->pluck('id')->all())->not->toContain($userB->id);
    });
});

// =========================================================================
// 3. STORE (CREACIÓN) Y VALIDACIÓN
// =========================================================================

describe('creación de usuario (store)', function () {
    it('crea un usuario, encripta la contraseña y le asigna los roles seleccionados', function () {
        $admin = superAdminUser();

        $payload = [
            'name' => 'Nuevo Empleado',
            'email' => 'empleado@empresa.com',
            'password' => 'password123',
            'branche_id' => testBranch()->id,
            'roles' => ['vendedor', 'cajero'],
        ];

        $response = $this->actingAs($admin)->post(route('usuarios.store'), $payload);

        $response->assertRedirect(route('usuarios.index'))
            ->assertSessionHas('success');

        $created = User::where('email', 'empleado@empresa.com')->first();
        expect($created)->not->toBeNull()
            ->and($created->name)->toBe('Nuevo Empleado')
            ->and(Hash::check('password123', $created->password))->toBeTrue()
            ->and($created->hasRole('vendedor'))->toBeTrue()
            ->and($created->hasRole('cajero'))->toBeTrue();
    });

    it('falla si los campos requeridos faltan', function () {
        $admin = superAdminUser();

        $response = $this->actingAs($admin)->post(route('usuarios.store'), [
            'name' => '',
            'email' => '',
            'password' => '',
            'roles' => [],
        ]);

        $response->assertSessionHasErrors(['name', 'email', 'password', 'roles']);
    });

    it('falla si el email ya existe o el password es menor a 8 caracteres', function () {
        $admin = superAdminUser();
        $existing = User::factory()->create(['email' => 'repetido@empresa.com']);

        $response = $this->actingAs($admin)->post(route('usuarios.store'), [
            'name' => 'Juan Perez',
            'email' => 'repetido@empresa.com',
            'password' => '12345',
            'roles' => ['vendedor'],
        ]);

        $response->assertSessionHasErrors(['email', 'password']);
    });
});

// =========================================================================
// 4. EDIT, UPDATE Y DESTROY
// =========================================================================

describe('edición, actualización y eliminación', function () {
    test('la vista show de usuarios no está definida en las rutas actuales')->todo('Route::resource usuarios excluye create y show.');

    it('muestra el formulario edit con roles preseleccionados', function () {
        $admin = superAdminUser();
        $target = User::factory()->create(['name' => 'Pedro Editar']);
        $target->assignRole('vendedor');

        $response = $this->actingAs($admin)->get(route('usuarios.edit', $target));

        $response->assertOk()
            ->assertViewIs('usuarios.edit')
            ->assertViewHas('userRoles', ['vendedor']);
    });

    it('actualiza el usuario manteniendo la contraseña si se deja vacía', function () {
        $admin = superAdminUser();
        $target = User::factory()->create([
            'name' => 'Antiguo Nombre',
            'email' => 'antiguo@empresa.com',
            'password' => Hash::make('original123'),
        ]);
        $target->assignRole('vendedor');

        $payload = [
            'name' => 'Nombre Actualizado',
            'email' => 'antiguo@empresa.com',
            'password' => '',
            'branche_id' => testBranch()->id,
            'roles' => ['cajero'],
        ];

        $response = $this->actingAs($admin)->put(route('usuarios.update', $target), $payload);

        $response->assertRedirect(route('usuarios.index'))
            ->assertSessionHas('success');

        $target->refresh();
        expect($target->name)->toBe('Nombre Actualizado')
            ->and(Hash::check('original123', $target->password))->toBeTrue()
            ->and($target->hasRole('cajero'))->toBeTrue()
            ->and($target->hasRole('vendedor'))->toBeFalse();
    });

    it('actualiza la contraseña si se proporciona una nueva', function () {
        $admin = superAdminUser();
        $target = User::factory()->create([
            'password' => Hash::make('viejaPassword123'),
        ]);
        $target->assignRole('vendedor');

        $payload = [
            'name' => $target->name,
            'email' => $target->email,
            'password' => 'nuevaPassword456',
            'branche_id' => testBranch()->id,
            'roles' => ['vendedor'],
        ];

        $response = $this->actingAs($admin)->put(route('usuarios.update', $target), $payload);

        $response->assertRedirect(route('usuarios.index'));
        $target->refresh();
        expect(Hash::check('nuevaPassword456', $target->password))->toBeTrue();
    });

    it('elimina un usuario correctamente', function () {
        $admin = superAdminUser();
        $target = User::factory()->create();

        $response = $this->actingAs($admin)->delete(route('usuarios.destroy', $target));

        $response->assertRedirect(route('usuarios.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('users', ['id' => $target->id]);
    });
});
