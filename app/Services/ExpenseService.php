<?php

namespace App\Services;

use App\Support\Bc;
use App\Models\Cashbox;
use App\Models\Expense;
use App\Models\Type;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Gastos: cada gasto saca su monto de una caja con una transacción tipo Gastos. Editar un gasto rehace esa
 * transacción (anula la anterior y crea otra) para que la caja siempre cuadre; anularlo devuelve el dinero.
 */
class ExpenseService
{
    public function __construct(private TransactionService $transactions) {}

    /**
     * @param  array{date: mixed, concept: string, description?: ?string, amount: float|string, receipt_number?: ?string, expense_category_id?: ?int, supplier_id?: ?int, payment_id: int, cashbox_id: int}  $data
     *
     * @throws \RuntimeException si la caja no tiene fondos suficientes.
     */
    public function register(array $data, User $user): Expense
    {
        return DB::transaction(function () use ($data, $user) {
            $cashbox = Cashbox::findOrFail($data['cashbox_id']);

            $expense = Expense::create($this->fields($data) + [
                'status' => Expense::STATUS_PAID,
                'branche_id' => $cashbox->branche_id,
                'user_id' => $user->id,
            ]);

            $expense->update(['transaction_id' => $this->pay($expense, $data, $user)->id]);

            return $expense->refresh();
        });
    }

    /**
     * @throws \RuntimeException si el gasto está anulado o la caja no alcanza.
     */
    public function update(Expense $expense, array $data, User $user): Expense
    {
        return DB::transaction(function () use ($expense, $data, $user) {
            $expense = Expense::lockForUpdate()->findOrFail($expense->id);

            if ($expense->isCanceled()) {
                throw new \RuntimeException('Un gasto anulado no se puede editar.');
            }

            // Se devuelve el dinero anterior antes de cobrar el nuevo monto: así el saldo de la caja se valida bien.
            if ($expense->transaction && ! $expense->transaction->canceled) {
                $this->transactions->cancel($expense->transaction);
            }

            $cashbox = Cashbox::findOrFail($data['cashbox_id']);
            $expense->update($this->fields($data) + ['branche_id' => $cashbox->branche_id]);
            $expense->update(['transaction_id' => $this->pay($expense, $data, $user)->id]);

            return $expense->refresh();
        });
    }

    /**
     * Anula el gasto: el dinero vuelve a la caja y el registro queda como historial.
     *
     * @throws \RuntimeException
     */
    public function cancel(Expense $expense): Expense
    {
        return DB::transaction(function () use ($expense) {
            $expense = Expense::lockForUpdate()->findOrFail($expense->id);

            if ($expense->isCanceled()) {
                throw new \RuntimeException('Este gasto ya está anulado.');
            }

            if ($expense->transaction && ! $expense->transaction->canceled) {
                $this->transactions->cancel($expense->transaction);
            }

            $expense->update(['status' => Expense::STATUS_CANCELED]);

            return $expense->refresh();
        });
    }

    private function fields(array $data): array
    {
        return [
            'date' => $data['date'] ?? now()->toDateString(),
            'concept' => $data['concept'],
            'description' => $data['description'] ?? null,
            'amount' => Bc::round($data['amount'], 2),
            'receipt_number' => $data['receipt_number'] ?? null,
            'expense_category_id' => $data['expense_category_id'] ?? null,
            'supplier_id' => $data['supplier_id'] ?? null,
        ];
    }

    private function pay(Expense $expense, array $data, User $user)
    {
        return $this->transactions->register([
            'type_id' => Type::where('code', 'GTO')->firstOrFail()->id,
            'payment_id' => $data['payment_id'],
            'cashbox_id' => $data['cashbox_id'],
            'amount' => $expense->amount,
            'memo' => "Gasto #{$expense->id}: {$expense->concept}",
            'date' => now(),
            'user_id' => $user->id,
        ]);
    }
}
