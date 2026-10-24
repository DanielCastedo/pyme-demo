<?php

namespace App\Policies;

use App\Models\Expense;
use App\Models\User;
use App\Policies\Concerns\AuthorizesWithSuperAdmin;

class ExpensePolicy
{
    use AuthorizesWithSuperAdmin;

    public function viewAny(User $user): bool
    {
        return $user->can('ver expenses');
    }

    public function view(User $user, Expense $expense): bool
    {
        return $user->can('ver expenses');
    }

    public function create(User $user): bool
    {
        return $user->can('crear expenses');
    }

    public function update(User $user, Expense $expense): bool
    {
        return $user->can('editar expenses') && ! $expense->isCanceled();
    }

    /**
     * Eliminar anula el gasto (el dinero vuelve a la caja) y, si ya estaba anulado, borra el registro.
     */
    public function delete(User $user, Expense $expense): bool
    {
        return $user->can('eliminar expenses');
    }
}
