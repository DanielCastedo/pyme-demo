<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    /**
     * Catálogo base de unidades de medida de una ferretería / tienda de importaciones.
     */
    public function run(): void
    {
        $units = [
            ['Unidad', 'und'],
            ['pza', 'pza'],
            ['Docena', 'doc'],
            ['Caja', 'cja'],
            ['Paquete', 'paq'],
        ];

        foreach ($units as [$name, $abbreviation]) {
            Unit::updateOrCreate(['name' => $name], ['abbreviation' => $abbreviation, 'is_active' => true]);
        }

        $this->command->info('✅ Catálogo de unidades de medida creado correctamente.');
    }
}
