<?php

namespace App\Services;

use App\Support\Bc;
use App\Models\Cashbox;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\PaymentMethod;
use App\Models\Type;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Cobros de una venta: el pago al contado, el anticipo de una venta a crédito y cada abono posterior.
 * Cada cobro mueve dinero en la caja (transacción VTA, ver TransactionService) y queda enlazado a la
 * venta en order_payments; el total cobrado se lleva en orders.paid_amount.
 */
class OrderPaymentService
{
    public function __construct(private readonly TransactionService $transactions) {}

    /**
     * @throws \RuntimeException si la venta no admite el cobro, el monto pasa del saldo o la caja no corresponde.
     */
    public function collect(Order $order, int|float|string $amount, PaymentMethod $payment, Cashbox $cashbox, User $user, ?string $receiptPhoto = null): OrderPayment
    {
        return DB::transaction(function () use ($order, $amount, $payment, $cashbox, $user, $receiptPhoto) {
            $order = Order::with('customer')->lockForUpdate()->findOrFail($order->id);
            $amount = Bc::round($amount, 2);

            if (! $order->isCompleted()) {
                throw new \RuntimeException('Solo se puede cobrar una venta vigente.');
            }

            if (Bc::lte($amount, 0)) {
                throw new \RuntimeException('El monto a cobrar debe ser mayor a 0.');
            }

            if (Bc::gt($amount, Bc::add($order->balance, '0.004'))) {
                throw new \RuntimeException('El monto ('.number_format($amount, 2).') supera el saldo de la venta ('.number_format($order->balance, 2).').');
            }

            if ((int) $cashbox->branche_id !== (int) $order->branche_id) {
                throw new \RuntimeException('La caja seleccionada no pertenece a la sucursal de la venta.');
            }

            $transaction = $this->transactions->register([
                'type_id' => Type::where('code', 'VTA')->firstOrFail()->id,
                'payment_id' => $payment->id,
                'cashbox_id' => $cashbox->id,
                'amount' => $amount,
                'memo' => ($order->isCredit() ? "Cobro de venta #{$order->id}" : "Venta #{$order->id}").($order->customer ? " — {$order->customer->name}" : ''),
                'user_id' => $user->id,
            ]);

            $orderPayment = $order->payments()->create([
                'transaction_id' => $transaction->id,
                'user_id' => $user->id,
                'amount' => $amount,
                'receipt_photo' => $receiptPhoto,
            ]);

            $order->update(['paid_amount' => Bc::round(Bc::add($order->paid_amount, $amount), 2)]);

            return $orderPayment;
        });
    }

    /**
     * Anula un cobro puntual (por ejemplo un abono mal cargado): revierte el dinero en la caja y devuelve
     * ese monto al saldo de la venta.
     *
     * @throws \RuntimeException
     */
    public function cancelPayment(OrderPayment $payment): void
    {
        DB::transaction(function () use ($payment) {
            $payment = OrderPayment::lockForUpdate()->findOrFail($payment->id);
            $order = Order::lockForUpdate()->findOrFail($payment->order_id);

            if ($payment->canceled) {
                throw new \RuntimeException('Este cobro ya está anulado.');
            }

            if ($order->isCanceled()) {
                throw new \RuntimeException('La venta está anulada.');
            }

            $this->revert($payment);

            $order->update(['paid_amount' => Bc::max(0, Bc::round(Bc::sub($order->paid_amount, $payment->amount), 2))]);
        });
    }

    /**
     * Anula todos los cobros vigentes de una venta (se usa al anular la venta entera).
     *
     * @throws \RuntimeException si la caja no tiene fondos para devolver algún cobro.
     */
    public function cancelAll(Order $order): void
    {
        foreach ($order->payments()->where('canceled', false)->lockForUpdate()->get() as $payment) {
            $this->revert($payment);
        }

        $order->update(['paid_amount' => 0]);
    }

    private function revert(OrderPayment $payment): void
    {
        $this->transactions->cancel($payment->transaction);
        $payment->update(['canceled' => true]);
    }
}
