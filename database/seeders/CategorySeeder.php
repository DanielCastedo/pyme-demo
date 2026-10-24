<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['name' => 'Analgésicos y Antipiréticos', 'description' => 'Paracetamol, ibuprofeno, diclofenaco y similares.'],
            ['name' => 'Antibióticos', 'description' => 'Venta con receta médica.'],
            ['name' => 'Respiratorios y Antigripales', 'description' => 'Jarabes, expectorantes y antigripales.'],
            ['name' => 'Vitaminas y Suplementos', 'description' => 'Multivitamínicos, vitamina C, hierro y suplementos.'],
            ['name' => 'Material de Curación', 'description' => 'Gasas, vendas, alcohol, algodón y botiquines.'],
            ['name' => 'Cuidado Personal e Higiene', 'description' => 'Higiene, protectores solares y cuidado de la piel.'],
        ] as $category) {
            Category::updateOrCreate(['name' => $category['name']], $category);
        }

        $this->command->info('✅ Categorías de producto creadas correctamente.');
    }
}
