<?php

// database/seeders/RolesSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesSeeder extends Seeder
{
    /** Comodín: el rol recibe todos los permisos que existan (incluido "admin", el bypass total de policies). */
    private const TODOS = '*';

    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        /*
         * Un rol => sus permisos. Para agregar o quitar algo a un rol, se edita solo su lista.
         * Los nombres que no existan en PermissionSeeder se ignoran.
         */
        $rolesConPermisos = [

            // ── SUPERADMIN: acceso a todo el sistema ─────────────────────────
            'superadmin' => self::TODOS,

            // ── ADMIN: módulos operativos del día a día de la farmacia ─────
            'admin' => [
                // Configuración
                'ver companies', 'editar companies',
                'ver subscriptions',
                'ver types', 'crear types', 'editar types', 'eliminar types',

                // Seguridad
                'ver usuarios', 'crear usuarios', 'editar usuarios', 'eliminar usuarios',
                'ver roles', 'crear roles', 'editar roles',
                'ver permisos', 'crear permisos', 'editar permisos',
                'ver role-permisos', 'editar role-permisos',

                // Inventario
                'ver products', 'crear products', 'editar products', 'eliminar products',
                'productos.exportar', 'productos.importar',
                'ver product-categories', 'crear product-categories', 'editar product-categories', 'eliminar product-categories',
                'ver categories', 'crear categories', 'editar categories', 'eliminar categories',
                'ver units', 'crear units', 'editar units', 'eliminar units',
                'ver product-units', 'crear product-units', 'editar product-units', 'eliminar product-units',
                'ver product-components', 'crear product-components', 'editar product-components', 'eliminar product-components',
                'ver warehouses', 'crear warehouses', 'editar warehouses', 'eliminar warehouses',
                'ver branches', 'crear branches', 'editar branches', 'eliminar branches',
                'ver product-warehouses', 'crear product-warehouses', 'editar product-warehouses', 'eliminar product-warehouses',
                'ver stock-movements', 'crear stock-movements', 'editar stock-movements', 'eliminar stock-movements',
                'ver inventory-movements', 'crear inventory-movements', 'editar inventory-movements', 'eliminar inventory-movements',
                'ver promotions', 'crear promotions', 'editar promotions', 'eliminar promotions',
                'kardex.importar',

                // Ventas
                'ver sales', 'crear sales', 'editar sales', 'eliminar sales',
                'ventas.anular', 'ventas.editar-precio', 'ventas.descuento', 'ventas.credito', 'ventas.cobrar', 'ventas.exportar',
                'ver orders', 'crear orders', 'editar orders', 'eliminar orders',
                'ver quotations', 'crear quotations', 'editar quotations', 'eliminar quotations', 'proformas.exportar',
                'ver consignments', 'crear consignments', 'editar consignments', 'eliminar consignments',
                'consignaciones.liquidar', 'consignaciones.exportar',
                'ver customers', 'crear customers', 'editar customers', 'eliminar customers',
                'ver payments', 'crear payments', 'editar payments', 'eliminar payments',

                // Compras
                'ver purchases', 'crear purchases', 'editar purchases', 'compras.anular', 'compras.exportar',
                'compras.credito', 'compras.pagar',
                'ver suppliers', 'crear suppliers', 'editar suppliers', 'eliminar suppliers',

                // Gastos
                'ver expenses', 'crear expenses', 'editar expenses', 'eliminar expenses', 'gastos.exportar',
                'ver expense-categories', 'crear expense-categories', 'editar expense-categories', 'eliminar expense-categories',

                // Finanzas
                'reportes.ver', 'reportes.exportar',
                'ver cashboxes', 'crear cashboxes', 'editar cashboxes', 'eliminar cashboxes',
            ],

            // ── OPERADOR: ventas en mostrador, proformas, clientes y consultas de inventario ──
            'operador' => [
                // Inventario (solo consulta)
                'ver products',
                'ver product-categories',
                'ver categories',
                'ver product-units',
                'ver warehouses',
                'ver product-warehouses',
                'ver promotions',

                // Ventas
                'ver sales', 'crear sales', 'ventas.cobrar', 'ventas.exportar',
                'ver orders', 'crear orders',
                'ver quotations', 'crear quotations', 'editar quotations', 'proformas.exportar',
                'ver customers', 'crear customers', 'editar customers',
            ],
        ];

        $todosLosPermisos = Permission::where('guard_name', 'web')->get();

        foreach ($rolesConPermisos as $nombre => $permisos) {
            $role = Role::firstOrCreate(['name' => $nombre, 'guard_name' => 'web']);

            $role->syncPermissions(
                $permisos === self::TODOS
                    ? $todosLosPermisos
                    : $todosLosPermisos->whereIn('name', $permisos)->values()
            );
        }

        $this->command->info('✅ Roles y permisos de la farmacia asignados correctamente.');
    }
}
