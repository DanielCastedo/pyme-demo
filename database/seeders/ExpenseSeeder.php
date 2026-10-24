<?php

namespace Database\Seeders;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Services\ExpenseService;
use Database\Seeders\Concerns\UsesDemoData;
use Illuminate\Database\Seeder;

/**
 * Dos gastos pagados desde la caja (cada uno resta su monto del efectivo de la caja).
 */
class ExpenseSeeder extends Seeder
{
    use UsesDemoData;

    public function run(ExpenseService $expenses): void
    {
        if (Expense::exists()) {
            $this->command->info('Ya existen gastos, se omite ExpenseSeeder.');

            return;
        }

        foreach ([
            ['Pago de energía eléctrica', 250, 'Servicios básicos (luz, agua, internet)', 12, 'FAC-948201'],
            ['Flete de mercadería', 150, 'Transporte y fletes', 8, 'GUIA-882'],
        ] as [$concept, $amount, $category, $daysAgo, $receipt]) {
            $expenses->register([
                'date' => now()->subDays($daysAgo)->toDateString(),
                'concept' => $concept,
                'amount' => $amount,
                'receipt_number' => $receipt,
                'expense_category_id' => ExpenseCategory::where('name', $category)->firstOrFail()->id,
                'payment_id' => $this->cash()->id,
                'cashbox_id' => $this->cashbox()->id,
            ], $this->admin());
        }

        $this->command->info('✅ Gastos de ejemplo registrados (Bs 400 en total).');
    }
}
