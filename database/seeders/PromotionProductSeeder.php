<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Promotion;
use Illuminate\Database\Seeder;

class PromotionProductSeeder extends Seeder
{
    public function run(): void
    {
        $reglas = [
            'Mes del Deporte en Bolsos' => 'Bolsos y Maletines',
            'Descuento por Mayor en Accesorios' => 'Accesorios',
        ];

        foreach ($reglas as $promotionName => $categoryName) {
            $promotion = Promotion::where('name', $promotionName)->first();
            $category = Category::where('name', $categoryName)->first();

            if (! $promotion || ! $category) {
                $this->command->warn("Falta la promoción \"{$promotionName}\" o la categoría \"{$categoryName}\".");

                continue;
            }

            $promotion->products()->syncWithoutDetaching(
                $category->products()->pluck('id')
            );
        }

        $this->command->info('✅ Productos vinculados a promociones correctamente.');
    }
}
