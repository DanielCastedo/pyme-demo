<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;
use App\Policies\Concerns\AuthorizesWithSuperAdmin;

class CategoryPolicy
{
    use AuthorizesWithSuperAdmin;

    public function viewAny(User $user): bool
    {
        return $user->can('ver categories');
    }

    public function view(User $user, Category $category): bool
    {
        return $user->can('ver categories');
    }

    public function create(User $user): bool
    {
        return $user->can('crear categories');
    }

    public function update(User $user, Category $category): bool
    {
        return $user->can('editar categories');
    }

    public function delete(User $user, Category $category): bool
    {
        return $user->can('eliminar categories');
    }
}
