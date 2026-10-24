<?php

namespace App\Policies;

use App\Models\ProductUnit;
use App\Models\User;
use App\Policies\Concerns\AuthorizesWithSuperAdmin;

class ProductUnitPolicy
{
    use AuthorizesWithSuperAdmin;

    public function viewAny(User $user): bool
    {
        return $user->can('ver product-units');
    }

    public function view(User $user, ProductUnit $productUnit): bool
    {
        return $user->can('ver product-units');
    }

    public function create(User $user): bool
    {
        return $user->can('crear product-units');
    }

    public function update(User $user, ProductUnit $productUnit): bool
    {
        return $user->can('editar product-units');
    }

    public function delete(User $user, ProductUnit $productUnit): bool
    {
        return $user->can('eliminar product-units');
    }
}
