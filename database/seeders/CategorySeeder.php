<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['name' => 'Herramientas Manuales', 'description' => 'Martillos, destornilladores, alicates, llaves y juegos de herramientas.'],
            ['name' => 'Herramientas Eléctricas', 'description' => 'Taladros, amoladoras, sierras y lijadoras.'],
            ['name' => 'Tornillería y Fijaciones', 'description' => 'Tornillos, clavos, pernos, tarugos y abrazaderas.'],
            ['name' => 'Pinturas y Accesorios', 'description' => 'Látex, esmaltes, brochas, rodillos y diluyentes.'],
            ['name' => 'Electricidad', 'description' => 'Cables, enchufes, interruptores y focos.'],
            ['name' => 'Plomería', 'description' => 'Tubos, codos, llaves de paso y pegamento PVC.'],
        ] as $category) {
            Category::updateOrCreate(['name' => $category['name']], $category);
        }

        $this->command->info('✅ Categorías de producto creadas correctamente.');
    }
}
