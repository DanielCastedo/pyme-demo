<?php

use App\Models\Cashbox;
use App\Models\Category;
use App\Models\Company;
use App\Models\Customer;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\SubscriptionPlan;
use App\Models\Warehouse;
use App\Services\ConsignmentService;
use App\Services\OrderService;
use App\Services\PurchaseService;
use App\Services\SaleService;
use Illuminate\Support\Facades\URL;

/*
|--------------------------------------------------------------------------
| PublicReceiptController: el QR del recibo enlaza a estas rutas públicas (sin sesión)
|--------------------------------------------------------------------------
| Las rutas van firmadas: sin la firma (o con una alterada) no cargan, así que no alcanza con
| adivinar el número de venta/pedido/consignación para ver el recibo de otro cliente.
*/

beforeEach(function () {
    $this->seed(\Database\Seeders\TypeSeeder::class);

    $company = Company::firstOrCreate(['id' => 1], ['name' => 'Brazil Importaciones', 'currency' => 'Bs']);
    $plan = SubscriptionPlan::firstOrCreate(['slug' => 'empresa'], ['name' => 'Plan Empresa', 'price' => 100, 'billing_cycle' => 'monthly', 'is_active' => true]);
    $company->subscriptions()->firstOrCreate(['company_id' => $company->id], [
        'subscription_plan_id' => $plan->id, 'status' => 'active', 'starts_at' => now()->subDay(), 'ends_at' => now()->addMonth(),
    ]);
});

/** Un almacén con stock, un cliente, una caja y un usuario con permisos de venta. */
function publicReceiptWorld(): array
{
    $branch = testBranch();
    $warehouse = Warehouse::factory()->create(['branche_id' => $branch->id]);
    $user = userWith('ver sales', 'crear sales', 'ver orders', 'crear orders', 'ver consignments', 'crear consignments');
    $customer = Customer::factory()->create();
    $category = Category::factory()->create();
    $product = Product::factory()->create(['category_id' => $category->id]);
    $unit = ProductUnit::factory()->create(['product_id' => $product->id, 'factor' => 1, 'is_base' => true, 'price' => 10]);

    app(PurchaseService::class)->register(['warehouse_id' => $warehouse->id], [
        ['product_unit_id' => $unit->id, 'quantity' => 100, 'unit_cost' => 5],
    ], $user);

    return [
        'warehouse' => $warehouse, 'user' => $user, 'customer' => $customer, 'unit' => $unit,
        'cash' => PaymentMethod::factory()->create(['is_cash' => true]),
        'box' => Cashbox::factory()->create(['branche_id' => $branch->id]),
    ];
}

// ═════════════════════════════════════════════════════════════════════════
describe('recibo público de venta', function () {
    it('sin firma responde 403', function () {
        $w = publicReceiptWorld();
        $sale = app(SaleService::class)->register([
            'warehouse_id' => $w['warehouse']->id, 'customer_id' => $w['customer']->id,
            'payment_id' => $w['cash']->id, 'cashbox_id' => $w['box']->id,
        ], [['product_unit_id' => $w['unit']->id, 'quantity' => 2]], $w['user']);

        $this->get(route('public.receipts.sale', ['sale' => $sale->id]))->assertForbidden();
    });

    it('con la firma que trae el QR del recibo, responde el PDF sin sesión', function () {
        $w = publicReceiptWorld();
        $sale = app(SaleService::class)->register([
            'warehouse_id' => $w['warehouse']->id, 'customer_id' => $w['customer']->id,
            'payment_id' => $w['cash']->id, 'cashbox_id' => $w['box']->id,
        ], [['product_unit_id' => $w['unit']->id, 'quantity' => 2]], $w['user']);

        $url = URL::signedRoute('public.receipts.sale', ['sale' => $sale->id]);

        $this->get($url)->assertOk()->assertHeader('content-type', 'application/pdf');
    });

    it('una firma alterada no pasa', function () {
        $w = publicReceiptWorld();
        $sale = app(SaleService::class)->register([
            'warehouse_id' => $w['warehouse']->id, 'customer_id' => $w['customer']->id,
            'payment_id' => $w['cash']->id, 'cashbox_id' => $w['box']->id,
        ], [['product_unit_id' => $w['unit']->id, 'quantity' => 2]], $w['user']);

        $url = URL::signedRoute('public.receipts.sale', ['sale' => $sale->id]);
        $otherSale = app(SaleService::class)->register([
            'warehouse_id' => $w['warehouse']->id, 'customer_id' => $w['customer']->id,
            'payment_id' => $w['cash']->id, 'cashbox_id' => $w['box']->id,
        ], [['product_unit_id' => $w['unit']->id, 'quantity' => 1]], $w['user']);

        // Firma de una venta usada para pedir el recibo de otra: la firma ya no coincide.
        $tampered = str_replace((string) $sale->id, (string) $otherSale->id, $url);

        $this->get($tampered)->assertForbidden();
    });

    it('una venta que no existe responde 404 aunque la firma sea válida', function () {
        $w = publicReceiptWorld();
        app(SaleService::class)->register([
            'warehouse_id' => $w['warehouse']->id, 'customer_id' => $w['customer']->id,
            'payment_id' => $w['cash']->id, 'cashbox_id' => $w['box']->id,
        ], [['product_unit_id' => $w['unit']->id, 'quantity' => 2]], $w['user']);

        $url = URL::signedRoute('public.receipts.sale', ['sale' => 999999]);

        $this->get($url)->assertNotFound();
    });
});

