<?php

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\AuthorizesWithSuperAdmin;

class UserPolicy
{
    use AuthorizesWithSuperAdmin;

    public function viewAny(User $user): bool
    {
        return $user->can('ver usuarios');
    }

    public function view(User $user, User $model): bool
    {
        return $user->can('ver usuarios');
    }

    public function create(User $user): bool
    {
        return $user->can('crear usuarios');
    }

    public function update(User $user, User $model): bool
    {
        return $user->can('editar usuarios');
    }

    public function delete(User $user, User $model): bool
    {
        return $user->can('eliminar usuarios');
    }
}
