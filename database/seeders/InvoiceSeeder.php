<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Invoice;
use Illuminate\Database\Seeder;

class InvoiceSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::find(1);
        $subscription = $company?->subscriptions()->latest('id')->first();
        $payment = $subscription?->payments()->latest('id')->first();

        if (! $company || ! $subscription) {
            $this->command->warn('No existe la empresa o su suscripción. Corre primero CompanySeeder y SubscriptionSeeder.');

            return;
        }

        $plan = $subscription->plan;

        $invoice = Invoice::updateOrCreate(
            ['invoice_number' => 'FAC-0001'],
            [
                'company_id' => $company->id,
                'subscription_id' => $subscription->id,
                'payment_id' => $payment?->id,
                'issue_date' => now()->toDateString(),
                'due_date' => now()->addDays(5)->toDateString(),
                'currency' => $plan->currency ?? 'BOB',
                'status' => $payment?->isCompleted() ? 'paid' : 'issued',
                'notes' => 'Factura por activación de suscripción.',
            ]
        );

        $invoice->items()->delete();

        $invoice->items()->create([
            'description' => "Suscripción plan {$plan->name} ({$plan->billing_cycle})",
            'quantity' => 1,
            'unit_price' => $plan->price,
            'subtotal' => $plan->price,
        ]);

        $invoice->recalculateTotals();

        $this->command->info('✅ Factura de suscripción de ejemplo creada correctamente.');
    }
}
