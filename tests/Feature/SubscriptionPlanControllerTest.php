<?php

use App\Models\SubscriptionPlan;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    testBranch();
    Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
});

function superAdminPlanUser(): User
{
    $user = userWith('admin');
    $user->assignRole('superadmin');
    return $user;
}

// =========================================================================
// 1. AUTENTICACIÓN Y AUTORIZACIÓN
// =========================================================================

describe('autenticación y rol superadmin', function () {
    it('redirige a login si no está autenticado', function () {
        $plan = SubscriptionPlan::factory()->create();

        $this->get(route('subscription-plans.index'))->assertRedirect(route('login'));
        $this->post(route('subscription-plans.store'), [])->assertRedirect(route('login'));
        $this->get(route('subscription-plans.show', $plan))->assertRedirect(route('login'));
        $this->get(route('subscription-plans.edit', $plan))->assertRedirect(route('login'));
        $this->put(route('subscription-plans.update', $plan), [])->assertRedirect(route('login'));
        $this->delete(route('subscription-plans.destroy', $plan))->assertRedirect(route('login'));
    });

    it('deniega con 403 a usuarios sin rol superadmin', function () {
        $user = userWith();
        $plan = SubscriptionPlan::factory()->create();

        $this->actingAs($user)->get(route('subscription-plans.index'))->assertForbidden();
        $this->actingAs($user)->post(route('subscription-plans.store'), [])->assertForbidden();
        $this->actingAs($user)->get(route('subscription-plans.show', $plan))->assertForbidden();
        $this->actingAs($user)->get(route('subscription-plans.edit', $plan))->assertForbidden();
        $this->actingAs($user)->put(route('subscription-plans.update', $plan), [])->assertForbidden();
        $this->actingAs($user)->delete(route('subscription-plans.destroy', $plan))->assertForbidden();
    });
});

// =========================================================================
// 2. LISTADO Y BÚSQUEDA
// =========================================================================

describe('index y búsqueda', function () {
    it('lista los planes de suscripción configurados', function () {
        $admin = superAdminPlanUser();
        SubscriptionPlan::factory()->create(['name' => 'Plan Empresarial Pro']);

        $response = $this->actingAs($admin)->get(route('subscription-plans.index'));

        $response->assertOk()
            ->assertViewIs('subscription-plans.index')
            ->assertSee('Plan Empresarial Pro');
    });

    it('filtra planes por nombre usando filter[buscar]', function () {
        $admin = superAdminPlanUser();
        $planA = SubscriptionPlan::factory()->create(['name' => 'Plan Basico Inicial']);
        $planB = SubscriptionPlan::factory()->create(['name' => 'Plan Corporativo Gold']);

        $response = $this->actingAs($admin)->get(route('subscription-plans.index', ['filter[buscar]' => 'Basico']));

        $response->assertOk();
        $items = $response->viewData('plans');
        expect($items->pluck('id')->all())->toContain($planA->id)
            ->and($items->pluck('id')->all())->not->toContain($planB->id);
    });
});

// =========================================================================
// 3. STORE (CREACIÓN) Y VALIDACIONES
// =========================================================================

describe('store y validaciones', function () {
    it('crea un plan de suscripción exitosamente', function () {
        $admin = superAdminPlanUser();

        $payload = [
            'name' => 'Plan Premium Anual',
            'description' => 'Acceso completo a todos los módulos y usuarios ilimitados.',
            'price' => 199.99,
            'currency' => 'BOB',
            'billing_cycle' => 'yearly',
            'max_users' => 10,
            'features' => "Facturación ilimitada\nSoporte telefónico\nAuditoría avanzada",
            'active' => 1,
        ];

        $response = $this->actingAs($admin)->post(route('subscription-plans.store'), $payload);

        $response->assertRedirect(route('subscription-plans.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('subscription_plans', [
            'name' => 'Plan Premium Anual',
            'billing_cycle' => 'yearly',
        ]);

        $created = SubscriptionPlan::where('name', 'Plan Premium Anual')->first();
        expect($created->features)->toBeArray()
            ->and($created->features)->toContain('Facturación ilimitada');
    });

    it('falla si los campos requeridos están ausentes o el ciclo de facturación es inválido', function () {
        $admin = superAdminPlanUser();

        $response = $this->actingAs($admin)->post(route('subscription-plans.store'), [
            'name' => '',
            'price' => -10,
            'billing_cycle' => 'semanal', // inválido: debe ser monthly, quarterly o yearly
        ]);

        $response->assertSessionHasErrors(['name', 'price', 'billing_cycle']);
    });
});

// =========================================================================
// 4. SHOW, EDIT, UPDATE, DESTROY
// =========================================================================

describe('operaciones de visualización, edición y borrado', function () {
    it('muestra el detalle del plan en show', function () {
        $admin = superAdminPlanUser();
        $plan = SubscriptionPlan::factory()->create(['name' => 'Plan Startup']);

        $response = $this->actingAs($admin)->get(route('subscription-plans.show', $plan));

        $response->assertOk()
            ->assertViewIs('subscription-plans.show')
            ->assertSee('Plan Startup');
    });

    it('muestra el formulario de edición en edit', function () {
        $admin = superAdminPlanUser();
        $plan = SubscriptionPlan::factory()->create(['name' => 'Plan PyME']);

        $response = $this->actingAs($admin)->get(route('subscription-plans.edit', $plan));

        $response->assertOk()
            ->assertViewIs('subscription-plans.edit')
            ->assertSee('Plan PyME');
    });

    it('actualiza los datos de un plan', function () {
        $admin = superAdminPlanUser();
        $plan = SubscriptionPlan::factory()->create(['name' => 'Plan Antiguo', 'price' => 100]);

        $payload = [
            'name' => 'Plan Renovado',
            'price' => 150.00,
            'billing_cycle' => 'monthly',
        ];

        $response = $this->actingAs($admin)->put(route('subscription-plans.update', $plan), $payload);

        $response->assertRedirect(route('subscription-plans.index'))
            ->assertSessionHas('success');

        expect($plan->fresh()->name)->toBe('Plan Renovado')
            ->and((float) $plan->fresh()->price)->toBe(150.0);
    });

    it('elimina un plan de suscripción', function () {
        $admin = superAdminPlanUser();
        $plan = SubscriptionPlan::factory()->create();

        $response = $this->actingAs($admin)->delete(route('subscription-plans.destroy', $plan));

        $response->assertRedirect(route('subscription-plans.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('subscription_plans', ['id' => $plan->id]);
    });
});
