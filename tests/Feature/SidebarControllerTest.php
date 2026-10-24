<?php

use App\Models\Sidebar;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    testBranch();
    Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
});

function superAdminSidebarUser(): User
{
    $user = userWith('admin');
    $user->assignRole('superadmin');
    return $user;
}

// =========================================================================
// 1. AUTENTICACIÓN Y AUTORIZACIÓN
// =========================================================================

describe('autenticación y rol superadmin', function () {
    it('redirige a login a visitantes no autenticados', function () {
        $this->get(route('sidebars.index'))->assertRedirect(route('login'));
        $this->post(route('sidebars.store'), [])->assertRedirect(route('login'));
    });

    it('prohíbe el acceso con 403 a usuarios que no son superadmin', function () {
        $user = userWith();

        $this->actingAs($user)->get(route('sidebars.index'))->assertForbidden();
        $this->actingAs($user)->post(route('sidebars.store'), [])->assertForbidden();
    });
});

// =========================================================================
// 2. CRUD DE SIDEBAR
// =========================================================================

describe('operaciones CRUD de sidebar', function () {
    it('lista los menús de la barra lateral y busca por nombre con filter[buscar]', function () {
        $admin = superAdminSidebarUser();
        $m1 = Sidebar::create(['nombre' => 'Reportes Financieros', 'orden' => 1]);
        $m2 = Sidebar::create(['nombre' => 'Configuracion General', 'orden' => 2]);

        $response = $this->actingAs($admin)->get(route('sidebars.index', ['filter[buscar]' => 'Reportes']));

        $response->assertOk()
            ->assertViewIs('sidebars.index');
        $items = $response->viewData('menus');
        expect($items->pluck('id')->all())->toContain($m1->id)
            ->and($items->pluck('id')->all())->not->toContain($m2->id);
    });

    it('crea un elemento de menú correctamente', function () {
        $admin = superAdminSidebarUser();

        $payload = [
            'nombre' => 'Catálogo Especial',
            'ruta' => 'catalogos.index',
            'orden' => 10,
        ];

        $response = $this->actingAs($admin)->post(route('sidebars.store'), $payload);

        $response->assertRedirect(route('sidebars.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('sidebars', [
            'nombre' => 'Catálogo Especial',
            'orden' => 10,
        ]);
    });

    it('muestra la vista de edición de menú', function () {
        $admin = superAdminSidebarUser();
        $menu = Sidebar::create(['nombre' => 'Inventario', 'orden' => 1]);

        $response = $this->actingAs($admin)->get(route('sidebars.edit', $menu));

        $response->assertOk()
            ->assertViewIs('sidebars.edit')
            ->assertSee('Inventario');
    });

    it('actualiza un elemento de menú existente', function () {
        $admin = superAdminSidebarUser();
        $menu = Sidebar::create(['nombre' => 'Ventas Legacy', 'orden' => 3]);

        $payload = [
            'nombre' => 'Ventas Nueva Versión',
            'ruta' => 'sales.index',
            'orden' => 5,
        ];

        $response = $this->actingAs($admin)->put(route('sidebars.update', $menu), $payload);

        $response->assertRedirect(route('sidebars.index'))
            ->assertSessionHas('success');

        expect($menu->fresh()->nombre)->toBe('Ventas Nueva Versión')
            ->and($menu->fresh()->orden)->toBe(5);
    });

    it('elimina un elemento de menú', function () {
        $admin = superAdminSidebarUser();
        $menu = Sidebar::create(['nombre' => 'Menú temporal', 'orden' => 99]);

        $response = $this->actingAs($admin)->delete(route('sidebars.destroy', $menu));

        $response->assertRedirect(route('sidebars.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('sidebars', ['id' => $menu->id]);
    });
});
