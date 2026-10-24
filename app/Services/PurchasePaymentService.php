<?php

namespace App\Services;

use App\Support\Bc;
use App\Models\Cashbox;
use App\Models\PaymentMethod;
use App\Models\Purchase;
use App\Models\PurchasePayment;
use App\Models\Type;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Pagos de una compra a crédito: el anticipo al registrarla y cada abono posterior. Cada pago mueve
 * dinero en la caja (transacción COM, ver TransactionService) y queda enlazado a la compra en
 * purchase_payments; el total pagado se lleva en purchases.paid_amount.
 */
class PurchasePaymentService
{
    public function __construct(private readonly TransactionService $transactions) {}

    /**
     * @throws \RuntimeException si la compra no admite el pago, el monto pasa del saldo o la caja no corresponde.
     */
    public function pay(Purchase $purchase, int|float|string $amount, PaymentMethod $payment, Cashbox $cashbox, User $user): PurchasePayment
    {
        return DB::transaction(function () use ($purchase, $amount, $payment, $cashbox, $user) {
            $purchase = Purchase::with('supplier')->lockForUpdate()->findOrFail($purchase->id);
            $amount = Bc::round($amount, 2);

            if (! $purchase->isCredit()) {
                throw new \RuntimeException('Solo se puede registrar un pago sobre una compra a crédito.');
            }

            if ($purchase->isCanceled()) {
                throw new \RuntimeException('La compra está anulada.');
            }

            if (Bc::lte($amount, 0)) {
                throw new \RuntimeException('El monto a pagar debe ser mayor a 0.');
            }

            if (Bc::gt($amount, Bc::add($purchase->balance, '0.004'))) {
                throw new \RuntimeException('El monto ('.number_format($amount, 2).') supera el saldo de la compra ('.number_format($purchase->balance, 2).').');
            }

            if ((int) $cashbox->branche_id !== (int) $purchase->branche_id) {
                throw new \RuntimeException('La caja seleccionada no pertenece a la sucursal de la compra.');
            }

            $transaction = $this->transactions->register([
                'type_id' => Type::where('code', 'COM')->firstOrFail()->id,
                'payment_id' => $payment->id,
                'cashbox_id' => $cashbox->id,
                'amount' => $amount,
                'memo' => "Pago de compra #{$purchase->id}".($purchase->supplier ? " — {$purchase->supplier->name}" : ''),
                'user_id' => $user->id,
            ]);

            $purchasePayment = $purchase->payments()->create([
                'transaction_id' => $transaction->id,
                'user_id' => $user->id,
                'amount' => $amount,
            ]);

            $purchase->update(['paid_amount' => Bc::round(Bc::add($purchase->paid_amount, $amount), 2)]);

            return $purchasePayment;
        });
    }

    /**
     * Anula un pago puntual (por ejemplo un abono mal cargado): revierte el dinero en la caja y devuelve
     * ese monto al saldo de la compra.
     *
     * @throws \RuntimeException
     */
    public function cancelPayment(PurchasePayment $payment): void
    {
        DB::transaction(function () use ($payment) {
            $payment = PurchasePayment::lockForUpdate()->findOrFail($payment->id);
            $purchase = Purchase::lockForUpdate()->findOrFail($payment->purchase_id);

            if ($payment->canceled) {
                throw new \RuntimeException('Este pago ya está anulado.');
            }

            if ($purchase->isCanceled()) {
                throw new \RuntimeException('La compra está anulada.');
            }

            $this->revert($payment);

            $purchase->update(['paid_amount' => Bc::max(0, Bc::round(Bc::sub($purchase->paid_amount, $payment->amount), 2))]);
        });
    }

    /**
     * Anula todos los pagos vigentes de una compra (se usa al anular la compra entera).
     *
     * @throws \RuntimeException si la caja no tiene fondos para devolver algún pago.
     */
    public function cancelAll(Purchase $purchase): void
    {
        foreach ($purchase->payments()->where('canceled', false)->lockForUpdate()->get() as $payment) {
            $this->revert($payment);
        }

        $purchase->update(['paid_amount' => 0]);
    }

    private function revert(PurchasePayment $payment): void
    {
        $this->transactions->cancel($payment->transaction);
        $payment->update(['canceled' => true]);
    }
}
