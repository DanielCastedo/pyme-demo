<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    /**
     * Catálogo base de unidades de medida de una ferretería.
     */
    public function run(): void
    {
        $units = [
            ['Unidad', 'und'],
            ['Pieza', 'pza'],
            ['Juego', 'jgo'],
            ['Caja', 'cja'],
            ['Paquete', 'paq'],
            ['Bolsa', 'bol'],
            ['Metro', 'm'],
            ['Rollo', 'rll'],
            ['Kilo', 'kg'],
            ['Galón', 'gal'],
        ];

        foreach ($units as [$name, $abbreviation]) {
            Unit::updateOrCreate(['name' => $name], ['abbreviation' => $abbreviation, 'is_active' => true]);
        }

        $this->command->info('✅ Catálogo de unidades de medida creado correctamente.');
    }
}
