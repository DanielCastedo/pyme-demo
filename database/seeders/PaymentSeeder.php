<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Payment;
use Illuminate\Database\Seeder;

class PaymentSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::find(1);
        $subscription = $company?->subscriptions()->latest('id')->first();

        if (! $company || ! $subscription) {
            $this->command->warn('No existe la empresa o su suscripción. Corre primero CompanySeeder y SubscriptionSeeder.');

            return;
        }

        Payment::updateOrCreate(
            ['subscription_id' => $subscription->id, 'reference' => 'PAGO-INICIAL-0001'],
            [
                'company_id' => $company->id,
                'amount' => $subscription->plan->price ?? 190.00,
                'currency' => $subscription->plan->currency ?? 'BOB',
                'method' => 'transferencia',
                'status' => 'completed',
                'paid_at' => now()->toDateString(),
                'notes' => 'Pago inicial de activación de la suscripción.',
            ]
        );

        $this->command->info('✅ Pago de suscripción de ejemplo creado correctamente.');
    }
}
