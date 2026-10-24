<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;
use App\Policies\Concerns\AuthorizesWithSuperAdmin;

class CustomerPolicy
{
    use AuthorizesWithSuperAdmin;

    public function viewAny(User $user): bool
    {
        return $user->can('ver customers');
    }

    public function view(User $user, Customer $customer): bool
    {
        return $user->can('ver customers');
    }

    public function create(User $user): bool
    {
        return $user->can('crear customers');
    }

    public function update(User $user, Customer $customer): bool
    {
        return $user->can('editar customers');
    }

    public function delete(User $user, Customer $customer): bool
    {
        return $user->can('eliminar customers');
    }
}
