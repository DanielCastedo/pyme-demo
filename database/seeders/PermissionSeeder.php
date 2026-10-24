<?php

// database/seeders/PermissionSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Limpiar la caché de Spatie antes de empezar (obligatorio)
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Módulos del sistema de la farmacia
        $modulos = [
            'usuarios', 'roles', 'permisos', 'role-permisos', 'menus', 'sidebars',
            'companies', 'subscriptions', 'products', 'product-categories', 'inventory-movements',
            'sales', 'customers', 'subscription-plans',
            'purchases', 'suppliers',
            'expenses', 'expense-categories',
            'types',
            'payments',
            'categories',
            'warehouses',
            'branches',
            'promotions',
            'product-units',
            'product-warehouses',
            'product-components',
            'stock-movements',
            'orders',
            'cashboxes',
            'quotations',
            'consignments',
            'units',
            'bitacora',
        ];

        $acciones = ['ver', 'crear', 'editar', 'eliminar'];
        $permisosParaInsertar = [];

        foreach ($modulos as $modulo) {
            foreach ($acciones as $accion) {
                $permisosParaInsertar[] = [
                    'name' => "{$accion} {$modulo}",
                    'guard_name' => 'web',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // Permisos puntuales que no siguen el patrón CRUD estándar
        $permisosExtra = [
            'admin',                    // acceso total: salta cualquier policy (ver App\Policies\Concerns\AuthorizesWithSuperAdmin). Solo para el rol superadmin.
            'ventas.anular',            // anular una venta ya confirmada
            'ventas.editar-precio',     // cambiar el precio de venta de un producto al vender
            'consignaciones.liquidar',  // liquidar una consignación: registrar lo vendido (genera una venta) y lo devuelto (vuelve al stock)
            'ventas.credito',           // vender a crédito (el cliente queda debiendo un saldo)
            'ventas.descuento',         // aplicar descuentos por línea al vender
            'ventas.exportar',          // exportar reportes de ventas
            'ventas.cobrar',            // registrar cobros de una venta a crédito (abonos)
            'compras.exportar',         // descargar el PDF de una compra
            'gastos.exportar',          // descargar el PDF de un gasto
            'proformas.exportar',       // descargar el PDF de una proforma
            'consignaciones.exportar',  // descargar el PDF de una consignación
            'compras.anular',           // anular una compra recibida
            'compras.credito',          // comprar a crédito (el proveedor queda con un saldo pendiente)
            'compras.pagar',            // registrar pagos de una compra a crédito (abonos)
            'kardex.exportar',          // exportar el kardex a Excel
            'kardex.importar',          // importar movimientos masivos
            'productos.exportar',       // exportar catálogo de productos a Excel
            'productos.importar',       // importar productos masivos
            'suscripciones.aprobar',    // aprobar/rechazar solicitudes de pago (solo admin)
            'reportes.ver',             // ver el dashboard de reportes financieros
            'reportes.exportar',        // exportar reportes (Excel/PDF)
        ];

        foreach ($permisosExtra as $permiso) {
            $permisosParaInsertar[] = [
                'name' => $permiso,
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Insertar todos los permisos en una sola consulta masiva, ignorando duplicados
        Permission::insertOrIgnore($permisosParaInsertar);
    }
}
