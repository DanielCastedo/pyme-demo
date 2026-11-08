<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['name' => 'Cervezas', 'description' => 'Nacionales e importadas, en lata y botella.'],
            ['name' => 'Whiskys', 'description' => 'Escoceses, bourbon y blends.'],
            ['name' => 'Rones y Vodkas', 'description' => 'Rones añejos, blancos y vodkas.'],
            ['name' => 'Singanis y Piscos', 'description' => 'Singanis bolivianos y piscos.'],
            ['name' => 'Vinos y Espumantes', 'description' => 'Tintos, blancos, rosados y espumantes.'],
            ['name' => 'Bebidas sin Alcohol y Hielo', 'description' => 'Gaseosas, aguas, energizantes y hielo para acompañar.'],
        ] as $category) {
            Category::updateOrCreate(['name' => $category['name']], $category);
        }

        $this->command->info('✅ Categorías de producto creadas correctamente.');
    }
}
