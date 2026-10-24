<?php

namespace App\Policies;

use App\Models\ProductComponent;
use App\Models\User;
use App\Policies\Concerns\AuthorizesWithSuperAdmin;

class ProductComponentPolicy
{
    use AuthorizesWithSuperAdmin;

    public function viewAny(User $user): bool
    {
        return $user->can('ver product-components');
    }

    public function view(User $user, ProductComponent $productComponent): bool
    {
        return $user->can('ver product-components');
    }

    public function create(User $user): bool
    {
        return $user->can('crear product-components');
    }

    public function update(User $user, ProductComponent $productComponent): bool
    {
        return $user->can('editar product-components');
    }

    public function delete(User $user, ProductComponent $productComponent): bool
    {
        return $user->can('eliminar product-components');
    }
}
