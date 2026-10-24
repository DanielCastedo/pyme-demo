<?php

namespace App\Policies;

use App\Models\Quotation;
use App\Models\User;
use App\Policies\Concerns\AuthorizesWithSuperAdmin;

class QuotationPolicy
{
    use AuthorizesWithSuperAdmin;

    public function before(User $user, string $ability): ?bool
    {
        // Si la habilidad es update o delete, no otorgar bypass automático a admin si la proforma ya fue aprobada
        if (in_array($ability, ['update', 'delete'])) {
            return null;
        }

        if ($user->can('admin')) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('ver quotations');
    }

    public function view(User $user, Quotation $quotation): bool
    {
        return $user->can('ver quotations');
    }

    public function create(User $user): bool
    {
        return $user->can('crear quotations');
    }

    public function update(User $user, Quotation $quotation): bool
    {
        // Una vez aprobada o rechazada, una proforma queda inmutable
        if (in_array($quotation->status, ['approved', 'rejected'])) {
            return false;
        }

        return $user->can('admin') || $user->can('editar quotations');
    }

    /**
     * Aprobar, rechazar o cambiar el estado de una proforma. Es una modificación: exige "editar quotations"
     * (el estado en sí lo valida el controlador: una proforma aprobada no vuelve atrás).
     */
    public function changeStatus(User $user, Quotation $quotation): bool
    {
        return $user->can('admin') || $user->can('editar quotations');
    }

    public function delete(User $user, Quotation $quotation): bool
    {
        // Una vez aprobada, no se puede eliminar
        if ($quotation->status === 'approved') {
            return false;
        }

        return $user->can('admin') || $user->can('eliminar quotations');
    }
}
