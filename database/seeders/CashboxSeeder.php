<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Cashbox;
use Illuminate\Database\Seeder;

/**
 * Una caja por sucursal: aunque Central y Norte compartan almacén, cada una cobra en su propia caja.
 * Arrancan con un fondo de ejemplo para poder pagar compras y gastos.
 */
class CashboxSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            BranchSeeder::CENTRAL => ['Caja Principal', 5000],
            BranchSeeder::NORTE => ['Caja Norte', 1000],
            BranchSeeder::PLAN_3000 => ['Caja Plan 3000', 1000],
        ] as $branchName => [$name, $fund]) {
            $branch = Branch::where('name', $branchName)->first();

            if (! $branch) {
                $this->command->warn("No existe la sucursal {$branchName}. Corre primero el BranchSeeder.");

                continue;
            }

            // firstOrCreate: si la caja ya existe no se le vuelve a poner el fondo.
            Cashbox::firstOrCreate(['branche_id' => $branch->id, 'name' => $name], ['cash' => $fund, 'balance' => $fund]);
        }

        $this->command->info('✅ Cajas registradoras creadas correctamente.');
    }
}
