<?php

namespace Database\Factories;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubscriptionPlanFactory extends Factory
{
    protected $model = SubscriptionPlan::class;

    public function definition(): array
    {
        return [
            'name' => 'Plan ' . fake()->unique()->word(),
            'description' => fake()->sentence(),
            'price' => fake()->randomFloat(2, 50, 500),
            'currency' => 'BOB',
            'billing_cycle' => 'monthly',
            'max_users' => 5,
            'features' => ['Soporte 24/7', 'Copias de seguridad'],
            'qr_image' => null,
            'active' => true,
        ];
    }
}
