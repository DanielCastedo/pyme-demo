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
            'Mes del Vino' => 'Vinos y Espumantes',
            'Descuento por Volumen en Cervezas' => 'Cervezas',
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
