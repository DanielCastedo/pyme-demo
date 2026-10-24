<?php

namespace App\Policies\Concerns;

use App\Models\User;

/**
 * Da bypass total a cualquier Policy que la use: si el usuario tiene el permiso
 * "admin" (reservado para el rol superadmin, ver RolesSeeder), todas las
 * habilidades del policy (viewAny, view, create, update, delete, etc.) se
 * autorizan sin evaluar sus reglas específicas.
 *
 * Laravel llama automáticamente al método before() de un policy antes que
 * cualquier otra habilidad; devolver null deja que la habilidad puntual decida
 * normalmente.
 */
trait AuthorizesWithSuperAdmin
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->can('admin')) {
            return true;
        }

        return null;
    }
}
