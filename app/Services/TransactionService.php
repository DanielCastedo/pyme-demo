<?php

namespace App\Services;

use App\Support\Bc;
use App\Models\Cashbox;
use App\Models\PaymentMethod;
use App\Models\Transaction;
use App\Models\Type;
use Illuminate\Support\Facades\DB;

/**
 * Único punto por el que se mueve dinero de una caja. Reglas:
 *  - tipo con signo "+" suma a la caja; signo "-" resta.
 *  - `balance` es el saldo total; `cash` es solo el efectivo físico (se toca únicamente si el
 *    método de pago es efectivo). Saldo digital = balance - cash.
 *  - una caja nunca queda con efectivo ni saldo digital negativos.
 */
class TransactionService
{
    /**
     * @param  array{type_id: int, payment_id: int, cashbox_id: int, amount: float|string, memo?: string|null, date?: mixed, user_id: int, related_transaction_id?: int|null}  $data
     *
     * @throws \RuntimeException
     */
    public function register(array $data): Transaction
    {
        return DB::transaction(function () use ($data) {
            $type = Type::findOrFail($data['type_id']);
            $payment = PaymentMethod::findOrFail($data['payment_id']);
            $amount = Bc::round($data['amount'], 2);

            if (! in_array($type->sign, ['+', '-'], true)) {
                throw new \RuntimeException("El tipo \"{$type->name}\" no mueve dinero de caja.");
            }

            if (Bc::lte($amount, 0)) {
                throw new \RuntimeException('El monto debe ser mayor a 0.');
            }

            $cashbox = Cashbox::lockForUpdate()->findOrFail($data['cashbox_id']);

            $this->applyToCashbox($cashbox, $payment, $type->sign === '+' ? $amount : Bc::sub(0, $amount));

            return Transaction::create([
                'number' => $this->nextNumber(),
                'date' => $data['date'] ?? now(),
                'amount' => $amount,
                'canceled' => false,
                'memo' => $data['memo'] ?? null,
                'type_id' => $type->id,
                'payment_id' => $payment->id,
                'cashbox_id' => $cashbox->id,
                'branche_id' => $cashbox->branche_id,
                'user_id' => $data['user_id'],
                'related_transaction_id' => $data['related_transaction_id'] ?? null,
            ]);
        });
    }

    /**
     * Anula una transacción: revierte su efecto en la caja y la marca como anulada (no se borra,
     * para conservar el historial).
     *
     * @throws \RuntimeException
     */
    public function cancel(Transaction $transaction): Transaction
    {
        return DB::transaction(function () use ($transaction) {
            $transaction = Transaction::with('type', 'paymentMethod')->lockForUpdate()->findOrFail($transaction->id);

            if ($transaction->canceled) {
                throw new \RuntimeException('Esta transacción ya está anulada.');
            }

            $cashbox = Cashbox::lockForUpdate()->findOrFail($transaction->cashbox_id);
            $amount = Bc::num($transaction->amount);

            $this->applyToCashbox($cashbox, $transaction->paymentMethod, $transaction->type->sign === '+' ? Bc::sub(0, $amount) : $amount);

            $transaction->update(['canceled' => true]);

            return $transaction;
        });
    }

    /**
     * Aplica un delta (positivo o negativo) a la caja ya bloqueada, validando que no quede en negativo.
     */
    private function applyToCashbox(Cashbox $cashbox, ?PaymentMethod $payment, int|float|string $delta): void
    {
        $newBalance = Bc::round(Bc::add($cashbox->balance, $delta), 2);
        $newCash = Bc::num($cashbox->cash);

        if ($payment && $payment->is_cash) {
            $newCash = Bc::round(Bc::add($newCash, $delta), 2);
        }

        if (Bc::lt($newCash, 0)) {
            throw new \RuntimeException(sprintf(
                'Efectivo insuficiente en "%s": tiene %s y el movimiento requiere %s.',
                $cashbox->name,
                number_format((float) $cashbox->cash, 2),
                number_format((float) Bc::abs($delta), 2)
            ));
        }

        if (Bc::lt(Bc::round(Bc::sub($newBalance, $newCash), 2), 0)) {
            throw new \RuntimeException(sprintf(
                'Saldo digital insuficiente en "%s": tiene %s y el movimiento requiere %s.',
                $cashbox->name,
                number_format((float) Bc::sub($cashbox->balance, $cashbox->cash), 2),
                number_format((float) Bc::abs($delta), 2)
            ));
        }

        $cashbox->update(['balance' => $newBalance, 'cash' => $newCash]);
    }

    /**
     * Siguiente número correlativo (TRX-0001...). Se llama dentro de la transacción de BD; el
     * índice único de `number` protege ante cualquier carrera.
     */
    private function nextNumber(): string
    {
        $last = Transaction::lockForUpdate()->orderByDesc('id')->value('number');
        $next = $last ? ((int) preg_replace('/\D/', '', $last)) + 1 : 1;

        return 'TRX-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
