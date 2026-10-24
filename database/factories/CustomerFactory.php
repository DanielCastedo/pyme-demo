<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->name(),
            'document_type' => $this->faker->randomElement(array_keys(Customer::DOCUMENT_TYPES)),
            'document' => (string) $this->faker->unique()->numerify('########'),
            'phone' => $this->faker->numerify('7#######'),
            'email' => $this->faker->unique()->safeEmail(),
            'address' => $this->faker->streetAddress(),
            'city' => $this->faker->city(),
            'is_active' => true,
            'credit_limit' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function withCreditLimit(float $limit): static
    {
        return $this->state(fn () => ['credit_limit' => $limit]);
    }
}
