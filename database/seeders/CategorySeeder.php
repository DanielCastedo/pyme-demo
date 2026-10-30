<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['name' => 'Mochilas Escolares', 'description' => 'Mochilas para colegio, con ruedas, de espalda acolchada y combos de regreso a clases.'],
            ['name' => 'Mochilas Urbanas y de Laptop', 'description' => 'Mochilas antirrobo, para laptop, de viaje y de uso diario.'],
            ['name' => 'Estuches y Cartucheras', 'description' => 'Estuches de uno o dos cierres, cartucheras tipo lata y porta lápices.'],
            ['name' => 'Loncheras', 'description' => 'Loncheras térmicas, porta tapers y botellas.'],
            ['name' => 'Bolsos y Maletines', 'description' => 'Bolsos deportivos, de viaje, maletines y riñoneras.'],
            ['name' => 'Accesorios', 'description' => 'Llaveros, candados, fundas impermeables y parches.'],
        ] as $category) {
            Category::updateOrCreate(['name' => $category['name']], $category);
        }

        $this->command->info('✅ Categorías de producto creadas correctamente.');
    }
}
