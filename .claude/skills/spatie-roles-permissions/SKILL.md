---
name: spatie-roles-permissions
description: Trabajar con roles y permisos de spatie/laravel-permission en este proyecto (brazil_importaciones) — agregar permisos nuevos, proteger rutas, verificar permisos en Blade/controladores, gestionar roles.
---

# Roles y Permisos (Spatie) — brazil_importaciones

Paquete: `spatie/laravel-permission` v6. Guard usado en todo el proyecto: **`web`** (siempre especifica `guard_name => 'web'`
al crear roles/permisos a mano — el proyecto lo hace explícito en varios sitios "para evitar bugs").

## Fuente de verdad de permisos: los seeders, no el código disperso

- `database/seeders/PermissionSeeder.php`: genera permisos CRUD automáticamente para una lista de `$modulos` con el
  patrón `"{accion} {modulo}"` (acciones: `ver`, `crear`, `editar`, `eliminar`), más una lista `$permisosExtra` para
  permisos puntuales con notación de punto (`ventas.anular`, `caja.abrir`, `caja.cerrar`, `caja.movimiento`,
  `reportes.ver`, `reportes.exportar`, `productos.exportar`, `productos.importar`, `kardex.exportar`, `kardex.importar`,
  `compras.anular`, `suscripciones.aprobar`).
- `database/seeders/RolesSeeder.php`: crea los roles (`admin`, `superadmin`) y les asigna permisos con `syncPermissions()`.
  `superadmin` siempre tiene todos los permisos.

**Cuando agregues un módulo/recurso nuevo (migración + modelo + controlador + rutas), agrega también su nombre a
`$modulos` en `PermissionSeeder.php`** para que se generen sus 4 permisos CRUD, y decide si `admin` debe recibirlos en
`RolesSeeder.php`. Después corre:

```bash
php artisan db:seed --class=PermissionSeeder
php artisan db:seed --class=RolesSeeder
```

No crees permisos sueltos con `Permission::create()` en un controlador o migración — siempre desde el seeder.

## Proteger rutas (patrón usado en `routes/web.php`)

```php
Route::middleware(['permission:ver products'])->group(function () {
    Route::resource('products', ProductController::class)->except(['show']);
});

Route::middleware(['permission:caja.abrir'])->group(function () {
    Route::post('cash-registers/open', [CashRegisterController::class, 'open']);
});

Route::middleware(['role:superadmin'])->group(function () {
    Route::resource('roles', RoleController::class);
});
```

Reglas: agrupa por permiso/rol con `Route::middleware([...])->group(...)`, no pongas `->middleware()` ruta por ruta si
varias comparten el mismo permiso. Usa `permission:` para acciones de un módulo normal, `role:` solo para pantallas de
administración pura del sistema (roles, permisos, superadmin).

## Verificar permisos en código

- Controlador / lógica: `$user->can('editar products')`, `$user->hasRole('admin')`, `$user->hasAnyRole(['admin', 'superadmin'])`.
- Blade: `@can('ver reportes')` ... `@endcan`. Ejemplo real del proyecto en el sidebar
  (`resources/views/components/app/sidebar.blade.php`): `$user->can($menu->permiso_requerido)` para ocultar ítems de menú
  dinámicamente según un campo de la BD.
- Nunca hardcodees `if ($user->id === 1)` ni compares nombres de rol con `==` fuera de `hasRole()` — usa siempre la API
  de Spatie.

## Gestión de roles/permisos vía UI (ya implementado)

- `RoleController` (`app/Http/Controllers/RoleController.php`): CRUD de roles, con `syncPermissions($request->permissions)`
  al crear/editar. Siempre pasa `guard_name => 'web'` explícito al crear un Role a mano.
- `PermisosController`: CRUD de permisos individuales (nota: nombres de variable en español, `$permiso`/`$permisos`, es
  el estilo ya establecido en ese controlador — mantenlo si lo editas).
- `RolePermisosController`: pantalla dedicada a asignar permisos a un rol ya existente.
- Cachea `Permission::all()` con `cache()->remember('permisos.all', 3600, fn () => Permission::all())` cuando se lista
  para selects, como en `RoleController@index` — evita repetir la query si vas a listar permisos en varias vistas.
- Si cambias permisos/roles desde un seeder o tinker, recuerda limpiar el caché de Spatie:
  `app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();` (ver el inicio de `PermissionSeeder`).

## Al agregar un middleware de permiso nuevo en una ruta

Verifica primero que el permiso exista en `PermissionSeeder.php` (o agrégalo) — un `middleware(['permission:x'])` con un
permiso que no existe en la tabla `permissions` lanzará `UnauthorizedException` para todos, incluido `superadmin`, hasta
que se sembre.
