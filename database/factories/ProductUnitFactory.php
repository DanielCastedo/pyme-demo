<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductUnit>
 */
class ProductUnitFactory extends Factory
{
    protected $model = ProductUnit::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'name' => fake()->randomElement(['Pieza', 'Caja x12', 'Paquete x6', 'Bulto']),
            'factor' => 1.0000,
            'price' => fake()->randomFloat(2, 10, 200),
            'is_base' => false,
            'barcode' => fake()->unique()->ean13(),
        ];
    }

    public function base(): static
    {
        return $this->state(fn () => [
            'name' => 'Unidad',
            'factor' => 1.0000,
            'is_base' => true,
        ]);
    }
}
