<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['name' => 'Herramientas Manuales', 'description' => 'Martillos, destornilladores y llaves.'],
            ['name' => 'Ferretería General', 'description' => 'Tornillos, clavos y piezas sueltas.'],
            ['name' => 'Pinturas y Acabados', 'description' => 'Pinturas, barnices y brochas.'],
        ] as $category) {
            Category::updateOrCreate(['name' => $category['name']], $category);
        }

        $this->command->info('✅ Categorías de producto creadas correctamente.');
    }
}
