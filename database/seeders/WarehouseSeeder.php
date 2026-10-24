<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

/**
 * Un solo almacén (el de la única sucursal): ahí entra y sale todo el stock de la farmacia.
 */
class WarehouseSeeder extends Seeder
{
    public function run(): void
    {
        $branch = Branch::where('name', BranchSeeder::CENTRAL)->first() ?? Branch::first();

        if (! $branch) {
            $this->command->warn('No hay sucursales. Corre primero el BranchSeeder.');

            return;
        }

        Warehouse::updateOrCreate(
            ['code' => 'ALM-CEN'],
            ['name' => 'Almacén Central', 'is_active' => true, 'branche_id' => $branch->id]
        );

        $this->command->info('✅ Almacén creado correctamente.');
    }
}
