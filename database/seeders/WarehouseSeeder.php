<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    public function run(): void
    {
        $branches = Branch::all();

        if ($branches->isEmpty()) {
            $this->command->warn('No hay sucursales. Corre primero el BranchSeeder.');

            return;
        }

        $porSucursal = [
            'Sucursal Brazil Importaciones' => ['name' => 'Almacén Central', 'code' => 'ALM-CEN'],
            'Sucursal Daniela Importaciones' => ['name' => 'Almacén Norte', 'code' => 'ALM-NOR'],
        ];

        foreach ($branches as $branch) {
            $datos = $porSucursal[$branch->name] ?? ['name' => "Almacén {$branch->name}", 'code' => 'ALM-'.$branch->id];

            Warehouse::updateOrCreate(
                ['code' => $datos['code']],
                ['name' => $datos['name'], 'is_active' => true, 'branche_id' => $branch->id]
            );
        }

        $this->command->info('✅ Almacenes creados correctamente.');
    }
}
