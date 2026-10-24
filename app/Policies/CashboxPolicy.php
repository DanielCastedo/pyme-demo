<?php

namespace App\Policies;

use App\Models\Cashbox;
use App\Models\User;
use App\Policies\Concerns\AuthorizesWithSuperAdmin;

class CashboxPolicy
{
    use AuthorizesWithSuperAdmin;

    public function viewAny(User $user): bool
    {
        return $user->can('ver cashboxes');
    }

    public function view(User $user, Cashbox $cashbox): bool
    {
        return $user->can('ver cashboxes');
    }

    public function create(User $user): bool
    {
        return $user->can('crear cashboxes');
    }

    public function update(User $user, Cashbox $cashbox): bool
    {
        return $user->can('editar cashboxes');
    }

    public function delete(User $user, Cashbox $cashbox): bool
    {
        return $user->can('eliminar cashboxes');
    }
}
