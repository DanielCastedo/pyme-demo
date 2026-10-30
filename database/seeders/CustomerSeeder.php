<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            // Colegio con límite de crédito: sirve para la venta a crédito de ejemplo.
            ['name' => 'Unidad Educativa San Martín', 'document_type' => 'nit', 'document' => '1023456029', 'phone' => '3-3221100', 'email' => 'compras@uesanmartin.example', 'address' => 'Av. Alemana, 4to anillo', 'city' => 'Santa Cruz', 'credit_limit' => 5000],
            ['name' => 'María Fernanda López', 'document_type' => 'ci', 'document' => '6543210', 'phone' => '70012345', 'email' => null, 'address' => 'Barrio Los Pinos, calle 5', 'city' => 'Santa Cruz', 'credit_limit' => null],
            // Revendedora que recibe bolsos en consignación (ver ConsignmentSeeder) y tiene un pedido.
            ['name' => 'Rosa Vaca (Revendedora)', 'document_type' => 'ci', 'document' => '7788990', 'phone' => '76543210', 'email' => 'rosa.vaca@example.com', 'address' => 'Calle Sucre 345', 'city' => 'Montero', 'credit_limit' => null],
        ] as $customer) {
            Customer::updateOrCreate(['document' => $customer['document']], $customer + ['is_active' => true]);
        }

        $this->command->info('✅ Clientes de ejemplo creados correctamente.');
    }
}
