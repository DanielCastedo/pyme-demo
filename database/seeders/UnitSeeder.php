<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    /**
     * Catálogo base de unidades de medida de una tienda de mochilas y estuches.
     */
    public function run(): void
    {
        $units = [
            ['Unidad', 'und'],
            ['Pieza', 'pza'],
            ['Par', 'par'],
            ['Juego', 'jgo'],
            ['Combo', 'cmb'],
            ['Paquete', 'paq'],
            ['Docena', 'doc'],
            ['Caja', 'cja'],
            ['Bolsa', 'bol'],
        ];

        foreach ($units as [$name, $abbreviation]) {
            Unit::updateOrCreate(['name' => $name], ['abbreviation' => $abbreviation, 'is_active' => true]);
        }

        $this->command->info('✅ Catálogo de unidades de medida creado correctamente.');
    }
}
