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
                'name' => 'Daniela ImportDCB',
                'legal_name' => null,
                'tax_id' => null,
                'phone' => null,
                'email' => null,
                'address' => null,
                'city' => 'Santa Cruz de la Sierra',
                'country' => 'Bolivia',
                'currency' => 'BOB',
                'active' => true,
            ]
        );
    }
}
