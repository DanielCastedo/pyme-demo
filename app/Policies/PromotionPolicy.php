<?php

namespace App\Policies;

use App\Models\Promotion;
use App\Models\User;
use App\Policies\Concerns\AuthorizesWithSuperAdmin;

class PromotionPolicy
{
    use AuthorizesWithSuperAdmin;

    public function viewAny(User $user): bool
    {
        return $user->can('ver promotions');
    }

    public function view(User $user, Promotion $promotion): bool
    {
        return $user->can('ver promotions');
    }

    public function create(User $user): bool
    {
        return $user->can('crear promotions');
    }

    public function update(User $user, Promotion $promotion): bool
    {
        return $user->can('editar promotions');
    }

    public function delete(User $user, Promotion $promotion): bool
    {
        return $user->can('eliminar promotions');
    }
}
