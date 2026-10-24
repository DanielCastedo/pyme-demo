<?php

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\AuthorizesWithSuperAdmin;

class RolePolicy
{
    use AuthorizesWithSuperAdmin;

    public function viewAny(User $user): bool
    {
        return $user->can('ver roles');
    }

    public function view(User $user, $role): bool
    {
        return $user->can('ver roles');
    }

    public function create(User $user): bool
    {
        return $user->can('crear roles');
    }

    public function update(User $user, $role): bool
    {
        return $user->can('editar roles');
    }

    public function delete(User $user, $role): bool
    {
        return $user->can('eliminar roles');
    }
}
