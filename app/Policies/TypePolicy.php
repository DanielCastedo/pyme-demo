<?php

namespace App\Policies;

use App\Models\Type;
use App\Models\User;
use App\Policies\Concerns\AuthorizesWithSuperAdmin;

class TypePolicy
{
    use AuthorizesWithSuperAdmin;

    public function viewAny(User $user): bool
    {
        return $user->can('ver types');
    }

    public function view(User $user, Type $type): bool
    {
        return $user->can('ver types');
    }

    public function create(User $user): bool
    {
        return $user->can('crear types');
    }

    public function update(User $user, Type $type): bool
    {
        return $user->can('editar types');
    }

    public function delete(User $user, Type $type): bool
    {
        return $user->can('eliminar types');
    }
}
