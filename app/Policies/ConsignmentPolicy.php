<?php

namespace App\Policies;

use App\Models\Consignment;
use App\Models\User;
use App\Policies\Concerns\AuthorizesWithSuperAdmin;

class ConsignmentPolicy
{
    use AuthorizesWithSuperAdmin;

    public function viewAny(User $user): bool
    {
        return $user->can('ver consignments');
    }

    public function view(User $user, Consignment $consignment): bool
    {
        return $user->can('ver consignments');
    }

    public function create(User $user): bool
    {
        return $user->can('crear consignments');
    }

    /**
     * Liquidar (registrar lo vendido y lo devuelto) genera una venta y movimientos de stock: permiso propio.
     */
    public function settle(User $user, Consignment $consignment): bool
    {
        return $user->can('consignaciones.liquidar') && $consignment->isOpen();
    }

    public function cancel(User $user, Consignment $consignment): bool
    {
        return $user->can('eliminar consignments') && $consignment->isOpen();
    }
}
