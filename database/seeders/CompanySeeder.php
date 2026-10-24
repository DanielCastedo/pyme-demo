<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        Company::updateOrCreate(
            ['id' => 1], // asegura que siempre exista un único registro
            [
                'name' => 'Farmacia Santa Rosa',
                'legal_name' => 'Farmacia Santa Rosa S.R.L.',
                'tax_id' => null,
                'phone' => '3-3361234',
                'email' => null,
                'address' => 'Av. Irala esq. calle Ejército',
                'city' => 'Santa Cruz de la Sierra',
                'country' => 'Bolivia',
                'currency' => 'BOB',
                'active' => true,
            ]
        );
    }
}
