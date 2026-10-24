<?php

namespace App\Policies;

use App\Models\ExpenseCategory;
use App\Models\User;
use App\Policies\Concerns\AuthorizesWithSuperAdmin;

class ExpenseCategoryPolicy
{
    use AuthorizesWithSuperAdmin;

    public function viewAny(User $user): bool
    {
        return $user->can('ver expense-categories');
    }

    public function view(User $user, ExpenseCategory $category): bool
    {
        return $user->can('ver expense-categories');
    }

    public function create(User $user): bool
    {
        return $user->can('crear expense-categories');
    }

    public function update(User $user, ExpenseCategory $category): bool
    {
        return $user->can('editar expense-categories');
    }

    public function delete(User $user, ExpenseCategory $category): bool
    {
        return $user->can('eliminar expense-categories');
    }
}
