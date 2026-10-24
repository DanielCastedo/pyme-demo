<?php

namespace Database\Factories;

use App\Models\StockMovement;
use App\Models\Type;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockMovement>
 */
class StockMovementFactory extends Factory
{
    protected $model = StockMovement::class;

    public function definition(): array
    {
        return [
            'type_id' => Type::factory(),
            'warehouse_id' => Warehouse::factory(),
            'user_id' => User::factory(),
            'date' => now()->toDateString(),
            'note' => fake()->sentence(),
            'order_id' => null,
        ];
    }
}
