<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

/**
 * La licorería tiene tres sucursales. Central y Norte venden del mismo almacén (ver WarehouseSeeder);
 * Plan 3000 tiene su propio depósito.
 */
class BranchSeeder extends Seeder
{
    public const CENTRAL = 'Licorería Santa Rosa - Central';

    public const NORTE = 'Licorería Santa Rosa - Norte';

    public const PLAN_3000 = 'Licorería Santa Rosa - Plan 3000';

    public function run(): void
    {
        foreach ([
            [self::CENTRAL, 'Av. Monseñor Rivero esq. calle Asunta Limpias', '3-3524100'],
            [self::NORTE, 'Av. Banzer, 4to anillo, zona Norte', '3-3428200'],
            [self::PLAN_3000, 'Av. Paurito, Plan 3000, UV 160', '3-3627300'],
        ] as [$name, $address, $phone]) {
            Branch::updateOrCreate(['name' => $name], ['address' => $address, 'phone' => $phone, 'is_active' => true]);
        }

        $this->command->info('✅ Sucursales creadas correctamente.');
    }
}
