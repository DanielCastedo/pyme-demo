<?php

use App\Models\Company;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\SubscriptionRequest;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    testBranch();
    Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
    // Company 1 es usada por SubscriptionController
    $this->company = Company::firstOrCreate(['id' => 1], [
        'name' => 'Empresa Principal',
        'subdomain' => 'principal',
        'is_active' => true,
    ]);
});

function superAdminSubUser(): User
{
    $user = userWith('admin');
    $user->assignRole('superadmin');
    return $user;
}

// =========================================================================
// 1. RUTAS PÚBLICAS Y DE SUSCRIPCIÓN BÁSICA
// =========================================================================

describe('planes públicos y suscripción de usuario', function () {
    it('muestra la página pública de planes sin autenticación', function () {
        SubscriptionPlan::factory()->create(['name' => 'Plan Inicial', 'active' => true]);

        $response = $this->get(route('subscriptions.plans'));

        $response->assertOk()
            ->assertViewIs('subscriptions.plans')
            ->assertSee('Plan Inicial');
    });

    it('permite a un usuario autenticado ver el estado de suscripción de la empresa', function () {
        $user = userWith();
        $plan = SubscriptionPlan::factory()->create(['name' => 'Plan Pro', 'active' => true]);
        Subscription::create([
            'company_id' => 1,
            'subscription_plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        $response = $this->actingAs($user)->get(route('subscriptions.index'));

        $response->assertOk()
            ->assertViewIs('subscriptions.index')
            ->assertSee('Plan Pro');
    });

    it('permite a un administrador solicitar una nueva suscripción', function () {
        $user = superAdminSubUser();
        $plan = SubscriptionPlan::factory()->create(['price' => 120]);

        $payload = [
            'subscription_plan_id' => $plan->id,
            'method' => 'qr',
            'reference' => 'TX-987654',
        ];

        $response = $this->actingAs($user)->post(route('subscriptions.subscribe'), $payload);

        $response->assertRedirect(route('subscriptions.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('subscriptions', [
            'company_id' => 1,
            'subscription_plan_id' => $plan->id,
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('payment_subscriptions', [
            'company_id' => 1,
            'reference' => 'TX-987654',
            'status' => 'pending',
        ]);
    });

    it('un usuario sin rol de administrador no puede solicitar, cancelar ni ver facturas', function () {
        $user = userWith();
        $plan = SubscriptionPlan::factory()->create();
        $sub = Subscription::create([
            'company_id' => 1,
            'subscription_plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now(),
        ]);

        $this->actingAs($user)->post(route('subscriptions.subscribe'), [
            'subscription_plan_id' => $plan->id,
            'method' => 'qr',
            'reference' => 'TX-1',
        ])->assertForbidden();
        $this->actingAs($user)->post(route('subscriptions.cancel'))->assertForbidden();
        $this->actingAs($user)->get(route('subscriptions.invoices'))->assertForbidden();

        expect($sub->fresh()->status)->toBe('active');
    });

    it('permite a un administrador cancelar la suscripción activa o pendiente', function () {
        $user = superAdminSubUser();
        $plan = SubscriptionPlan::factory()->create();
        $sub = Subscription::create([
            'company_id' => 1,
            'subscription_plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now(),
            'auto_renew' => true,
        ]);

        $response = $this->actingAs($user)->post(route('subscriptions.cancel'));

        $response->assertRedirect(route('subscriptions.index'))
            ->assertSessionHas('success');

        expect($sub->fresh()->status)->toBe('canceled');
    });
});

// =========================================================================
// 2. GESTIÓN ADMINISTRATIVA (SUPERADMIN)
// =========================================================================

describe('aprobación y gestión superadmin de suscripciones', function () {
    it('muestra el listado de solicitudes pendientes a un superadmin', function () {
        $admin = superAdminSubUser();
        $plan = SubscriptionPlan::factory()->create();
        Subscription::create([
            'company_id' => 1,
            'subscription_plan_id' => $plan->id,
            'status' => 'pending',
            'starts_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('subscriptions.pending'));

        $response->assertOk()
            ->assertViewIs('subscriptions.pending');
    });

    it('prohíbe el acceso a solicitudes pendientes a usuarios que no son superadmin', function () {
        $regular = userWith();

        $this->actingAs($regular)->get(route('subscriptions.pending'))
            ->assertForbidden();
    });

    it('aprueba una suscripción pendiente activándola y generando su factura', function () {
        $admin = superAdminSubUser();
        $plan = SubscriptionPlan::factory()->create(['billing_cycle' => 'monthly', 'price' => 100]);
        $sub = Subscription::create([
            'company_id' => 1,
            'subscription_plan_id' => $plan->id,
            'status' => 'pending',
            'starts_at' => now(),
        ]);
        Payment::create([
            'subscription_id' => $sub->id,
            'company_id' => 1,
            'amount' => 100,
            'currency' => 'BOB',
            'method' => 'qr',
            'reference' => 'PAGO-123',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->post(route('subscriptions.approve', $sub));

        $response->assertSessionHas('success');
        expect($sub->fresh()->status)->toBe('active')
            ->and($sub->fresh()->ends_at)->not->toBeNull();

        $this->assertDatabaseHas('invoices', [
            'company_id' => 1,
            'subscription_id' => $sub->id,
            'status' => 'paid',
        ]);
    });

    it('rechaza una solicitud de suscripción pendiente', function () {
        $admin = superAdminSubUser();
        $plan = SubscriptionPlan::factory()->create();
        $sub = Subscription::create([
            'company_id' => 1,
            'subscription_plan_id' => $plan->id,
            'status' => 'pending',
            'starts_at' => now(),
        ]);

        $response = $this->actingAs($admin)->post(route('subscriptions.reject', $sub), [
            'reason' => 'Comprobante de pago ilegible',
        ]);

        $response->assertSessionHas('success');
        expect($sub->fresh()->status)->toBe('canceled');
    });
});
