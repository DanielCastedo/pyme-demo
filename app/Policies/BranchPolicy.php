<?php

namespace App\Policies;

use App\Models\Branch;
use App\Models\User;
use App\Policies\Concerns\AuthorizesWithSuperAdmin;

class BranchPolicy
{
    use AuthorizesWithSuperAdmin;

    public function viewAny(User $user): bool
    {
        return $user->can('ver branches');
    }

    public function view(User $user, Branch $branch): bool
    {
        return $user->can('ver branches');
    }

    public function create(User $user): bool
    {
        return $user->can('crear branches');
    }

    public function update(User $user, Branch $branch): bool
    {
        return $user->can('editar branches');
    }

    public function delete(User $user, Branch $branch): bool
    {
        return $user->can('eliminar branches');
    }
}
