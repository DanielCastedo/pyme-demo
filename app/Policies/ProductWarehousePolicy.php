<?php

namespace App\Policies;

use App\Models\ProductWarehouse;
use App\Models\User;
use App\Policies\Concerns\AuthorizesWithSuperAdmin;

class ProductWarehousePolicy
{
    use AuthorizesWithSuperAdmin;

    public function viewAny(User $user): bool
    {
        return $user->can('ver product-warehouses');
    }

    public function view(User $user, ProductWarehouse $productWarehouse): bool
    {
        return $user->can('ver product-warehouses');
    }

    public function create(User $user): bool
    {
        return $user->can('crear product-warehouses');
    }

    public function update(User $user, ProductWarehouse $productWarehouse): bool
    {
        return $user->can('editar product-warehouses');
    }

    public function delete(User $user, ProductWarehouse $productWarehouse): bool
    {
        return $user->can('eliminar product-warehouses');
    }
}
