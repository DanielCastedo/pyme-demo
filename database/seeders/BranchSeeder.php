<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        $branches = [
            [
                'name' => 'Sucursal Brazil Importaciones',
                'address' => 'Av. Cristo Redentor, 3er anillo interno',
                'phone' => '3-3456789',
                'is_active' => true,
            ],
            [
                'name' => 'Sucursal Daniela Importaciones',
                'address' => 'Av. Banzer, radial 27',
                'phone' => '3-3987654',
                'is_active' => true,
            ],
        ];

        foreach ($branches as $branch) {
            Branch::updateOrCreate(['name' => $branch['name']], $branch);
        }

        $this->command->info('✅ Sucursales creadas correctamente.');
    }
}
