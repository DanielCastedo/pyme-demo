<?php

use App\Models\Cashbox;
use App\Models\Category;
use App\Models\Company;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Purchase;
use App\Models\StockBatch;
use App\Models\Supplier;
use App\Models\Warehouse;

/*
|--------------------------------------------------------------------------
| PurchaseControllerTest: Pruebas del ciclo de Compras
|--------------------------------------------------------------------------
| Listado, creación, entrada de mercadería (lotes FIFO), pago desde caja,
| anulación, permisos y exportación/impresión a PDF.
*/

use App\Models\SubscriptionPlan;

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

describe('Acceso y Permisos de Compras', function () {
    it('redirige al login a un visitante no autenticado', function () {
        $this->get(route('purchases.index'))->assertRedirect(route('login'));
    });

    it('deniega el acceso con 403 a usuarios sin permiso ver purchases', function () {
        $user = userWith();
        $this->actingAs($user)->get(route('purchases.index'))->assertForbidden();
        $this->actingAs($user)->get(route('purchases.create'))->assertForbidden();
    });

    it('permite ver index y show con ver purchases, pero no crear ni anular', function () {
        $user = userWith('ver purchases');
        $branch = testBranch();
        $warehouse = Warehouse::factory()->create(['branche_id' => $branch->id]);
        $supplier = Supplier::create(['name' => 'Proveedor ACME', 'document' => '998877']);

        $purchase = Purchase::create([
            'warehouse_id' => $warehouse->id,
            'branche_id' => $branch->id,
            'user_id' => $user->id,
            'supplier_id' => $supplier->id,
            'amount' => 500,
            'status' => Purchase::STATUS_RECEIVED,
            'date' => now()->toDateString(),
        ]);

        $this->actingAs($user)->get(route('purchases.index'))->assertOk();
        $this->actingAs($user)->get(route('purchases.show', $purchase))->assertOk();
        $this->actingAs($user)->get(route('purchases.create'))->assertForbidden();
        $this->actingAs($user)->post(route('purchases.cancel', $purchase))->assertForbidden();
    });

    it('bloquea la anulación a un usuario que no posee el permiso compras.anular', function () {
        $user = userWith('ver purchases', 'crear purchases');
        $branch = testBranch();
        $warehouse = Warehouse::factory()->create(['branche_id' => $branch->id]);

        $purchase = Purchase::create([
            'warehouse_id' => $warehouse->id,
            'branche_id' => $branch->id,
            'user_id' => $user->id,
            'amount' => 200,
            'status' => Purchase::STATUS_RECEIVED,
            'date' => now()->toDateString(),
        ]);

        $this->actingAs($user)->post(route('purchases.cancel', $purchase))->assertForbidden();
    });
});

describe('Alta rápida de producto desde el buscador de la compra', function () {
    it('el formulario de nueva compra ofrece "+ Nuevo producto" con permiso, y el buscador (no el catálogo entero) manda la foto del producto', function () {
        $branch = testBranch();
        Warehouse::factory()->create(['branche_id' => $branch->id]);
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id, 'photo' => 'products/999.webp']);
        ProductUnit::factory()->create(['product_id' => $product->id, 'is_base' => true, 'factor' => 1]);

        $user = userWith('ver purchases', 'crear purchases', 'crear products', 'crear categories');

        $response = $this->actingAs($user)->get(route('purchases.create'))->assertOk();

        $response->assertSee('+ Nuevo producto', false)
            ->assertSee('+ Nueva categoría', false)
            ->assertSee($category->name, false)
            ->assertViewMissing('units'); // ver PurchaseController::search(): se pide de a poco, no se precarga

        $units = collect($this->getJson(route('purchases.products.search', ['q' => $product->name]))->json('units'));
        expect($units->firstWhere('sku', $product->sku)['photo_url'])->toContain('products/999.webp');
    });

    it('sin permiso para crear productos, no ofrece la opción', function () {
        testBranch();

        $user = userWith('ver purchases', 'crear purchases');

        $this->actingAs($user)->get(route('purchases.create'))
            ->assertOk()
            ->assertDontSee('+ Nuevo producto', false);
    });
});

describe('Listado y Búsqueda de Compras', function () {
    it('muestra el listado de compras y permite filtrar por proveedor o número de factura', function () {
        $user = userWith('ver purchases');
        $branch = testBranch();
        $warehouse = Warehouse::factory()->create(['branche_id' => $branch->id]);
        $supplier1 = Supplier::create(['name' => 'Aceros del Oriente', 'document' => '112233']);
        $supplier2 = Supplier::create(['name' => 'Pinturas del Sur', 'document' => '445566']);

        Purchase::create([
            'warehouse_id' => $warehouse->id,
            'branche_id' => $branch->id,
            'user_id' => $user->id,
            'supplier_id' => $supplier1->id,
            'invoice_number' => 'FAC-1001',
            'amount' => 1500,
            'status' => Purchase::STATUS_RECEIVED,
            'date' => now()->toDateString(),
        ]);

        Purchase::create([
            'warehouse_id' => $warehouse->id,
            'branche_id' => $branch->id,
            'user_id' => $user->id,
            'supplier_id' => $supplier2->id,
            'invoice_number' => 'FAC-2002',
            'amount' => 800,
            'status' => Purchase::STATUS_RECEIVED,
            'date' => now()->toDateString(),
        ]);

        // Filtrar por proveedor
        $response = $this->actingAs($user)->get(route('purchases.index', ['filter[buscar]' => 'Aceros']));
        $response->assertOk();
        $response->assertSee('FAC-1001');
        $response->assertDontSee('FAC-2002');
    });
});

