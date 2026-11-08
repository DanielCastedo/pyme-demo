<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    /**
     * Catálogo base de unidades de medida de una licorería.
     */
    public function run(): void
    {
        $units = [
            ['Unidad', 'und'],
            ['Botella', 'bot'],
            ['Lata', 'lta'],
            ['Pack', 'pck'],
            ['Caja', 'cja'],
            ['Bolsa', 'bol'],
            ['Combo', 'cmb'],
        ];

        foreach ($units as [$name, $abbreviation]) {
            Unit::updateOrCreate(['name' => $name], ['abbreviation' => $abbreviation, 'is_active' => true]);
        }

        $this->command->info('✅ Catálogo de unidades de medida creado correctamente.');
    }
}
