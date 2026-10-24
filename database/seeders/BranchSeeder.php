<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

/**
 * La farmacia trabaja con una sola sucursal: todo (usuarios, almacén, caja) cuelga de ella.
 */
class BranchSeeder extends Seeder
{
    public const CENTRAL = 'Farmacia Santa Rosa - Central';

    public function run(): void
    {
        Branch::updateOrCreate(['name' => self::CENTRAL], [
            'name' => self::CENTRAL,
            'address' => 'Av. Irala esq. calle Ejército, frente al Hospital San Juan de Dios',
            'phone' => '3-3361234',
            'is_active' => true,
        ]);

        $this->command->info('✅ Sucursal creada correctamente.');
    }
}
