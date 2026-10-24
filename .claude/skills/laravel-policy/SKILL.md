---
name: laravel-policy
description: Crear o registrar Policies de autorización en este proyecto Laravel (brazil_importaciones), combinadas con roles y permisos de Spatie. Usar cuando se pida autorización a nivel de modelo/registro (no solo a nivel de ruta).
---

# Policies + Spatie Permission — brazil_importaciones

## ⚠️ Contexto importante: `app/Policies/` tiene código muerto de otro dominio

Ya existen archivos en `app/Policies/` (`StudentPolicy`, `FacultyPolicy`, `CareerPolicy`, `PackagePolicy`,
`FileGroupPolicy`, `SubjectPolicy`, etc.) que pertenecen a un scaffold anterior de un sistema **universitario**, sin
relación con el dominio actual (ferretería/importadora: productos, ventas, compras, clientes, caja, consignaciones).
`app/Providers/AuthServiceProvider.php` tiene `$policies = []` vacío — **ninguna policy está registrada ni activa hoy**.

No uses esos archivos como plantilla ni los extiendas. Si el usuario pide crear la primera policy real del proyecto,
puedes preguntarle si quiere que se borren los archivos huérfanos, pero no los borres sin confirmar.

## Cómo se autoriza hoy en este proyecto (y cuándo SÍ hace falta una Policy)

La autorización actual vive casi toda en `routes/web.php` con middleware de Spatie:

```php
Route::middleware(['permission:ver products'])->group(function () { ... });
Route::middleware(['role:superadmin'])->group(function () { ... });
```

Esto alcanza para "¿puede este usuario ver/crear/editar/eliminar este tipo de recurso?". **Crea una Policy solo cuando
la regla depende del registro específico**, no solo del tipo de recurso — por ejemplo: "un vendedor solo puede anular
sus propias ventas", "un usuario no puede editar una compra ya recibida". Para reglas puramente de tipo-de-recurso,
usa el middleware de ruta, no una policy.

## Estructura de una Policy combinada con permisos de Spatie

```php
<?php

namespace App\Policies;

use App\Models\Sale;
use App\Models\User;

class SalePolicy
{
    public function update(User $user, Sale $sale): bool
    {
        if (! $user->can('editar sales')) {
            return false;
        }

        return $sale->user_id === $user->id || $user->hasRole('admin', 'superadmin');
    }

    public function anular(User $user, Sale $sale): bool
    {
        return $user->can('ventas.anular') && $sale->status !== 'anulada';
    }
}
```

Registrar en `app/Providers/AuthServiceProvider.php`:

```php
protected $policies = [
    Sale::class => \App\Policies\SalePolicy::class,
];
```

Uso en el controlador:

```php
$this->authorize('anular', $sale);
```

O en Blade:

```blade
@can('anular', $sale)
    ...
@endcan
```

## Bypass total para superadmin: trait `AuthorizesWithSuperAdmin`

Existe un permiso especial `admin` (sembrado en `PermissionSeeder`, asignado únicamente al rol `superadmin` vía el
`syncPermissions($todosLosPermisos)` de `RolesSeeder`) que salta cualquier policy. El trait
`App\Policies\Concerns\AuthorizesWithSuperAdmin` implementa el método `before()` que Laravel invoca automáticamente
antes que cualquier habilidad del policy:

```php
use App\Policies\Concerns\AuthorizesWithSuperAdmin;

class TypePolicy
{
    use AuthorizesWithSuperAdmin;

    // viewAny/view/create/update/delete normales — no necesitan comprobar superadmin manualmente
}
```

**Toda Policy nueva del proyecto debe usar este trait** (`use AuthorizesWithSuperAdmin;` dentro de la clase) para que
el rol `superadmin` nunca quede bloqueado por una regla pensada para roles operativos. No repitas `$user->can('admin')`
a mano dentro de cada método de habilidad — el trait ya lo cubre vía `before()`. Ver `App\Policies\TypePolicy` como
ejemplo real ya cableado.

## Reglas del proyecto

- Los nombres de permisos deben coincidir exactamente con los sembrados en `database/seeders/PermissionSeeder.php`:
  patrón CRUD `"{accion} {modulo}"` (ej. `ver products`, `crear sales`, `eliminar customers`) o permisos puntuales con
  punto (`ventas.anular`, `caja.abrir`, `reportes.exportar`). Antes de inventar un nombre de permiso nuevo, revisa ese
  seeder — si el permiso no existe, agrégalo ahí también (y a `RolesSeeder.php` si algún rol debe tenerlo).
- Dentro de la policy, usa `$user->can('permiso')` / `$user->hasRole('rol')` (vienen del trait `HasRoles` en `User`),
  no repitas lógica de permisos con arrays hardcodeados.
- No crees una Policy vacía "por si acaso" para cada modelo nuevo — solo cuando hay una regla real de autorización a
  nivel de registro que el middleware de ruta no puede expresar.
- Registra siempre la policy nueva en `AuthServiceProvider::$policies` — sin eso, `$this->authorize()` y `@can` a nivel
  de modelo no la usarán.
