<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            // Empresa de eventos con límite de crédito: sirve para la venta a crédito de ejemplo.
            ['name' => 'Eventos y Banquetes Oriente S.R.L.', 'document_type' => 'nit', 'document' => '1023456029', 'phone' => '3-3221100', 'email' => 'compras@eventosoriente.example', 'address' => 'Av. Alemana, 4to anillo', 'city' => 'Santa Cruz', 'credit_limit' => 8000],
            ['name' => 'Juan Pérez Rojas', 'document_type' => 'ci', 'document' => '6543210', 'phone' => '70012345', 'email' => null, 'address' => 'Barrio Los Pinos, calle 5', 'city' => 'Santa Cruz', 'credit_limit' => null],
            // Restaurante que recibe vino en consignación (ver ConsignmentSeeder) y tiene un pedido.
            ['name' => 'Restaurante El Fogón', 'document_type' => 'nit', 'document' => '7788990', 'phone' => '76543210', 'email' => 'reservas@elfogon.example', 'address' => 'Calle Sucre 345', 'city' => 'Montero', 'credit_limit' => null],
        ] as $customer) {
            Customer::updateOrCreate(['document' => $customer['document']], $customer + ['is_active' => true]);
        }

        $this->command->info('✅ Clientes de ejemplo creados correctamente.');
    }
}