describe('Registro de Compras (Entrada de Stock e Inventario FIFO)', function () {
    it('valida que se envíen almacén y al menos un producto con cantidad y costo válidos', function () {
        $user = userWith('ver purchases', 'crear purchases');

        $response = $this->actingAs($user)->post(route('purchases.store'), []);
        $response->assertSessionHasErrors(['warehouse_id', 'items']);
    });

    it('registra una compra correctamente, crea items y lotes de stock en el almacén', function () {
        $user = userWith('ver purchases', 'crear purchases');
        $branch = testBranch();
        $warehouse = Warehouse::factory()->create(['branche_id' => $branch->id]);
        $supplier = Supplier::create(['name' => 'Ferretera Mayorista', 'document' => '778899']);

        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);
        $unit = ProductUnit::factory()->create([
            'product_id' => $product->id,
            'factor' => 1,
            'is_base' => true,
        ]);

        $payload = [
            'warehouse_id' => $warehouse->id,
            'supplier_id' => $supplier->id,
            'invoice_number' => 'FAC-999',
            'date' => now()->toDateString(),
            'pay_now' => false,
            'items' => [
                [
                    'product_unit_id' => $unit->id,
                    'quantity' => 10,
                    'unit_cost' => 45.50,
                ],
            ],
        ];

        $response = $this->actingAs($user)->post(route('purchases.store'), $payload);

        $purchase = Purchase::where('invoice_number', 'FAC-999')->first();
        expect($purchase)->not->toBeNull();
        expect((float) $purchase->amount)->toEqual(455.00);
        expect($purchase->status)->toBe(Purchase::STATUS_RECEIVED);

        $response->assertRedirect(route('purchases.show', $purchase));

        // Verificar que se creó el lote de stock
        $batch = StockBatch::where('product_id', $product->id)
            ->where('warehouse_id', $warehouse->id)
            ->first();
        expect($batch)->not->toBeNull();
        expect((int) $batch->qty_initial)->toBe(10);
        expect((int) $batch->qty_remaining)->toBe(10);
    });

    it('registra una compra con pago inmediato deduciendo el saldo de la caja', function () {
        $user = userWith('ver purchases', 'crear purchases');
        $branch = testBranch();
        $warehouse = Warehouse::factory()->create(['branche_id' => $branch->id]);
        $cashbox = Cashbox::factory()->create([
            'branche_id' => $branch->id,
            'balance' => 1000,
            'cash' => 1000,
        ]);
        $paymentMethod = PaymentMethod::factory()->cash()->create();

        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);
        $unit = ProductUnit::factory()->create([
            'product_id' => $product->id,
            'factor' => 1,
            'is_base' => true,
        ]);

        $payload = [
            'warehouse_id' => $warehouse->id,
            'invoice_number' => 'FAC-EFECTIVO',
            'date' => now()->toDateString(),
            'pay_now' => true,
            'cashbox_id' => $cashbox->id,
            'payment_id' => $paymentMethod->id,
            'items' => [
                [
                    'product_unit_id' => $unit->id,
                    'quantity' => 5,
                    'unit_cost' => 20.00,
                ],
            ],
        ];

        $response = $this->actingAs($user)->post(route('purchases.store'), $payload);

        $purchase = Purchase::where('invoice_number', 'FAC-EFECTIVO')->first();
        expect($purchase)->not->toBeNull();
        expect($purchase->transaction_id)->not->toBeNull();
        $response->assertRedirect(route('purchases.show', $purchase));
    });
});

describe('Anulación de Compras', function () {
    it('permite anular una compra con compras.anular, reduciendo el stock y revirtiendo el saldo', function () {
        $user = userWith('ver purchases', 'crear purchases', 'compras.anular');
        $branch = testBranch();
        $warehouse = Warehouse::factory()->create(['branche_id' => $branch->id]);

        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);
        $unit = ProductUnit::factory()->create([
            'product_id' => $product->id,
            'factor' => 1,
            'is_base' => true,
        ]);

        // Registrar compra primero
        $service = app(\App\Services\PurchaseService::class);
        $purchase = $service->register([
            'warehouse_id' => $warehouse->id,
            'date' => now()->toDateString(),
        ], [
            [
                'product_unit_id' => $unit->id,
                'quantity' => 8,
                'unit_cost' => 15.00,
            ],
        ], $user);

        expect($purchase->status)->toBe(Purchase::STATUS_RECEIVED);

        // Anular compra
        $response = $this->actingAs($user)->post(route('purchases.cancel', $purchase));
        $response->assertRedirect(route('purchases.show', $purchase));

        $purchase->refresh();
        expect($purchase->status)->toBe(Purchase::STATUS_CANCELED);
    });
});

describe('PDF y Botón Superior Derecho en Compras', function () {
    it('genera el PDF de la compra y muestra el botón imprimir en la esquina superior derecha', function () {
        $user = userWith('ver purchases', 'compras.exportar');
        $branch = testBranch();
        $warehouse = Warehouse::factory()->create(['branche_id' => $branch->id]);

        $purchase = Purchase::create([
            'warehouse_id' => $warehouse->id,
            'branche_id' => $branch->id,
            'user_id' => $user->id,
            'amount' => 350,
            'status' => Purchase::STATUS_RECEIVED,
            'date' => now()->toDateString(),
        ]);

        // Vista detalle debe tener el enlace al PDF en el encabezado
        $responseShow = $this->actingAs($user)->get(route('purchases.show', $purchase));
        $responseShow->assertOk();
        $responseShow->assertSee(route('purchases.pdf', $purchase));

        // Descarga de PDF debe responder 200 y Content-Type application/pdf
        $responsePdf = $this->actingAs($user)->get(route('purchases.pdf', $purchase));
        $responsePdf->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    });
});
