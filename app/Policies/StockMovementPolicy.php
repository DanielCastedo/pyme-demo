<?php

namespace App\Policies;

use App\Models\StockMovement;
use App\Models\User;
use App\Policies\Concerns\AuthorizesWithSuperAdmin;

class StockMovementPolicy
{
    use AuthorizesWithSuperAdmin;

    public function viewAny(User $user): bool
    {
        return $user->can('ver stock-movements');
    }

    public function view(User $user, StockMovement $stockMovement): bool
    {
        return $user->can('ver stock-movements');
    }

    public function create(User $user): bool
    {
        return $user->can('crear stock-movements');
    }

    public function update(User $user, StockMovement $stockMovement): bool
    {
        return $user->can('editar stock-movements');
    }

    public function delete(User $user, StockMovement $stockMovement): bool
    {
        return $user->can('eliminar stock-movements');
    }
}
