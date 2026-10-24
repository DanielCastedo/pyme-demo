<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;
use App\Policies\Concerns\AuthorizesWithSuperAdmin;

/**
 * Las órdenes se gestionan desde el módulo de Ventas, por eso usan los permisos "sales".
 */
class OrderPolicy
{
    use AuthorizesWithSuperAdmin;

    public function viewAny(User $user): bool
    {
        return $user->can('ver sales');
    }

    public function view(User $user, Order $order): bool
    {
        return $user->can('ver sales');
    }

    public function create(User $user): bool
    {
        return $user->can('crear sales');
    }

    // ── Pedidos (órdenes en espera) ─ permisos "orders", distintos a los de ventas ──

    public function viewAnyPedido(User $user): bool
    {
        return $user->can('ver orders');
    }

    public function viewPedido(User $user, Order $order): bool
    {
        return $user->can('ver orders');
    }

    public function createPedido(User $user): bool
    {
        return $user->can('crear orders');
    }

    public function updatePedido(User $user, Order $order): bool
    {
        return $user->can('editar orders') && $order->isPending();
    }

    public function deletePedido(User $user, Order $order): bool
    {
        return $user->can('eliminar orders') && $order->isPending();
    }

    /**
     * Confirmar un pedido es vender: exige poder vender.
     */
    public function confirmPedido(User $user, Order $order): bool
    {
        return $user->can('crear sales') && $order->isPending();
    }

    /**
     * Registrar un abono: se puede mientras la venta esté vigente y tenga saldo.
     */
    public function collect(User $user, Order $order): bool
    {
        return $user->can('ventas.cobrar') && $order->isCompleted() && $order->balance > 0;
    }

    public function cancelPayment(User $user, Order $order): bool
    {
        return $user->can('ventas.anular') && $order->isCompleted();
    }

    /**
     * Anular una venta ya cobrada revierte stock y caja: exige su permiso propio.
     */
    public function cancel(User $user, Order $order): bool
    {
        return $order->isCompleted() && $user->can('ventas.anular');
    }
}
