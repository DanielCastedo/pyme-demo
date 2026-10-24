<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;
use App\Policies\Concerns\AuthorizesWithSuperAdmin;

class ProductPolicy
{
    use AuthorizesWithSuperAdmin;

    public function viewAny(User $user): bool
    {
        return $user->can('ver products');
    }

    public function view(User $user, Product $product): bool
    {
        return $user->can('ver products');
    }

    public function create(User $user): bool
    {
        return $user->can('crear products');
    }

    public function update(User $user, Product $product): bool
    {
        return $user->can('editar products');
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->can('eliminar products');
    }
}
