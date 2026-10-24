<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductComponent;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductComponentFactory extends Factory
{
    protected $model = ProductComponent::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'component_product_id' => Product::factory(),
            'quantity' => $this->faker->numberBetween(1, 5),
        ];
    }
}
