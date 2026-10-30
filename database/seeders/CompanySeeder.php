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
                'name' => 'Mochilas Santa Rosa',
                'legal_name' => 'Mochilas Santa Rosa S.R.L.',
                'tax_id' => null,
                'phone' => '3-3524100',
                'email' => null,
                'address' => 'Av. Grigotá esq. 3er anillo interno',
                'city' => 'Santa Cruz de la Sierra',
                'country' => 'Bolivia',
                'currency' => 'BOB',
                'active' => true,
            ]
        );
    }
}
