<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Cashbox;
use Illuminate\Database\Seeder;

class CashboxSeeder extends Seeder
{
    public function run(): void
    {
        $branches = Branch::all();

        if ($branches->isEmpty()) {
            $this->command->warn('No hay sucursales. Corre primero el BranchSeeder.');

            return;
        }

        foreach ($branches as $branch) {
            $esCentral = $branch->name === 'Sucursal Brazil Importaciones';

            // La caja de la sucursal central arranca con un fondo de ejemplo (Bs 4.000) para poder pagar compras y gastos.
            // firstOrCreate: si la caja ya existe no se le vuelve a poner el fondo.
            Cashbox::firstOrCreate(
                ['branche_id' => $branch->id, 'name' => $esCentral ? 'Caja Principal' : "Caja {$branch->name}"],
                ['cash' => $esCentral ? 4000 : 0, 'balance' => $esCentral ? 4000 : 0]
            );
        }

        $this->command->info('✅ Cajas registradoras creadas correctamente.');
    }
}
