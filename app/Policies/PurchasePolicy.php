<?php

namespace App\Policies;

use App\Models\Purchase;
use App\Models\User;
use App\Policies\Concerns\AuthorizesWithSuperAdmin;

class PurchasePolicy
{
    use AuthorizesWithSuperAdmin;

    public function viewAny(User $user): bool
    {
        return $user->can('ver purchases');
    }

    public function view(User $user, Purchase $purchase): bool
    {
        return $user->can('ver purchases');
    }

    public function create(User $user): bool
    {
        return $user->can('crear purchases');
    }

    /**
     * "Editar" una compra solo permite AGREGAR productos (lo ya comprado no se modifica): no se puede en una anulada.
     */
    public function update(User $user, Purchase $purchase): bool
    {
        return $user->can('editar purchases') && ! $purchase->isCanceled();
    }

    /**
     * Anular baja el stock y devuelve el dinero a la caja: permiso propio.
     */
    public function cancel(User $user, Purchase $purchase): bool
    {
        return $user->can('compras.anular') && ! $purchase->isCanceled();
    }

    /**
     * Registrar un pago (abono): se puede mientras la compra sea a crédito, esté vigente y tenga saldo.
     */
    public function pay(User $user, Purchase $purchase): bool
    {
        return $user->can('compras.pagar') && $purchase->isCredit() && ! $purchase->isCanceled() && $purchase->balance > 0;
    }

    public function cancelPayment(User $user, Purchase $purchase): bool
    {
        return $user->can('compras.anular') && ! $purchase->isCanceled();
    }
}
