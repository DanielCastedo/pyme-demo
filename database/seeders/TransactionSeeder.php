<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Cashbox;
use App\Models\PaymentMethod;
use App\Models\Transaction;
use App\Models\Type;
use App\Models\User;
use App\Services\TransactionService;
use Illuminate\Database\Seeder;

class TransactionSeeder extends Seeder
{
    /**
     * Los movimientos de dinero se registran con TransactionService (no con create directo) para
     * que los saldos de la caja queden consistentes con el historial. Las ventas ya generan su
     * propio cobro (ver OrderSeeder); aquí solo se agrega un gasto de ejemplo, que necesita
     * efectivo en caja, por eso este seeder corre después de OrderSeeder.
     */
    public function run(TransactionService $transactions): void
    {
        $gasto = Type::where('code', 'GTO')->first();

        if (! $gasto || Transaction::where('type_id', $gasto->id)->exists()) {
            $this->command->info('Sin tipo de gasto o ya existen gastos, se omite TransactionSeeder.');

            return;
        }

        $user = User::where('email', 'admin@gmail.com')->first() ?? User::first();
        $branch = Branch::first();
        $cashbox = $branch ? Cashbox::where('branche_id', $branch->id)->first() : null;
        $efectivo = PaymentMethod::where('is_cash', true)->first();

        if (! $user || ! $cashbox || ! $efectivo) {
            $this->command->warn('Faltan datos base (usuario, caja o método de pago en efectivo).');

            return;
        }

        try {
            $transactions->register([
                'type_id' => $gasto->id,
                'payment_id' => $efectivo->id,
                'cashbox_id' => $cashbox->id,
                'amount' => 150,
                'memo' => 'Gasto de ejemplo: útiles y limpieza',
                'user_id' => $user->id,
            ]);
        } catch (\RuntimeException $e) {
            $this->command->warn("Gasto de ejemplo omitido: {$e->getMessage()}");

            return;
        }

        $this->command->info('✅ Gasto de ejemplo registrado correctamente.');
    }
}
