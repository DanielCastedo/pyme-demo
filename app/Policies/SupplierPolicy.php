<?php

namespace App\Policies;

use App\Models\Supplier;
use App\Models\User;
use App\Policies\Concerns\AuthorizesWithSuperAdmin;

class SupplierPolicy
{
    use AuthorizesWithSuperAdmin;

    public function viewAny(User $user): bool
    {
        return $user->can('ver suppliers');
    }

    public function view(User $user, Supplier $supplier): bool
    {
        return $user->can('ver suppliers');
    }

    public function create(User $user): bool
    {
        return $user->can('crear suppliers');
    }

    public function update(User $user, Supplier $supplier): bool
    {
        return $user->can('editar suppliers');
    }

    public function delete(User $user, Supplier $supplier): bool
    {
        return $user->can('eliminar suppliers');
    }
}
