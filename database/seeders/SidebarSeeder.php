<?php

// database/seeders/SidebarSeeder.php

namespace Database\Seeders;

use App\Models\Sidebar;
use Illuminate\Database\Seeder;

class SidebarSeeder extends Seeder
{
    /**
     * Estructura completa del menú lateral, organizada por grupos.
     * Cada grupo puede tener 'children' (sub-items) o ser un enlace directo (sin 'children').
     *
     * rol_requerido: úsalo SOLO para lo que es 100% exclusivo de un rol puntual
     * (ej. "Seguridad", solo superadmin — no hay nada por encima que deba heredarlo).
     * permiso_requerido: úsalo para todo lo demás, así admin y superadmin comparten
     * acceso sin depender de jerarquías estáticas (superadmin ya tiene todos los
     * permisos vía RolesSeeder::syncPermissions($todosLosPermisos)).
     */
    private array $menu = [
        [
            'nombre' => 'Inicio',
            'ruta' => 'dashboard',
            'icono' => 'home',
            'orden' => 1,
            'rol_requerido' => null,
            'permiso_requerido' => null,
        ],

        [
            'nombre' => 'Seguridad',
            'icono' => 'shield',
            'orden' => 2,
            'rol_requerido' => null,
            'permiso_requerido' => null,
            'children' => [
                ['nombre' => 'Usuarios', 'ruta' => 'usuarios.index', 'orden' => 1, 'rol_requerido' => null, 'permiso_requerido' => 'ver usuarios'],
                ['nombre' => 'Roles', 'ruta' => 'roles.index', 'orden' => 2, 'rol_requerido' => null, 'permiso_requerido' => 'ver roles'],
                ['nombre' => 'Permisos', 'ruta' => 'permisos.index', 'orden' => 3, 'rol_requerido' => null, 'permiso_requerido' => 'ver permisos'],
                ['nombre' => 'Roles y Permisos', 'ruta' => 'role-permisos.index', 'orden' => 4, 'rol_requerido' => null, 'permiso_requerido' => 'ver role-permisos'],
                ['nombre' => 'Menus', 'ruta' => 'sidebars.index', 'orden' => 5, 'rol_requerido' => 'superadmin', 'permiso_requerido' => null],
                ['nombre' => 'Bitácora', 'ruta' => 'bitacora.index', 'orden' => 6, 'rol_requerido' => 'superadmin', 'permiso_requerido' => null],
            ],
        ],

        [
            'nombre' => 'Configuración',
            'icono' => 'cog',
            'orden' => 3,
            'rol_requerido' => null,
            'permiso_requerido' => null,
            'children' => [
                ['nombre' => 'Empresa', 'ruta' => 'companies.index', 'orden' => 1, 'rol_requerido' => null, 'permiso_requerido' => 'ver companies'],
                ['nombre' => 'Mi Suscripción', 'ruta' => 'subscriptions.index', 'orden' => 2, 'rol_requerido' => null, 'permiso_requerido' => 'ver subscriptions'],
                ['nombre' => 'Facturas', 'ruta' => 'subscriptions.invoices', 'orden' => 3, 'rol_requerido' => 'superadmin', 'permiso_requerido' => null],
                ['nombre' => 'Administrar Planes', 'ruta' => 'subscription-plans.index', 'orden' => 4, 'rol_requerido' => 'superadmin', 'permiso_requerido' => null],
                ['nombre' => 'Planes de Suscripción', 'ruta' => 'subscriptions.plans', 'orden' => 5, 'rol_requerido' => 'superadmin', 'permiso_requerido' => null],
                ['nombre' => 'Solicitudes de Pago', 'ruta' => 'subscriptions.pending', 'orden' => 6, 'rol_requerido' => 'superadmin', 'permiso_requerido' => null],
                ['nombre' => 'Tipos', 'ruta' => 'types.index', 'orden' => 7, 'rol_requerido' => null, 'permiso_requerido' => 'ver types'],
            ],
        ],
        [
            'nombre' => 'Ventas',
            'icono' => 'cart',
            'orden' => 5,
            'rol_requerido' => null,
            'permiso_requerido' => null,
            'children' => [
                ['nombre' => 'Ventas', 'ruta' => 'sales.index', 'orden' => 1, 'rol_requerido' => null, 'permiso_requerido' => 'ver sales'],
                ['nombre' => 'Pedidos', 'ruta' => 'pedidos.index', 'orden' => 2, 'rol_requerido' => null, 'permiso_requerido' => 'ver orders'],
                ['nombre' => 'Cuentas por cobrar', 'ruta' => 'receivables.index', 'orden' => 3, 'rol_requerido' => null, 'permiso_requerido' => 'ver sales'],
                ['nombre' => 'Consignaciones', 'ruta' => 'consignments.index', 'orden' => 4, 'rol_requerido' => null, 'permiso_requerido' => 'ver consignments'],
                ['nombre' => 'Proformas', 'ruta' => 'quotations.index', 'orden' => 5, 'rol_requerido' => null, 'permiso_requerido' => 'ver quotations'],
                ['nombre' => 'Clientes', 'ruta' => 'customers.index', 'orden' => 6, 'rol_requerido' => null, 'permiso_requerido' => 'ver customers'],
                ['nombre' => 'Métodos de Pago', 'ruta' => 'payments.index', 'orden' => 7, 'rol_requerido' => null, 'permiso_requerido' => 'ver payments'],
            ],
        ],
        [
            'nombre' => 'Inventario',
            'icono' => 'box',
            'orden' => 4,
            'rol_requerido' => null,
            'permiso_requerido' => null,
            'children' => [
                ['nombre' => 'Productos', 'ruta' => 'products.index', 'orden' => 1, 'rol_requerido' => null, 'permiso_requerido' => 'ver products'],
                ['nombre' => 'Unidades de medida', 'ruta' => 'units.index', 'orden' => 2, 'rol_requerido' => null, 'permiso_requerido' => 'ver units'],
                ['nombre' => 'Unidades de Producto', 'ruta' => 'product-units.index', 'orden' => 3, 'rol_requerido' => null, 'permiso_requerido' => 'ver product-units'],
                ['nombre' => 'Componentes / Kits', 'ruta' => 'product-components.index', 'orden' => 4, 'rol_requerido' => null, 'permiso_requerido' => 'ver product-components'],
                ['nombre' => 'Categorías', 'ruta' => 'categories.index', 'orden' => 5, 'rol_requerido' => null, 'permiso_requerido' => 'ver categories'],
                ['nombre' => 'Almacenes', 'ruta' => 'warehouses.index', 'orden' => 6, 'rol_requerido' => null, 'permiso_requerido' => 'ver warehouses'],
                ['nombre' => 'Sucursales', 'ruta' => 'branches.index', 'orden' => 7, 'rol_requerido' => null, 'permiso_requerido' => 'ver branches'],
                ['nombre' => 'Stock por Almacén', 'ruta' => 'product-warehouses.index', 'orden' => 8, 'rol_requerido' => null, 'permiso_requerido' => 'ver product-warehouses'],
                ['nombre' => 'Stock por Sucursal', 'ruta' => 'stock-overview.index', 'orden' => 9, 'rol_requerido' => null, 'permiso_requerido' => 'ver product-warehouses'],
                ['nombre' => 'Movimientos de Stock', 'ruta' => 'stock-movements.index', 'orden' => 10, 'rol_requerido' => null, 'permiso_requerido' => 'ver stock-movements'],
                ['nombre' => 'Promociones', 'ruta' => 'promotions.index', 'orden' => 11, 'rol_requerido' => null, 'permiso_requerido' => 'ver promotions'],
            ],
        ],

        [
            'nombre' => 'Compras',
            'icono' => 'truck',
            'orden' => 6,
            'rol_requerido' => null,
            'permiso_requerido' => null,
            'children' => [
                ['nombre' => 'Compras', 'ruta' => 'purchases.index', 'orden' => 1, 'rol_requerido' => null, 'permiso_requerido' => 'ver purchases'],
                ['nombre' => 'Cuentas por pagar', 'ruta' => 'payables.index', 'orden' => 2, 'rol_requerido' => null, 'permiso_requerido' => 'ver purchases'],
                ['nombre' => 'Proveedores', 'ruta' => 'suppliers.index', 'orden' => 3, 'rol_requerido' => null, 'permiso_requerido' => 'ver suppliers'],
            ],
        ],
        [
            'nombre' => 'Gastos',
            'icono' => 'chart',
            'orden' => 7,
            'rol_requerido' => null,
            'permiso_requerido' => null,
            'children' => [
                ['nombre' => 'Gastos', 'ruta' => 'expenses.index', 'orden' => 1, 'rol_requerido' => null, 'permiso_requerido' => 'ver expenses'],
                ['nombre' => 'Categorías de gasto', 'ruta' => 'expense-categories.index', 'orden' => 2, 'rol_requerido' => null, 'permiso_requerido' => 'ver expense-categories'],
            ],
        ],

        [
            'nombre' => 'Finanzas',
            'icono' => 'trend',
            'orden' => 8,
            'rol_requerido' => null,
            'permiso_requerido' => null,
            'children' => [
                ['nombre' => 'Reportes', 'ruta' => 'finance.report', 'orden' => 1, 'rol_requerido' => null, 'permiso_requerido' => 'reportes.ver'],
                ['nombre' => 'Ganancias por producto', 'ruta' => 'finance.products', 'orden' => 2, 'rol_requerido' => null, 'permiso_requerido' => 'reportes.ver'],
                ['nombre' => 'Cajas', 'ruta' => 'cashboxes.index', 'orden' => 3, 'rol_requerido' => null, 'permiso_requerido' => 'ver cashboxes'],
            ],
        ],

        [
            'nombre' => 'Términos y Condiciones',
            'ruta' => 'terms.index',
            'icono' => 'document',
            'orden' => 10,
            'rol_requerido' => null,
            'permiso_requerido' => null,
        ],
    ];

