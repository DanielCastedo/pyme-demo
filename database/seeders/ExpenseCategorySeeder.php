<?php

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use Illuminate\Database\Seeder;

class ExpenseCategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            'Servicios básicos (luz, agua, internet)',
            'Alquiler',
            'Transporte y fletes',
            'Mantenimiento del local y equipos',
            'Otros gastos',
        ] as $name) {
            ExpenseCategory::firstOrCreate(['name' => $name]);
        }

        $this->command->info('✅ Categorías de gasto creadas.');
    }
}