// ═════════════════════════════════════════════════════════════════════════
describe('recibo público de pedido', function () {
    it('con la firma que trae el QR, responde el PDF sin sesión', function () {
        $w = publicReceiptWorld();
        $pedido = app(OrderService::class)->create([
            'customer_id' => $w['customer']->id, 'warehouse_id' => $w['warehouse']->id,
            'delivery_date' => now()->addDays(3)->toDateString(),
        ], [['product_unit_id' => $w['unit']->id, 'quantity' => 2]], $w['user']);

        $url = URL::signedRoute('public.receipts.pedido', ['pedido' => $pedido->id]);

        $this->get($url)->assertOk()->assertHeader('content-type', 'application/pdf');
    });

    it('sin firma responde 403', function () {
        $w = publicReceiptWorld();
        $pedido = app(OrderService::class)->create([
            'customer_id' => $w['customer']->id, 'warehouse_id' => $w['warehouse']->id,
            'delivery_date' => now()->addDays(3)->toDateString(),
        ], [['product_unit_id' => $w['unit']->id, 'quantity' => 2]], $w['user']);

        $this->get(route('public.receipts.pedido', ['pedido' => $pedido->id]))->assertForbidden();
    });

    it('una venta directa no es un pedido: responde 404 aunque la firma sea válida', function () {
        $w = publicReceiptWorld();
        $sale = app(SaleService::class)->register([
            'warehouse_id' => $w['warehouse']->id, 'customer_id' => $w['customer']->id,
            'payment_id' => $w['cash']->id, 'cashbox_id' => $w['box']->id,
        ], [['product_unit_id' => $w['unit']->id, 'quantity' => 2]], $w['user']);

        $url = URL::signedRoute('public.receipts.pedido', ['pedido' => $sale->id]);

        $this->get($url)->assertNotFound();
    });
});

// ═════════════════════════════════════════════════════════════════════════
describe('recibo público de consignación', function () {
    it('con la firma que trae el QR, responde el PDF sin sesión', function () {
        $w = publicReceiptWorld();
        $consignment = app(ConsignmentService::class)->create([
            'customer_id' => $w['customer']->id, 'warehouse_id' => $w['warehouse']->id,
        ], [['product_unit_id' => $w['unit']->id, 'quantity' => 2]], $w['user']);

        $url = URL::signedRoute('public.receipts.consignment', ['consignment' => $consignment->id]);

        $this->get($url)->assertOk()->assertHeader('content-type', 'application/pdf');
    });

    it('sin firma responde 403', function () {
        $w = publicReceiptWorld();
        $consignment = app(ConsignmentService::class)->create([
            'customer_id' => $w['customer']->id, 'warehouse_id' => $w['warehouse']->id,
        ], [['product_unit_id' => $w['unit']->id, 'quantity' => 2]], $w['user']);

        $this->get(route('public.receipts.consignment', ['consignment' => $consignment->id]))->assertForbidden();
    });
});

// ═════════════════════════════════════════════════════════════════════════
describe('recibo público de compra', function () {
    it('con la firma que trae el QR, responde el PDF sin sesión', function () {
        $w = publicReceiptWorld();
        $purchase = app(PurchaseService::class)->register([
            'warehouse_id' => $w['warehouse']->id,
        ], [['product_unit_id' => $w['unit']->id, 'quantity' => 3, 'unit_cost' => 5]], $w['user']);

        $url = URL::signedRoute('public.receipts.purchase', ['purchase' => $purchase->id]);

        $this->get($url)->assertOk()->assertHeader('content-type', 'application/pdf');
    });

    it('sin firma responde 403', function () {
        $w = publicReceiptWorld();
        $purchase = app(PurchaseService::class)->register([
            'warehouse_id' => $w['warehouse']->id,
        ], [['product_unit_id' => $w['unit']->id, 'quantity' => 3, 'unit_cost' => 5]], $w['user']);

        $this->get(route('public.receipts.purchase', ['purchase' => $purchase->id]))->assertForbidden();
    });
});
