<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            [
                'name' => 'Distribuidora de Bebidas del Oriente S.A.',
                'document_type' => 'nit',
                'document' => '3049582019',
                'contact_name' => 'Hernán Roca',
                'phone' => '3-3335566',
                'email' => 'ventas@bebidasoriente.example',
                'address' => 'Av. Mutualista entre 2do y 3er Anillo',
                'city' => 'Santa Cruz',
            ],
            [
                'name' => 'Importadora de Vinos y Licores Andes S.R.L.',
                'document_type' => 'nit',
                'document' => '1029384019',
                'contact_name' => 'Carlos Alberto Silva',
                'phone' => '3-3448899',
                'email' => 'pedidos@licoresandes.example',
                'address' => 'Parque Industrial PI-32',
                'city' => 'Santa Cruz',
            ],
        ] as $supplier) {
            Supplier::updateOrCreate(['document' => $supplier['document']], $supplier + ['is_active' => true]);
        }

        $this->command->info('✅ Proveedores de ejemplo creados correctamente.');
    }
}
