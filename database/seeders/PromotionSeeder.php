<?php

namespace Database\Seeders;

use App\Models\Promotion;
use Illuminate\Database\Seeder;

class PromotionSeeder extends Seeder
{
    public function run(): void
    {
        $promotions = [
            [
                'name' => 'Mes del Vino',
                'value' => 10.00,
                'min_quantity' => 1,
                'start_date' => now()->toDateString(),
                'end_date' => now()->addMonth()->toDateString(),
                'is_active' => true,
            ],
            [
                'name' => 'Descuento por Volumen en Cervezas',
                'value' => 15.00,
                'min_quantity' => 24,
                'start_date' => now()->toDateString(),
                'end_date' => now()->addMonths(2)->toDateString(),
                'is_active' => true,
            ],
        ];

        foreach ($promotions as $promotion) {
            Promotion::updateOrCreate(['name' => $promotion['name']], $promotion);
        }

        $this->command->info('✅ Promociones creadas correctamente.');
    }
}
