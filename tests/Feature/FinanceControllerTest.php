<?php

use App\Models\Category;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Purchase;
use App\Models\SubscriptionPlan;
use App\Models\Warehouse;

/*
|--------------------------------------------------------------------------
| FinanceControllerTest: Reporte Financiero y Ganancias por Producto
|--------------------------------------------------------------------------
| Resumen de ingresos, costos, gastos, ganancia neta, desglose de productos,
| filtros de fecha y exportación de reportes PDF con botón superior derecho.
*/

beforeEach(function () {
    $this->seed(\Database\Seeders\TypeSeeder::class);

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

describe('Acceso y Permisos de Finanzas', function () {
    it('redirige al login a un visitante no autenticado', function () {
        $this->get(route('finance.report'))->assertRedirect(route('login'));
        $this->get(route('finance.products'))->assertRedirect(route('login'));
    });

    it('deniega acceso con 403 a usuarios sin el permiso reportes.ver', function () {
        $user = userWith();
        $this->actingAs($user)->get(route('finance.report'))->assertForbidden();
        $this->actingAs($user)->get(route('finance.products'))->assertForbidden();
        $this->actingAs($user)->get(route('finance.report.pdf'))->assertForbidden();
        $this->actingAs($user)->get(route('finance.products.pdf'))->assertForbidden();
    });

    it('permite acceder al reporte financiero y de ganancias con reportes.ver', function () {
        $user = userWith('reportes.ver');

        $this->actingAs($user)->get(route('finance.report'))->assertOk();
        $this->actingAs($user)->get(route('finance.products'))->assertOk();
    });
});

describe('Reporte Financiero Global', function () {
    it('muestra el balance financiero y permite filtrar por rango de fechas', function () {
        $user = userWith('reportes.ver');
        $branch = testBranch();
        $warehouse = Warehouse::factory()->create(['branche_id' => $branch->id]);
        $customer = Customer::create(['name' => 'Cliente Reporte', 'document' => '112233']);

        // Venta registrada
        Order::create([
            'user_id' => $user->id,
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'branche_id' => $branch->id,
            'amount' => 500.00,
            'paid_amount' => 500.00,
            'payment_type' => Order::PAYMENT_CASH,
            'status' => Order::STATUS_COMPLETED,
            'date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($user)->get(route('finance.report', [
            'desde' => now()->subDays(5)->toDateString(),
            'hasta' => now()->addDay()->toDateString(),
        ]));

        $response->assertOk();
        $response->assertViewHas('summary');
        $response->assertViewHas('movements');
    });
});

describe('Reporte de Ganancias por Producto', function () {
    it('calcula y muestra las ganancias por producto permitiendo ordenamientos', function () {
        $user = userWith('reportes.ver');

        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);
        ProductUnit::factory()->create([
            'product_id' => $product->id,
            'factor' => 1,
            'is_base' => true,
        ]);

        // Ordenar por diferentes criterios
        $criterios = ['ganancia', 'ventas', 'cantidad', 'compras'];

        foreach ($criterios as $orden) {
            $response = $this->actingAs($user)->get(route('finance.products', ['order' => $orden]));
            $response->assertOk();
            $response->assertViewHas('order', $orden);
            $response->assertViewHas('products');
        }
    });
});

describe('Exportación de PDFs y Botón Superior Derecho en Finanzas', function () {
    it('muestra el botón imprimir PDF en la cabecera superior derecha y genera el documento', function () {
        $user = userWith('reportes.ver', 'reportes.exportar');

        // Vista Reporte General
        $responseReport = $this->actingAs($user)->get(route('finance.report'));
        $responseReport->assertOk();
        $responseReport->assertSee(route('finance.report.pdf', []));

        // Descarga PDF Reporte General
        $responseReportPdf = $this->actingAs($user)->get(route('finance.report.pdf'));
        $responseReportPdf->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        // Vista Ganancias por Producto
        $responseProducts = $this->actingAs($user)->get(route('finance.products'));
        $responseProducts->assertOk();
        $responseProducts->assertSee(route('finance.products.pdf', []));

        // Descarga PDF Ganancias por Producto
        $responseProductsPdf = $this->actingAs($user)->get(route('finance.products.pdf'));
        $responseProductsPdf->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    });
});
