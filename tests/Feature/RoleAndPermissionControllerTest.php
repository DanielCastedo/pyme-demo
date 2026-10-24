<?php

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    testBranch();
    Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
});

function securitySuperAdmin(): User
{
    $user = userWith('admin');
    $user->assignRole('superadmin');
    return $user;
}

// =========================================================================
// 1. ROLE CONTROLLER
// =========================================================================

describe('RoleController', function () {
    it('requiere autenticación y rol superadmin', function () {
        $this->get(route('roles.index'))->assertRedirect(route('login'));

        $regular = userWith();
        $this->actingAs($regular)->get(route('roles.index'))->assertForbidden();
    });

    it('lista roles existentes y busca por nombre con filter[buscar]', function () {
        $admin = securitySuperAdmin();
        $rolA = Role::create(['name' => 'auditor', 'guard_name' => 'web']);
        $rolB = Role::create(['name' => 'repartidor', 'guard_name' => 'web']);

        $response = $this->actingAs($admin)->get(route('roles.index', ['filter[buscar]' => 'auditor']));

        $response->assertOk()
            ->assertViewIs('roles.index');
        $items = $response->viewData('roles');
        expect($items->pluck('id')->all())->toContain($rolA->id)
            ->and($items->pluck('id')->all())->not->toContain($rolB->id);
    });

    it('crea un rol y le asocia permisos', function () {
        $admin = securitySuperAdmin();
        $perm1 = Permission::firstOrCreate(['name' => 'ver auditorias', 'guard_name' => 'web']);
        $perm2 = Permission::firstOrCreate(['name' => 'crear auditorias', 'guard_name' => 'web']);

        $response = $this->actingAs($admin)->post(route('roles.store'), [
            'name' => 'supervisor_general',
            'permissions' => [$perm1->name, $perm2->name],
        ]);

        $response->assertRedirect(route('roles.index'))
            ->assertSessionHas('success');

        $created = Role::where('name', 'supervisor_general')->first();
        expect($created)->not->toBeNull()
            ->and($created->hasPermissionTo('ver auditorias'))->toBeTrue()
            ->and($created->hasPermissionTo('crear auditorias'))->toBeTrue();
    });

    it('falla al crear un rol con nombre duplicado o vacío', function () {
        $admin = securitySuperAdmin();
        Role::create(['name' => 'existente', 'guard_name' => 'web']);

        $response = $this->actingAs($admin)->post(route('roles.store'), [
            'name' => 'existente',
        ]);
        $response->assertSessionHasErrors(['name']);

        $responseEmpty = $this->actingAs($admin)->post(route('roles.store'), [
            'name' => '',
        ]);
        $responseEmpty->assertSessionHasErrors(['name']);
    });

    it('actualiza el nombre del rol sin tocar sus permisos (esos se asignan en Roles y Permisos)', function () {
        $admin = securitySuperAdmin();
        $permA = Permission::firstOrCreate(['name' => 'permiso_a', 'guard_name' => 'web']);
        $permB = Permission::firstOrCreate(['name' => 'permiso_b', 'guard_name' => 'web']);
        $rol = Role::create(['name' => 'rol_viejo', 'guard_name' => 'web']);
        $rol->givePermissionTo($permA);

        $response = $this->actingAs($admin)->put(route('roles.update', $rol), [
            'name' => 'rol_renombrado',
            'permissions' => [$permB->name],
        ]);

        $response->assertRedirect(route('roles.index'))
            ->assertSessionHas('success');

        $rol->refresh();
        expect($rol->name)->toBe('rol_renombrado')
            ->and($rol->hasPermissionTo('permiso_a'))->toBeTrue()
            ->and($rol->hasPermissionTo('permiso_b'))->toBeFalse();
    });

    it('elimina un rol existente', function () {
        $admin = securitySuperAdmin();
        $rol = Role::create(['name' => 'a_eliminar', 'guard_name' => 'web']);

        $response = $this->actingAs($admin)->delete(route('roles.destroy', $rol));

        $response->assertRedirect(route('roles.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('roles', ['id' => $rol->id]);
    });
});

// =========================================================================
// 2. PERMISOS CONTROLLER
// =========================================================================

describe('PermisosController', function () {
    it('requiere autenticación y rol superadmin para gestionar permisos', function () {
        $this->get(route('permisos.index'))->assertRedirect(route('login'));

        $regular = userWith();
        $this->actingAs($regular)->get(route('permisos.index'))->assertForbidden();
    });

    it('lista los permisos en el sistema', function () {
        $admin = securitySuperAdmin();
        $perm = Permission::firstOrCreate(['name' => 'reportes avanzados', 'guard_name' => 'web']);

        $response = $this->actingAs($admin)->get(route('permisos.index'));

        $response->assertOk()
            ->assertViewIs('permisos.index')
            ->assertSee('reportes avanzados');
    });

    it('busca permisos por nombre (filter[buscar])', function () {
        $admin = securitySuperAdmin();
        Permission::firstOrCreate(['name' => 'ver reportes avanzados', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'exportar excel ventas', 'guard_name' => 'web']);

        $names = $this->actingAs($admin)
            ->get(route('permisos.index', ['filter' => ['buscar' => 'reportes']]))
            ->viewData('permisos')->pluck('name')->all();

        expect($names)->toContain('ver reportes avanzados')
            ->and($names)->not->toContain('exportar excel ventas');
    });

    it('crea un permiso exitosamente con guard web', function () {
        $admin = securitySuperAdmin();

        $response = $this->actingAs($admin)->post(route('permisos.store'), [
            'name' => 'exportar excel ventas',
        ]);

        $response->assertRedirect(route('permisos.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('permissions', [
            'name' => 'exportar excel ventas',
            'guard_name' => 'web',
        ]);
    });

    it('falla al crear un permiso repetido o vacío', function () {
        $admin = securitySuperAdmin();
        Permission::firstOrCreate(['name' => 'permiso unico', 'guard_name' => 'web']);

        $response = $this->actingAs($admin)->post(route('permisos.store'), [
            'name' => 'permiso unico',
        ]);
        $response->assertSessionHasErrors(['name']);

        $responseEmpty = $this->actingAs($admin)->post(route('permisos.store'), [
            'name' => '',
        ]);
        $responseEmpty->assertSessionHasErrors(['name']);
    });

    it('actualiza el nombre de un permiso', function () {
        $admin = securitySuperAdmin();
        $perm = Permission::firstOrCreate(['name' => 'permiso inicial', 'guard_name' => 'web']);

        $response = $this->actingAs($admin)->put(route('permisos.update', $perm), [
            'name' => 'permiso modificado',
        ]);

        $response->assertRedirect(route('permisos.index'))
            ->assertSessionHas('success');

        expect($perm->fresh()->name)->toBe('permiso modificado');
    });

    it('elimina un permiso existente', function () {
        $admin = securitySuperAdmin();
        $perm = Permission::firstOrCreate(['name' => 'permiso borrar', 'guard_name' => 'web']);

        $response = $this->actingAs($admin)->delete(route('permisos.destroy', $perm));

        $response->assertRedirect(route('permisos.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('permissions', ['id' => $perm->id]);
    });
});

// =========================================================================
// 3. ROLE PERMISOS CONTROLLER
// =========================================================================

describe('RolePermisosController', function () {
    it('muestra la asignación de permisos por rol en index', function () {
        $admin = securitySuperAdmin();
        $rol = Role::create(['name' => 'gestor_almacen', 'guard_name' => 'web']);
        $perm = Permission::firstOrCreate(['name' => 'mover inventario', 'guard_name' => 'web']);
        $rol->givePermissionTo($perm);

        $response = $this->actingAs($admin)->get(route('role-permisos.index'));

        $response->assertOk()
            ->assertViewIs('rolepermisos.index')
            ->assertSee('gestor_almacen');
    });

    it('muestra el formulario de edición de permisos para un rol', function () {
        $admin = securitySuperAdmin();
        $rol = Role::create(['name' => 'cajero_sucursal', 'guard_name' => 'web']);

        $response = $this->actingAs($admin)->get(route('role-permisos.edit', $rol->id));

        $response->assertOk()
            ->assertViewIs('rolepermisos.edit')
            ->assertViewHas('role');
    });

    it('sincroniza permisos mediante update en role-permisos', function () {
        $admin = securitySuperAdmin();
        $rol = Role::create(['name' => 'secretaria', 'guard_name' => 'web']);
        $perm1 = Permission::firstOrCreate(['name' => 'agendar citas', 'guard_name' => 'web']);
        $perm2 = Permission::firstOrCreate(['name' => 'emitir comprobantes', 'guard_name' => 'web']);
        $rol->givePermissionTo($perm1);

        $response = $this->actingAs($admin)->put(route('role-permisos.update', $rol->id), [
            'permissions' => [$perm2->name],
        ]);

        $response->assertRedirect(route('role-permisos.index'))
            ->assertSessionHas('success');

        $rol->refresh();
        expect($rol->hasPermissionTo('emitir comprobantes'))->toBeTrue()
            ->and($rol->hasPermissionTo('agendar citas'))->toBeFalse();
    });
});
