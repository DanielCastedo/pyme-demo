<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\User;
use App\Policies\Concerns\AuthorizesWithSuperAdmin;

class CompanyPolicy
{
    use AuthorizesWithSuperAdmin;

    public function viewAny(User $user): bool
    {
        return $user->can('ver companies');
    }

    public function view(User $user, Company $company): bool
    {
        return $user->can('ver companies');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('superadmin');
    }

    public function update(User $user, Company $company): bool
    {
        return $user->can('editar companies');
    }

    public function delete(User $user, Company $company): bool
    {
        return $user->hasRole('superadmin');
    }
}
