<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

/**
 * La tienda tiene tres sucursales. Central y Norte venden del mismo almacén (ver WarehouseSeeder);
 * Plan 3000 tiene su propio depósito.
 */
class BranchSeeder extends Seeder
{
    public const CENTRAL = 'Mochilas Santa Rosa - Central';

    public const NORTE = 'Mochilas Santa Rosa - Norte';

    public const PLAN_3000 = 'Mochilas Santa Rosa - Plan 3000';

    public function run(): void
    {
        foreach ([
            [self::CENTRAL, 'Av. Grigotá esq. 3er anillo interno', '3-3524100'],
            [self::NORTE, 'Av. Banzer, 6to anillo, frente al mercado Abasto Norte', '3-3428200'],
            [self::PLAN_3000, 'Av. Paurito, Plan 3000, UV 160', '3-3627300'],
        ] as [$name, $address, $phone]) {
            Branch::updateOrCreate(['name' => $name], ['address' => $address, 'phone' => $phone, 'is_active' => true]);
        }

        $this->command->info('✅ Sucursales creadas correctamente.');
    }
}
