<?php

use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    testBranch();
    Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'operador', 'guard_name' => 'web']);
});

function createSuperAdmin(): User
{
    $u = userWith('admin');
    $u->assignRole('superadmin');
    return $u;
}

function createAdminUser(): User
{
    $u = userWith('ver companies', 'editar companies', 'ver usuarios', 'crear usuarios', 'editar usuarios', 'eliminar usuarios', 'ver roles', 'ver permisos');
    $u->assignRole('admin');
    return $u;
}

// =========================================================================
// 1. TÉRMINOS Y CONDICIONES
// =========================================================================

describe('términos y condiciones', function () {
    it('redirige a terms.pending a un admin designado que aún no ha aceptado los términos', function () {
        $user = createAdminUser();
        $user->update([
            'is_designated_admin' => true,
            'terms_accepted_at' => null,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertRedirect(route('terms.pending'));
    });

    it('permite al admin designado ver la pantalla de términos pendientes', function () {
        $user = createAdminUser();
        $user->update([
            'is_designated_admin' => true,
            'terms_accepted_at' => null,
        ]);

        $response = $this->actingAs($user)->get(route('terms.pending'));
        $response->assertOk()
            ->assertSee('Lectura y Aceptación Obligatoria de Términos')
            ->assertSee('CLÁUSULA TERCERA');
    });

    it('procesa la aceptación de términos, marca terms_accepted_at y desbloquea el acceso', function () {
        $user = createAdminUser();
        $user->update([
            'is_designated_admin' => true,
            'terms_accepted_at' => null,
        ]);

        $response = $this->actingAs($user)->post(route('terms.accept'), [
            'accept_terms' => '1',
        ]);

        $response->assertRedirect(route('dashboard'));
        $user->refresh();

        expect($user->terms_accepted_at)->not->toBeNull();
        expect($user->hasAcceptedTerms())->toBeTrue();
        expect($user->mustAcceptTerms())->toBeFalse();

        // Ahora ya puede acceder normalmente al dashboard
        $dashResponse = $this->actingAs($user)->get(route('dashboard'));
        $dashResponse->assertOk();
    });

    it('procesa el rechazo de términos, cierra la sesión y redirige al login', function () {
        $this->withoutExceptionHandling();
        $user = createAdminUser();
        $user->update([
            'is_designated_admin' => true,
            'terms_accepted_at' => null,
        ]);

        $response = $this->actingAs($user)->post(route('terms.reject'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    });

    it('permite a superadmin y admin ver el módulo de términos y condiciones', function () {
        $superadmin = createSuperAdmin();
        $admin = createAdminUser();

        $this->actingAs($superadmin)->get(route('terms.index'))->assertOk();
        $this->actingAs($admin)->get(route('terms.index'))->assertOk();
    });
});

// =========================================================================
// 2. BITÁCORA SOLO PARA SUPERADMIN
// =========================================================================

describe('restricción de bitácora', function () {
    it('permite ver la bitácora únicamente al superadmin', function () {
        $superadmin = createSuperAdmin();
        $this->actingAs($superadmin)->get(route('bitacora.index'))->assertOk();
    });

    it('deniega con 403 a un usuario admin que intenta acceder a la bitácora', function () {
        $admin = createAdminUser();
        $this->actingAs($admin)->get(route('bitacora.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('bitacora.pdf'))->assertForbidden();
    });
});

// =========================================================================
// 3. USUARIOS (ADMIN NO PUEDE LISTAR, VER, EDITAR NI ELIMINAR A SUPERADMIN)
// =========================================================================

describe('control de usuarios por parte de admin', function () {
    it('el admin puede ver la lista de usuarios pero no ve al superadmin', function () {
        $admin = createAdminUser();
        $superadmin = createSuperAdmin();
        $superadmin->update(['name' => 'Super Administrador Secreto']);

        $regularUser = User::factory()->create(['name' => 'Empleado Regular']);
        $regularUser->assignRole('operador');

        $response = $this->actingAs($admin)->get(route('usuarios.index'));
        $response->assertOk()
            ->assertSee('Empleado Regular')
            ->assertDontSee('Super Administrador Secreto');
    });

    it('prohíbe con 403 a un admin editar o actualizar a un superadmin', function () {
        $admin = createAdminUser();
        $superadmin = createSuperAdmin();

        $this->actingAs($admin)->get(route('usuarios.edit', $superadmin))->assertForbidden();

        $this->actingAs($admin)->put(route('usuarios.update', $superadmin), [
            'name' => 'Hacked Name',
            'email' => $superadmin->email,
            'roles' => ['admin'],
        ])->assertForbidden();
    });

    it('prohíbe con 403 a un admin eliminar a un superadmin', function () {
        $admin = createAdminUser();
        $superadmin = createSuperAdmin();

        $this->actingAs($admin)->delete(route('usuarios.destroy', $superadmin))->assertForbidden();
        expect(User::find($superadmin->id))->not->toBeNull();
    });

    it('prohíbe a un admin asignar el rol superadmin', function () {
        $admin = createAdminUser();
        $target = User::factory()->create();

        $this->actingAs($admin)->put(route('usuarios.update', $target), [
            'name' => $target->name,
            'email' => $target->email,
            'branche_id' => testBranch()->id,
            'roles' => ['superadmin'],
        ])->assertForbidden();
    });
});

// =========================================================================
// 4. ROLES Y PERMISOS: ADMIN NO PUEDE ELIMINAR
// =========================================================================

describe('restricción de eliminación de roles y permisos', function () {
    it('prohíbe con 403 a un admin eliminar un rol', function () {
        $admin = createAdminUser();
        $role = Role::create(['name' => 'rol_de_prueba', 'guard_name' => 'web']);

        $this->actingAs($admin)->delete(route('roles.destroy', $role))->assertForbidden();
        expect(Role::where('name', 'rol_de_prueba')->exists())->toBeTrue();
    });

    it('prohíbe con 403 a un admin eliminar un permiso', function () {
        $admin = createAdminUser();
        $perm = Permission::create(['name' => 'permiso_de_prueba', 'guard_name' => 'web']);

        $this->actingAs($admin)->delete(route('permisos.destroy', $perm))->assertForbidden();
        expect(Permission::where('name', 'permiso_de_prueba')->exists())->toBeTrue();
    });
});

// =========================================================================
// 5. EMPRESA: ADMIN PUEDE EDITAR PERO NO REGISTRAR OTRA
// =========================================================================

describe('gestión de empresa', function () {
    it('permite al admin editar los datos de la empresa', function () {
        $admin = createAdminUser();
        $company = Company::factory()->create([
            'name' => 'Empresa Original',
            'tax_id' => '1020304050',
        ]);

        $response = $this->actingAs($admin)->put(route('companies.update', $company), [
            'name' => 'Empresa Actualizada por Admin',
            'tax_id' => '1020304050',
            'city' => 'Santa Cruz',
            'country' => 'Bolivia',
        ]);

        $response->assertRedirect(route('companies.index'));
        expect($company->fresh()->name)->toBe('Empresa Actualizada por Admin');
    });

    it('prohíbe con 403 a un admin registrar una nueva empresa', function () {
        $admin = createAdminUser();

        $this->actingAs($admin)->post(route('companies.store'), [
            'name' => 'Segunda Empresa Prohibida',
            'tax_id' => '9999999999',
            'city' => 'La Paz',
            'country' => 'Bolivia',
        ])->assertForbidden();
    });

    it('prohíbe con 403 a un admin eliminar una empresa', function () {
        $admin = createAdminUser();
        $company = Company::factory()->create();

        $this->actingAs($admin)->delete(route('companies.destroy', $company))->assertForbidden();
        expect(Company::find($company->id))->not->toBeNull();
    });
});
