<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Cashbox;
use Illuminate\Database\Seeder;

class CashboxSeeder extends Seeder
{
    public function run(): void
    {
        $branch = Branch::where('name', BranchSeeder::CENTRAL)->first() ?? Branch::first();

        if (! $branch) {
            $this->command->warn('No hay sucursales. Corre primero el BranchSeeder.');

            return;
        }

        // La caja arranca con un fondo de ejemplo (Bs 4.000) para poder pagar compras y gastos.
        // firstOrCreate: si la caja ya existe no se le vuelve a poner el fondo.
        Cashbox::firstOrCreate(
            ['branche_id' => $branch->id, 'name' => 'Caja Principal'],
            ['cash' => 4000, 'balance' => 4000]
        );

        $this->command->info('✅ Caja registradora creada correctamente.');
    }
}
