<?php

namespace App\Support;

/** Agrupa los nombres de permisos por área y módulo para mostrarlos ordenados en pantalla. */
final class PermissionGroups
{
    public const SUPERADMIN_AREAS = ['Seguridad', 'Configuración', 'Sistema'];

    /**
     * Agrupa los permisos por área y módulo para mostrarlos ordenados en la pantalla.
     * Devuelve [área => [módulo => ['label' => ..., 'permissions' => [nombre => etiqueta de la acción]]]].
     */
    public static function group(iterable $names): array
    {
        // módulo => [etiqueta, área]
        $modules = [
            'usuarios' => ['Usuarios', 'Seguridad'],
            'roles' => ['Roles', 'Seguridad'],
            'permisos' => ['Permisos', 'Seguridad'],
            'role-permisos' => ['Asignación de permisos', 'Seguridad'],
            'menus' => ['Menús', 'Seguridad'],
            'sidebars' => ['Menú lateral', 'Seguridad'],
            'bitacora' => ['Bitácora', 'Seguridad'],
            'companies' => ['Empresa', 'Configuración'],
            'branches' => ['Sucursales', 'Inventario'],
            'types' => ['Tipos', 'Configuración'],
            'payments' => ['Métodos de pago', 'Ventas'],
            'cashboxes' => ['Cajas', 'Finanzas'],
            'subscriptions' => ['Suscripciones', 'Configuración'],
            'subscription-plans' => ['Planes de suscripción', 'Configuración'],
            'products' => ['Productos', 'Inventario'],
            'categories' => ['Categorías', 'Inventario'],
            'product-categories' => ['Categorías de producto', 'Inventario'],
            'units' => ['Unidades de medida', 'Inventario'],
            'product-units' => ['Unidades de producto', 'Inventario'],
            'product-components' => ['Componentes / Kits', 'Inventario'],
            'warehouses' => ['Almacenes', 'Inventario'],
            'product-warehouses' => ['Stock por almacén', 'Inventario'],
            'stock-movements' => ['Movimientos de stock', 'Inventario'],
            'inventory-movements' => ['Movimientos de inventario', 'Inventario'],
            'promotions' => ['Promociones', 'Inventario'],
            'sales' => ['Ventas', 'Ventas'],
            'orders' => ['Pedidos', 'Ventas'],
            'quotations' => ['Proformas', 'Ventas'],
            'consignments' => ['Consignaciones', 'Ventas'],
            'customers' => ['Clientes', 'Ventas'],
            'purchases' => ['Compras', 'Compras'],
            'suppliers' => ['Proveedores', 'Compras'],
            'expenses' => ['Gastos', 'Gastos'],
            'expense-categories' => ['Categorías de gasto', 'Gastos'],
            'reportes' => ['Reportes', 'Finanzas'],
            'admin' => ['Acceso total', 'Sistema'],
        ];

        // prefijo de los permisos "modulo.accion" => módulo
        $prefixes = [
            'ventas' => 'sales', 'compras' => 'purchases', 'kardex' => 'stock-movements', 'productos' => 'products',
            'suscripciones' => 'subscriptions', 'consignaciones' => 'consignments',
            'gastos' => 'expenses', 'proformas' => 'quotations', 'reportes' => 'reportes',
        ];

        $crud = ['ver' => 'Ver', 'crear' => 'Crear', 'editar' => 'Editar', 'eliminar' => 'Eliminar'];
        $areaOrder = ['Seguridad', 'Configuración', 'Inventario', 'Ventas', 'Compras', 'Gastos', 'Finanzas', 'Sistema', 'Otros'];

        $groups = [];

        foreach ($names as $name) {
            if (str_contains($name, '.')) {
                [$prefix, $action] = explode('.', $name, 2);
                $module = $prefixes[$prefix] ?? $prefix;
                $label = ucfirst(str_replace('-', ' ', $action));
            } elseif (preg_match('/^(ver|crear|editar|eliminar) (.+)$/', $name, $m)) {
                $module = $m[2];
                $label = $crud[$m[1]];
            } else {
                $module = $name;
                $label = ucfirst($name);
            }

            [$moduleLabel, $area] = $modules[$module] ?? [ucfirst(str_replace('-', ' ', $module)), 'Otros'];

            $groups[$area][$module]['label'] = $moduleLabel;
            $groups[$area][$module]['permissions'][$name] = $label;
        }

        // Orden: áreas en su orden lógico; dentro del módulo, primero ver/crear/editar/eliminar
        $actionRank = array_flip(array_values($crud));
        $ordered = [];

        foreach ($areaOrder as $area) {
            if (! isset($groups[$area])) {
                continue;
            }

            foreach ($groups[$area] as $module => $data) {
                uasort($data['permissions'], fn ($a, $b) => ($actionRank[$a] ?? 99) <=> ($actionRank[$b] ?? 99));
                $ordered[$area][$module] = $data;
            }
        }

        return $ordered;
    }
}
