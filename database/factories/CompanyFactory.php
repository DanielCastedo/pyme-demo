<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'legal_name' => fake()->company() . ' S.R.L.',
            'tax_id' => fake()->unique()->numerify('##########'),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->companyEmail(),
            'address' => fake()->address(),
            'city' => fake()->city(),
            'country' => 'Bolivia',
            'currency' => 'BOB',
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Company $company) {
            $plan = \App\Models\SubscriptionPlan::firstOrCreate(
                ['name' => 'Plan Básico'],
                ['price' => 0, 'billing_cycle' => 'monthly', 'active' => true]
            );

            $company->subscriptions()->create([
                'subscription_plan_id' => $plan->id,
                'status' => 'trial',
                'trial_ends_at' => now()->addMonth(),
                'starts_at' => now(),
            ]);
        });
    }
}
