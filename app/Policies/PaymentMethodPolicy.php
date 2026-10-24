<?php

namespace App\Policies;

use App\Models\PaymentMethod;
use App\Models\User;
use App\Policies\Concerns\AuthorizesWithSuperAdmin;

class PaymentMethodPolicy
{
    use AuthorizesWithSuperAdmin;

    public function viewAny(User $user): bool
    {
        return $user->can('ver payments');
    }

    public function view(User $user, PaymentMethod $paymentMethod): bool
    {
        return $user->can('ver payments');
    }

    public function create(User $user): bool
    {
        return $user->can('crear payments');
    }

    public function update(User $user, PaymentMethod $paymentMethod): bool
    {
        return $user->can('editar payments');
    }

    public function delete(User $user, PaymentMethod $paymentMethod): bool
    {
        return $user->can('eliminar payments');
    }
}
