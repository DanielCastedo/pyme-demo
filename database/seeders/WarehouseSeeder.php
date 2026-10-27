<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

/**
 * Dos formas de trabajar a la vez:
 *  - Almacén Central: es de la sucursal Central y también lo usa la Norte (dos sucursales, un mismo stock).
 *  - Depósito Plan 3000: solo de la sucursal Plan 3000 (cada sucursal con su almacén).
 * Desde Inventario → Almacenes se cambia qué sucursales usa cada almacén.
 */
class WarehouseSeeder extends Seeder
{
    public function run(): void
    {
        $central = Branch::where('name', BranchSeeder::CENTRAL)->first();
        $norte = Branch::where('name', BranchSeeder::NORTE)->first();
        $plan3000 = Branch::where('name', BranchSeeder::PLAN_3000)->first();

        if (! $central || ! $norte || ! $plan3000) {
            $this->command->warn('Faltan sucursales. Corre primero el BranchSeeder.');

            return;
        }

        Warehouse::updateOrCreate(
            ['code' => 'ALM-CEN'],
            ['name' => 'Almacén Central', 'is_active' => true, 'branche_id' => $central->id]
        )->branches()->sync([$central->id, $norte->id]);

        Warehouse::updateOrCreate(
            ['code' => 'ALM-P3K'],
            ['name' => 'Depósito Plan 3000', 'is_active' => true, 'branche_id' => $plan3000->id]
        )->branches()->sync([$plan3000->id]);

        $this->command->info('✅ Almacenes creados (Central compartido con Norte; Plan 3000 con el suyo).');
    }
}
