<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Cashbox;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cashbox>
 */
class CashboxFactory extends Factory
{
    protected $model = Cashbox::class;

    public function definition(): array
    {
        return [
            'branche_id' => Branch::factory(),
            'name' => 'Caja ' . fake()->unique()->numerify('##'),
            'cash' => 0.00,
            'balance' => 0.00,
        ];
    }
}
