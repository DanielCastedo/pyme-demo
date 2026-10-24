<?php

use App\Models\Company;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\SubscriptionRequest;
use App\Models\User;

beforeEach(function () {
    testBranch();
    $this->company = Company::firstOrCreate(['id' => 1], [
        'name' => 'Empresa Principal',
        'subdomain' => 'principal',
        'is_active' => true,
    ]);
    $plan = SubscriptionPlan::firstOrCreate(['name' => 'Plan Base'], [
        'price' => 100,
        'billing_cycle' => 'monthly',
    ]);
    Subscription::firstOrCreate(['company_id' => 1], [
        'subscription_plan_id' => $plan->id,
        'status' => 'active',
        'starts_at' => now(),
        'ends_at' => now()->addYear(),
        'auto_renew' => true,
    ]);
});

// =========================================================================
// 1. PUBLIC PLAN CONTROLLER (LANDING Y SOLICITUDES)
// =========================================================================

describe('PublicPlanController', function () {
    it('muestra la página pública de planes a visitantes no autenticados', function () {
        SubscriptionPlan::factory()->create(['name' => 'Plan Express', 'active' => true, 'price' => 50]);

        $response = $this->get(route('public.plans'));

        $response->assertOk()
            ->assertViewIs('public.plans')
            ->assertSee('Plan Express');
    });

    it('redirige a la vista interna de planes si el usuario ya está autenticado', function () {
        $user = userWith();

        $response = $this->actingAs($user)->get(route('public.plans'));

        $response->assertRedirect(route('subscriptions.plans'));
    });

    it('permite a un visitante enviar una solicitud de plan (lead)', function () {
        $plan = SubscriptionPlan::factory()->create();

        $payload = [
            'subscription_plan_id' => $plan->id,
            'name' => 'Alejandro Morales',
            'business_name' => 'Comercial Morales',
            'phone' => '71234567',
            'email' => 'alejandro@morales.com',
            'message' => 'Deseo más información sobre el plan',
        ];

        $response = $this->post(route('public.plans.request'), $payload);

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('subscription_requests', [
            'name' => 'Alejandro Morales',
            'phone' => '71234567',
            'subscription_plan_id' => $plan->id,
        ]);
    });

    it('falla si faltan nombre o teléfono en la solicitud pública de plan', function () {
        $response = $this->post(route('public.plans.request'), [
            'name' => '',
            'phone' => '',
        ]);

        $response->assertSessionHasErrors(['name', 'phone']);
    });
});

// =========================================================================
// 2. DASHBOARD CONTROLLER
// =========================================================================

describe('DashboardController', function () {
    it('muestra el dashboard principal a usuarios autenticados', function () {
        $user = userWith();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk()
            ->assertViewIs('pages.dashboard.dashboard');
    });

    it('muestra las vistas de analytics y fintech del dashboard', function () {
        $user = userWith();

        $respAnalytics = $this->actingAs($user)->get(route('dashboard.analytics'));
        $respAnalytics->assertOk()
            ->assertViewIs('pages.dashboard.analytics');

        $respFintech = $this->actingAs($user)->get(route('dashboard.fintech'));
        $respFintech->assertOk()
            ->assertViewIs('pages.dashboard.fintech');
    })->todo('Las vistas analytics.blade.php y fintech.blade.php no existen en la aplicación.');
});

// =========================================================================
// 3. INVOICE CONTROLLER
// =========================================================================

describe('InvoiceController', function () {
    it('muestra el detalle de una factura generada para la suscripción', function () {
        $user = userWith();
        $plan = SubscriptionPlan::factory()->create(['price' => 100]);
        $sub = Subscription::create([
            'company_id' => 1,
            'subscription_plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now(),
        ]);
        $payment = Payment::create([
            'subscription_id' => $sub->id,
            'company_id' => 1,
            'amount' => 100,
            'currency' => 'BOB',
            'method' => 'qr',
            'reference' => 'REF-INV-1',
            'status' => 'completed',
        ]);
        $invoice = Invoice::create([
            'company_id' => 1,
            'subscription_id' => $sub->id,
            'payment_id' => $payment->id,
            'invoice_number' => 'FAC-202609-001',
            'issue_date' => now(),
            'due_date' => now(),
            'currency' => 'BOB',
            'status' => 'paid',
            'subtotal' => 100,
            'tax' => 0,
            'total' => 100,
        ]);

        $response = $this->actingAs($user)->get(route('invoices.show', $invoice));

        $response->assertOk()
            ->assertViewIs('subscriptions.invoice-show')
            ->assertSee('FAC-202609-001');
    });
});
