<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Warehouse;
use App\Policies\Concerns\AuthorizesWithSuperAdmin;

class WarehousePolicy
{
    use AuthorizesWithSuperAdmin;

    public function viewAny(User $user): bool
    {
        return $user->can('ver warehouses');
    }

    public function view(User $user, Warehouse $warehouse): bool
    {
        return $user->can('ver warehouses');
    }

    public function create(User $user): bool
    {
        return $user->can('crear warehouses');
    }

    public function update(User $user, Warehouse $warehouse): bool
    {
        return $user->can('editar warehouses');
    }

    public function delete(User $user, Warehouse $warehouse): bool
    {
        return $user->can('eliminar warehouses');
    }
}
