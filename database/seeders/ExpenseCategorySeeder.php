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
            'Permisos y trámites sanitarios',
            'Otros gastos',
        ] as $name) {
            ExpenseCategory::firstOrCreate(['name' => $name]);
        }

        $this->command->info('✅ Categorías de gasto creadas.');
    }
}
