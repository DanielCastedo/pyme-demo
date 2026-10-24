<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    /**
     * Catálogo base de unidades de medida de una farmacia.
     */
    public function run(): void
    {
        $units = [
            ['Unidad', 'und'],
            ['Tableta', 'tab'],
            ['Cápsula', 'cap'],
            ['Blíster', 'bli'],
            ['Caja', 'cja'],
            ['Frasco', 'fco'],
            ['Sobre', 'sob'],
            ['Ampolla', 'amp'],
            ['Tubo', 'tub'],
        ];

        foreach ($units as [$name, $abbreviation]) {
            Unit::updateOrCreate(['name' => $name], ['abbreviation' => $abbreviation, 'is_active' => true]);
        }

        $this->command->info('✅ Catálogo de unidades de medida creado correctamente.');
    }
}