    /**
     * SVG (solo el contenido del <path>, sin la etiqueta <svg>) de cada ícono
     * usado por los grupos principales. Los sub-items no llevan ícono.
     */
    private array $iconos = [
        'home' => '<path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />',
        'shield' => '<path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />',
        'cog' => '<path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />',
        'box' => '<path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />',
        'cart' => '<path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />',
        'truck' => '<path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zm10 0a2 2 0 11-4 0 2 2 0 014 0zM13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10m10 0H9m4 0h2m4 0h1a1 1 0 001-1v-4.586a1 1 0 00-.293-.707l-2.414-2.414A1 1 0 0017.586 7H13" />',
        'trend' => '<path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />',
        'upload' => '<path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />',
        'chart' => '<path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />',
        'document' => '<path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />',
    ];

    public function run(): void
    {
        Sidebar::query()->delete();

        foreach ($this->menu as $item) {
            $this->createMenuItem($item, parentId: null);
        }

        $this->command->info('✅ Sidebar reorganizado por módulos, con permisos actualizados para todos los roles.');
    }

    /**
     * Crea un ítem del menú (grupo o sub-item) y, si tiene 'children',
     * los crea recursivamente enlazados a este como padre.
     */
    private function createMenuItem(array $item, ?int $parentId): void
    {
        $sidebar = Sidebar::create([
            'nombre' => $item['nombre'],
            'ruta' => $item['ruta'] ?? null,
            'icono' => isset($item['icono']) ? $this->iconos[$item['icono']] : null,
            'orden' => $item['orden'],
            'parent_id' => $parentId,
            'rol_requerido' => $item['rol_requerido'],
            'permiso_requerido' => $item['permiso_requerido'],
        ]);

        foreach ($item['children'] ?? [] as $child) {
            $this->createMenuItem($child, parentId: $sidebar->id);
        }
    }
}
