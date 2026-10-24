<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        $methods = [
            [
                'name' => 'Efectivo',
                'note' => 'Pago en efectivo recibido en caja.',
                'number' => null,
                'photo' => null,
                'is_cash' => true,
            ],
            [
                'name' => 'QR Simple',
                'note' => 'Pago mediante QR desde cualquier banco o billetera móvil.',
                'number' => null,
                'photo' => null,
            ],
            [
                'name' => 'Tarjeta de Crédito/Débito',
                'note' => 'Cobro con POS Visa/Mastercard.',
                'number' => null,
                'photo' => null,
            ],
        ];

        foreach ($methods as $method) {
            PaymentMethod::updateOrCreate(['name' => $method['name']], $method);
        }

        $this->command->info('✅ Métodos de pago creados correctamente.');
    }
}
