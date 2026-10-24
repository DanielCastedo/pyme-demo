<?php

use App\Models\Company;
use App\Models\Purchase;
use App\Models\SubscriptionPlan;
use App\Models\Supplier;
use App\Models\Warehouse;

/*
|--------------------------------------------------------------------------
| SupplierControllerTest: Pruebas de Gestión de Proveedores
|--------------------------------------------------------------------------
| Listado, búsqueda, creación (web y modal JSON para compras),
| edición, eliminación protegida y permisos.
*/

beforeEach(function () {
    $company = Company::firstOrCreate(['id' => 1], [
        'name' => 'Ferretería Brazil Importaciones',
        'nit' => '1029384756',
        'currency' => 'Bs',
    ]);

    $plan = SubscriptionPlan::firstOrCreate(['slug' => 'empresa'], [
        'name' => 'Plan Empresa',
        'price' => 100,
        'billing_cycle' => 'monthly',
        'is_active' => true,
    ]);

    $company->subscriptions()->firstOrCreate(['company_id' => $company->id], [
        'subscription_plan_id' => $plan->id,
        'status' => 'active',
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addMonth(),
    ]);
});

describe('Acceso y Permisos de Proveedores', function () {
    it('redirige al login a un visitante no autenticado', function () {
        $this->get(route('suppliers.index'))->assertRedirect(route('login'));
    });

    it('deniega acceso con 403 si el usuario no tiene permiso ver suppliers', function () {
        $user = userWith();
        $this->actingAs($user)->get(route('suppliers.index'))->assertForbidden();
    });

    it('permite ver index y show con ver suppliers, pero bloquea crear/editar/eliminar sin permisos', function () {
        $user = userWith('ver suppliers');
        $supplier = Supplier::create([
            'name' => 'Proveedor General',
            'document' => '1234567',
            'document_type' => 'nit',
        ]);

        $this->actingAs($user)->get(route('suppliers.index'))->assertOk();
        $this->actingAs($user)->get(route('suppliers.show', $supplier))->assertOk();
        $this->actingAs($user)->get(route('suppliers.edit', $supplier))->assertForbidden();
        $this->actingAs($user)->post(route('suppliers.store'), ['name' => 'Nuevo'])->assertForbidden();
        $this->actingAs($user)->delete(route('suppliers.destroy', $supplier))->assertForbidden();
    });
});

describe('Gestión y Búsqueda de Proveedores', function () {
    it('permite buscar proveedores por nombre o documento', function () {
        $user = userWith('ver suppliers');
        Supplier::create(['name' => 'Distribuidora San Martin', 'document' => '556677', 'document_type' => 'nit']);
        Supplier::create(['name' => 'Herramientas Totales', 'document' => '889900', 'document_type' => 'nit']);

        $response = $this->actingAs($user)->get(route('suppliers.index', ['filter[buscar]' => 'San Martin']));
        $response->assertOk();
        $response->assertSee('Distribuidora San Martin');
        $response->assertDontSee('Herramientas Totales');
    });

    it('registra un proveedor vía formulario web y responde con redirección', function () {
        $user = userWith('ver suppliers', 'crear suppliers');

        $response = $this->actingAs($user)->post(route('suppliers.store'), [
            'name' => 'Tubos y PVC del Oriente',
            'document' => '10203040',
            'document_type' => 'nit',
            'contact_name' => 'Carlos Mendoza',
            'phone' => '71234567',
            'city' => 'Santa Cruz',
            'is_active' => true,
        ]);

        $response->assertRedirect(route('suppliers.index'));
        expect(Supplier::where('document', '10203040')->exists())->toBeTrue();
    });

    it('soporta creación rápida vía JSON para el modal emergente de Compras', function () {
        $user = userWith('ver suppliers', 'crear suppliers');

        $response = $this->actingAs($user)->postJson(route('suppliers.store'), [
            'name' => 'Ferretera Express SRL',
            'document' => '99881122',
            'document_type' => 'nit',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('name', 'Ferretera Express SRL')
            ->assertJsonPath('document', '99881122');
    });

    it('permite editar la información de un proveedor', function () {
        $user = userWith('ver suppliers', 'editar suppliers');
        $supplier = Supplier::create([
            'name' => 'Nombre Antiguo',
            'document' => '111222',
            'document_type' => 'nit',
        ]);

        $response = $this->actingAs($user)->put(route('suppliers.update', $supplier), [
            'name' => 'Nombre Actualizado SA',
            'document' => '111222',
            'document_type' => 'nit',
            'city' => 'La Paz',
        ]);

        $response->assertRedirect(route('suppliers.index'));
        $supplier->refresh();
        expect($supplier->name)->toBe('Nombre Actualizado SA');
        expect($supplier->city)->toBe('La Paz');
    });

    it('bloquea la eliminación de un proveedor si ya tiene compras registradas', function () {
        $user = userWith('ver suppliers', 'eliminar suppliers');
        $branch = testBranch();
        $warehouse = Warehouse::factory()->create(['branche_id' => $branch->id]);

        $supplier = Supplier::create([
            'name' => 'Proveedor con Compras',
            'document' => '555444',
            'document_type' => 'nit',
        ]);

        Purchase::create([
            'warehouse_id' => $warehouse->id,
            'branche_id' => $branch->id,
            'user_id' => $user->id,
            'supplier_id' => $supplier->id,
            'amount' => 1000,
            'status' => Purchase::STATUS_RECEIVED,
            'date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($user)->delete(route('suppliers.destroy', $supplier));
        $response->assertRedirect(route('suppliers.index'));
        $response->assertSessionHas('error');

        expect(Supplier::where('id', $supplier->id)->exists())->toBeTrue();
    });

    it('elimina un proveedor correctamente si no tiene compras asociadas', function () {
        $user = userWith('ver suppliers', 'eliminar suppliers');
        $supplier = Supplier::create([
            'name' => 'Proveedor Sin Compras',
            'document' => '777111',
            'document_type' => 'nit',
        ]);

        $response = $this->actingAs($user)->delete(route('suppliers.destroy', $supplier));
        $response->assertRedirect(route('suppliers.index'));
        $response->assertSessionHas('success');

        expect(Supplier::where('id', $supplier->id)->exists())->toBeFalse();
    });
});
