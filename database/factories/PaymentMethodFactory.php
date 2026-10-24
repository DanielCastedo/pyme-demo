<?php

namespace Database\Factories;

use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentMethod>
 */
class PaymentMethodFactory extends Factory
{
    protected $model = PaymentMethod::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word() . ' Pay',
            'number' => fake()->numerify('####-####-####'),
            'is_cash' => false,
            'note' => fake()->sentence(),
            'photo' => null,
        ];
    }

    public function cash(): static
    {
        return $this->state(fn () => [
            'name' => 'Efectivo',
            'is_cash' => true,
            'number' => null,
        ]);
    }
}
