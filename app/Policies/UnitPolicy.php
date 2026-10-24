<?php

namespace App\Policies;

use App\Models\Unit;
use App\Models\User;
use App\Policies\Concerns\AuthorizesWithSuperAdmin;

class UnitPolicy
{
    use AuthorizesWithSuperAdmin;

    public function viewAny(User $user): bool
    {
        return $user->can('ver units');
    }

    public function view(User $user, Unit $unit): bool
    {
        return $user->can('ver units');
    }

    public function create(User $user): bool
    {
        return $user->can('crear units');
    }

    public function update(User $user, Unit $unit): bool
    {
        return $user->can('editar units');
    }

    public function delete(User $user, Unit $unit): bool
    {
        return $user->can('eliminar units');
    }
}
