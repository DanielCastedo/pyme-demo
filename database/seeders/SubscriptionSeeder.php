<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::find(1);

        if (! $company) {
            $this->command->warn('No existe la empresa con id 1. Corre primero el CompanySeeder.');

            return;
        }

        $plan = SubscriptionPlan::where('name', 'Pro Anual')->where('billing_cycle', 'yearly')->first();

        if (! $plan) {
            $this->command->warn('No existe el plan Pro Anual. Corre primero el SubscriptionPlanSeeder.');

            return;
        }

        Subscription::updateOrCreate(
            ['company_id' => $company->id, 'status' => 'active'],
            [
                'subscription_plan_id' => $plan->id,
                'starts_at' => '2026-09-22 00:00:00',
                'ends_at' => '2027-09-23 00:00:00',
                'trial_ends_at' => null,
                'status' => 'active',
                'auto_renew' => false,
            ]
        );
    }
}
