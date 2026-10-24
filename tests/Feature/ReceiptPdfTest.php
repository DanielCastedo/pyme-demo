<?php

use App\Models\Cashbox;
use App\Models\Category;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\SubscriptionPlan;
use App\Models\Warehouse;
use App\Services\ConsignmentService;
use App\Services\OrderPaymentService;
use App\Services\OrderService;
use App\Services\PurchaseService;
use App\Services\SaleService;

/*
|--------------------------------------------------------------------------
| Recibos (PDF): venta al contado y a crédito, consignación y pedido
|--------------------------------------------------------------------------
| Formato de factura boliviana pero titulado RECIBO (todavía no se factura): datos de la empresa y del cliente,
| detalle, total con monto en letras y un QR con el número de la transacción (o del documento si no hubo caja).
*/

beforeEach(function () {
    $this->seed(\Database\Seeders\TypeSeeder::class);

    $this->company = Company::firstOrCreate(['id' => 1], [
        'name' => 'Brazil Importaciones', 'tax_id' => '1007017028', 'address' => 'Av. Cristo Redentor 1520',
        'phone' => '3-3415000', 'city' => 'Santa Cruz', 'currency' => 'Bs',
    ]);
    $plan = SubscriptionPlan::firstOrCreate(['slug' => 'empresa'], ['name' => 'Plan Empresa', 'price' => 100, 'billing_cycle' => 'monthly', 'is_active' => true]);
    $this->company->subscriptions()->firstOrCreate(['company_id' => $this->company->id], [
        'subscription_plan_id' => $plan->id, 'status' => 'active', 'starts_at' => now()->subDay(), 'ends_at' => now()->addMonth(),
    ]);
});

/** Un almacén con stock de dos productos, un cliente, una caja y un usuario con todos los permisos de venta. */
function receiptWorld(): array
{
    $branch = testBranch();
    $branch->update(['name' => 'Central - Santa Cruz']);
    $warehouse = Warehouse::factory()->create(['branche_id' => $branch->id, 'name' => 'Almacén Central']);
    $user = userWith(
        'ver sales', 'crear sales', 'ventas.exportar', 'ventas.credito', 'ventas.descuento', 'ventas.editar-precio', 'ventas.anular',
        'ver orders', 'crear orders', 'ver consignments', 'crear consignments', 'consignaciones.exportar'
    );
    $customer = Customer::factory()->create(['name' => 'Transportes Bolivia S.A.', 'document' => '1023456029']);
    $category = Category::factory()->create();

    $product = function (string $name, string $sku, float $price) use ($category, $warehouse, $user) {
        $p = Product::factory()->create(['category_id' => $category->id, 'name' => $name, 'sku' => $sku]);
        $u = ProductUnit::factory()->create(['product_id' => $p->id, 'name' => 'Unidad', 'factor' => 1, 'is_base' => true, 'price' => $price]);
        app(PurchaseService::class)->register(['warehouse_id' => $warehouse->id], [['product_unit_id' => $u->id, 'quantity' => 500, 'unit_cost' => $price / 2]], $user);

        return $u;
    };

    return [
        'branch' => $branch, 'warehouse' => $warehouse, 'user' => $user, 'customer' => $customer,
        'tornillo' => $product('Tornillo autorroscante', 'FG-0001', 1.0),
        'martillo' => $product('Martillo de uña 16oz', 'HER-0210', 55.0),
        'cash' => PaymentMethod::factory()->create(['is_cash' => true, 'name' => 'Efectivo']),
        'box' => Cashbox::factory()->create(['branche_id' => $branch->id, 'balance' => 0, 'cash' => 0, 'name' => 'Caja Principal']),
    ];
}

/** Líneas de venta con un 10 % de descuento en la primera. */
function receiptLines(array $w): array
{
    return [
        ['product_unit_id' => $w['tornillo']->id, 'quantity' => 92, 'discount_type' => 'percent', 'discount_value' => 10],
        ['product_unit_id' => $w['martillo']->id, 'quantity' => 3],
    ];
}

function cashReceiptSale(array $w): Order
{
    return app(SaleService::class)->register([
        'warehouse_id' => $w['warehouse']->id, 'customer_id' => $w['customer']->id,
        'payment_id' => $w['cash']->id, 'cashbox_id' => $w['box']->id, 'note' => 'Entrega en obra',
    ], receiptLines($w), $w['user']);
}

function creditReceiptSale(array $w, float $advance = 100): Order
{
    return app(SaleService::class)->register([
        'warehouse_id' => $w['warehouse']->id, 'customer_id' => $w['customer']->id, 'payment_type' => 'credit',
        'due_date' => now()->addDays(15)->toDateString(),
        'initial_payment' => $advance, 'payment_id' => $w['cash']->id, 'cashbox_id' => $w['box']->id,
    ], receiptLines($w), $w['user']);
}

/** HTML del recibo de una venta (lo mismo que se convierte en PDF). */
function saleReceiptHtml(Order $sale): string
{
    $sale->load('customer', 'user', 'warehouse', 'branch', 'items.product', 'items.productUnit', 'payments.transaction.paymentMethod', 'payments.transaction.cashbox');

    return view('sales.pdf', ['sale' => $sale, 'company' => Company::first()])->render();
}

// ═════════════════════════════════════════════════════════════════════════
describe('venta al contado', function () {
    it('se genera como PDF', function () {
        $w = receiptWorld();
        $sale = cashReceiptSale($w);

        $this->actingAs($w['user'])->get(route('sales.pdf', $sale))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    });

    it('se titula RECIBO y nunca dice factura', function () {
        $w = receiptWorld();
        $html = saleReceiptHtml(cashReceiptSale($w));

        expect($html)->toContain('RECIBO')
            ->and($html)->toContain('(Venta al contado)')
            ->and(mb_strtolower(strip_tags($html)))->not->toContain('factura');
    });

    it('muestra el nombre de la sucursal (sin repetir el de la empresa), el NIT y el cliente', function () {
        $w = receiptWorld();
        $html = saleReceiptHtml(cashReceiptSale($w));

        expect($html)->toContain('Central - Santa Cruz')
            ->and($html)->not->toContain('SUCURSAL')
            ->and($html)->not->toContain('Brazil Importaciones')
            ->and($html)->toContain('1007017028')                    // NIT de la empresa (tax_id)
            ->and($html)->toContain('Av. Cristo Redentor 1520')
            ->and($html)->toContain('SANTA CRUZ')
            ->and($html)->toContain('Transportes Bolivia S.A.')
            ->and($html)->toContain('1023456029')
            ->and($html)->toContain('Cod. Cliente');
    });

    it('detalla código, cantidad, unidad, descripción, precio, descuento y subtotal', function () {
        $w = receiptWorld();
        $html = saleReceiptHtml(cashReceiptSale($w));

        expect($html)->toContain('FG-0001')
            ->and($html)->toContain('Tornillo autorroscante')
            ->and($html)->toContain('82.80')      // 92 × 1.00 - 10 % (9.20)
            ->and($html)->toContain('9.20')
            ->and($html)->toContain('165.00')     // 3 × 55.00
            ->and($html)->toContain('SUBTOTAL BS')
            ->and($html)->toContain('DESCUENTO BS')
            ->and($html)->toContain('TOTAL BS')
            ->and($html)->toContain('247.80');
    });

    it('escribe el total en letras', function () {
        $w = receiptWorld();

        expect(saleReceiptHtml(cashReceiptSale($w)))->toContain('Son: Doscientos cuarenta y siete 80/100 Bolivianos');
    });

    it('la referencia lleva el número de la transacción de caja y el QR enlaza al recibo público', function () {
        $w = receiptWorld();
        $sale = cashReceiptSale($w);
        $transaction = $sale->payments()->firstOrFail()->transaction;
        $html = saleReceiptHtml($sale);
        $url = \Illuminate\Support\Facades\URL::signedRoute('public.receipts.sale', ['sale' => $sale->id]);

        expect($transaction->number)->toStartWith('TRX-')
            ->and($html)->toContain('TRANSACCIÓN')
            ->and($html)->toContain($transaction->number)
            ->and($html)->toContain('data:image/svg+xml;base64,')
            ->and($html)->toContain(\App\Support\ReceiptQr::dataUri($url))
            ->and($html)->toContain('<div class="cap">Ver recibo</div>');
    });

    it('el QR es un link firmado al recibo público de la venta (sin sesión)', function () {
        $w = receiptWorld();
        $sale = cashReceiptSale($w);
        $url = \Illuminate\Support\Facades\URL::signedRoute('public.receipts.sale', ['sale' => $sale->id]);

        expect($url)->toContain('/recibo/venta/'.$sale->id)->and($url)->toContain('signature=')
            ->and(saleReceiptHtml($sale))->toContain(\App\Support\ReceiptQr::dataUri($url));
    });

    it('no incluye firmas ni saldo (es al contado)', function () {
        $w = receiptWorld();
        $html = saleReceiptHtml(cashReceiptSale($w));

        expect($html)->not->toContain('SALDO BS')->and($html)->not->toContain('Conforme Cliente');
    });
});

// ═════════════════════════════════════════════════════════════════════════
describe('venta a crédito', function () {
    it('muestra a cuenta, saldo, vencimiento y firmas', function () {
        $w = receiptWorld();
        $html = saleReceiptHtml(creditReceiptSale($w, 100));

        expect($html)->toContain('(Venta a crédito)')
            ->and($html)->toContain('A CUENTA BS')
            ->and($html)->toContain('100.00')
            ->and($html)->toContain('SALDO BS')
            ->and($html)->toContain('147.80')     // 247.80 - 100
            ->and($html)->toContain('Vencimiento')
            ->and($html)->toContain(now()->addDays(15)->format('d/m/Y'))
            ->and($html)->toContain('Conforme Cliente')
            ->and(mb_strtolower(strip_tags($html)))->not->toContain('factura');
    });

    it('con varios abonos, la referencia lista todas las transacciones y el QR sigue enlazando al recibo', function () {
        $w = receiptWorld();
        $sale = creditReceiptSale($w, 100);
        app(OrderPaymentService::class)->collect($sale, 50, $w['cash'], $w['box'], $w['user']);

        $numbers = $sale->fresh()->payments()->with('transaction')->get()->pluck('transaction.number');
        $html = saleReceiptHtml($sale->fresh());
        $url = \Illuminate\Support\Facades\URL::signedRoute('public.receipts.sale', ['sale' => $sale->id]);

        expect($numbers)->toHaveCount(2)
            ->and($html)->toContain($numbers->implode(' · '))
            ->and($html)->toContain(\App\Support\ReceiptQr::dataUri($url))
            ->and($html)->toContain('SALDO BS')
            ->and($html)->toContain('97.80');     // 247.80 - 150
    });

    it('un cobro anulado no aparece en el QR ni en la lista de cobros', function () {
        $w = receiptWorld();
        $sale = creditReceiptSale($w, 100);
        $extra = app(OrderPaymentService::class)->collect($sale, 50, $w['cash'], $w['box'], $w['user']);
        $canceledNumber = $extra->transaction->number;

        app(OrderPaymentService::class)->cancelPayment($extra);

        expect(saleReceiptHtml($sale->fresh()))->not->toContain($canceledNumber);
    });

    it('sin cobros, el QR sigue enlazando al recibo público de la venta', function () {
        $w = receiptWorld();
        $sale = app(SaleService::class)->register([
            'warehouse_id' => $w['warehouse']->id, 'customer_id' => $w['customer']->id, 'payment_type' => 'credit',
            'due_date' => now()->addDays(10)->toDateString(),
        ], receiptLines($w), $w['user']);
        $html = saleReceiptHtml($sale);
        $url = \Illuminate\Support\Facades\URL::signedRoute('public.receipts.sale', ['sale' => $sale->id]);

        expect($sale->payments()->count())->toBe(0)
            ->and($html)->toContain(\App\Support\ReceiptQr::dataUri($url))
            ->and($html)->not->toContain('TRANSACCIÓN</td>')
            ->and($html)->toContain('SALDO BS');
    });

    it('se genera como PDF', function () {
        $w = receiptWorld();

        $this->actingAs($w['user'])->get(route('sales.pdf', creditReceiptSale($w)))
            ->assertOk()->assertHeader('content-type', 'application/pdf');
    });
});

// ═════════════════════════════════════════════════════════════════════════
describe('consignación', function () {
    function receiptConsignment(array $w)
    {
        return app(ConsignmentService::class)->create([
            'customer_id' => $w['customer']->id, 'warehouse_id' => $w['warehouse']->id,
            'due_date' => now()->addDays(30)->toDateString(), 'note' => 'Devolver lo no vendido en 30 días.',
        ], [
            ['product_unit_id' => $w['tornillo']->id, 'quantity' => 40],
            ['product_unit_id' => $w['martillo']->id, 'quantity' => 5],
        ], $w['user']);
    }

    function consignmentReceiptHtml($consignment): string
    {
        $consignment->load('customer', 'warehouse', 'branch', 'user', 'items.product', 'items.productUnit');

        return view('consignments.pdf', ['consignment' => $consignment, 'company' => Company::first()])->render();
    }

    it('se genera como PDF', function () {
        $w = receiptWorld();

        $this->actingAs($w['user'])->get(route('consignments.pdf', receiptConsignment($w)))
            ->assertOk()->assertHeader('content-type', 'application/pdf');
    });

    it('se titula RECIBO con el formato nuevo y sin la palabra factura', function () {
        $w = receiptWorld();
        $html = consignmentReceiptHtml(receiptConsignment($w));

        expect($html)->toContain('RECIBO')
            ->and($html)->toContain('(Entrega de mercadería en consignación)')
            ->and($html)->toContain('Transportes Bolivia S.A.')
            ->and($html)->toContain('Central - Santa Cruz')
            ->and(mb_strtolower(strip_tags($html)))->not->toContain('factura');
    });

    it('muestra lo entregado, vendido y pendiente con sus cantidades reales', function () {
        $w = receiptWorld();
        $html = consignmentReceiptHtml(receiptConsignment($w));

        // Antes "Entregado" y "Vendido" salían siempre en 0 (leía atributos que no existen)
        expect($html)->toContain('<td class="rc-c rc-b">40</td>')
            ->and($html)->toContain('<td class="rc-c rc-b">5</td>')
            ->and($html)->toContain('TOTAL ENTREGADO BS')
            ->and($html)->toContain('315.00')          // 40 × 1 + 5 × 55
            ->and($html)->toContain('Son: Trescientos quince 00/100 Bolivianos');
    });

    it('el QR enlaza al recibo público de la consignación', function () {
        $w = receiptWorld();
        $consignment = receiptConsignment($w);
        $html = consignmentReceiptHtml($consignment);
        $url = \Illuminate\Support\Facades\URL::signedRoute('public.receipts.consignment', ['consignment' => $consignment->id]);

        expect($html)->toContain(\App\Support\ReceiptQr::dataUri($url))
            ->and($html)->toContain('<div class="cap">Ver recibo</div>')
            ->and($html)->toContain('Recibí conforme');
    });
});

// ═════════════════════════════════════════════════════════════════════════
describe('pedido', function () {
    function receiptOrder(array $w): Order
    {
        return app(OrderService::class)->create([
            'customer_id' => $w['customer']->id, 'warehouse_id' => $w['warehouse']->id,
            'delivery_date' => now()->addDays(3)->toDateString(), 'note' => 'Llamar antes de entregar',
        ], receiptLines($w), $w['user']);
    }

    it('se genera como PDF con el permiso de exportar ventas', function () {
        $w = receiptWorld();
        $pedido = receiptOrder($w);

        $this->actingAs($w['user'])->get(route('pedidos.pdf', $pedido))
            ->assertOk()->assertHeader('content-type', 'application/pdf');
    });

    it('exige "ver orders" y "ventas.exportar"', function () {
        $w = receiptWorld();
        $pedido = receiptOrder($w);

        $this->actingAs(userWith('ver orders'))->get(route('pedidos.pdf', $pedido))->assertForbidden();
        $this->actingAs(userWith('ventas.exportar'))->get(route('pedidos.pdf', $pedido))->assertForbidden();
    });

    it('responde 404 si la orden no es un pedido (una venta directa)', function () {
        $w = receiptWorld();
        $sale = cashReceiptSale($w);

        $this->actingAs($w['user'])->get(route('pedidos.pdf', $sale))->assertNotFound();
    });

    it('lleva el formato de recibo con la fecha de entrega y su QR', function () {
        $w = receiptWorld();
        $pedido = receiptOrder($w)->load('customer', 'user', 'warehouse', 'branch', 'items.product', 'items.productUnit');
        $html = view('pedidos.pdf', ['pedido' => $pedido, 'company' => Company::first()])->render();

        $url = \Illuminate\Support\Facades\URL::signedRoute('public.receipts.pedido', ['pedido' => $pedido->id]);

        expect($html)->toContain('RECIBO')
            ->and($html)->toContain('(Pedido en espera de entrega)')
            ->and($html)->toContain(now()->addDays(3)->format('d/m/Y'))
            ->and($html)->toContain(\App\Support\ReceiptQr::dataUri($url))
            ->and($html)->toContain('<div class="cap">Ver recibo</div>')
            ->and($html)->toContain('Son: Doscientos cuarenta y siete 80/100 Bolivianos')
            ->and(mb_strtolower(strip_tags($html)))->not->toContain('factura');
    });

    it('el detalle del pedido ofrece el botón del PDF solo con permiso', function () {
        $w = receiptWorld();
        $pedido = receiptOrder($w);

        $this->actingAs($w['user'])->get(route('pedidos.show', $pedido))->assertOk()->assertSee(route('pedidos.pdf', $pedido), false);

        $soloVer = userWith('ver orders');
        $this->actingAs($soloVer)->get(route('pedidos.show', $pedido))->assertOk()->assertDontSee(route('pedidos.pdf', $pedido), false);
    });
});

// ═════════════════════════════════════════════════════════════════════════
describe('componente', function () {
    it('funciona sin empresa registrada (usa el nombre de la sucursal)', function () {
        $w = receiptWorld();
        $sale = cashReceiptSale($w)->load('customer', 'user', 'warehouse', 'branch', 'items.product', 'items.productUnit', 'payments.transaction.paymentMethod', 'payments.transaction.cashbox');

        $html = view('sales.pdf', ['sale' => $sale, 'company' => null])->render();

        expect($html)->toContain($w['branch']->name)->and($html)->toContain('RECIBO')->and($html)->not->toContain('NIT</td>');
    });

    it('sin empresa ni sucursal, usa el nombre de la aplicación', function () {
        $w = receiptWorld();
        $sale = cashReceiptSale($w)->load('customer', 'user', 'warehouse', 'items.product', 'items.productUnit', 'payments.transaction.paymentMethod', 'payments.transaction.cashbox');
        $sale->setRelation('branch', null);

        $html = view('sales.pdf', ['sale' => $sale, 'company' => null])->render();

        expect($html)->toContain(config('app.name'))->and($html)->toContain('RECIBO');
    });

    it('muestra el logo por defecto (logo-pymes.png) en el encabezado cuando la sucursal no tiene logo', function () {
        $w = receiptWorld();
        $html = saleReceiptHtml(cashReceiptSale($w));

        expect($html)->toContain('alt="Logo"')
            ->and($html)->toContain(\App\Support\ReceiptLogo::dataUri(null))
            ->and(strlen(\App\Support\ReceiptLogo::dataUri(null)))->toBeLessThan(150_000); // liviano: no se incrusta el original de ~1 MB
    });

    it('usa el logo de la sucursal (aunque esté en WebP) y el PDF se genera', function () {
        $w = receiptWorld();
        $sale = cashReceiptSale($w);

        \Illuminate\Support\Facades\Storage::fake('uploads');
        $image = imagecreatetruecolor(400, 200);
        imagefill($image, 0, 0, imagecolorallocate($image, 5, 150, 105));
        ob_start();
        imagewebp($image, null, 90);
        \Illuminate\Support\Facades\Storage::disk('uploads')->put('logos/sucursal.webp', ob_get_clean());
        $branch = $w['branch'];
        $branch->update(['logo' => 'logos/sucursal.webp']);

        $own = \App\Support\ReceiptLogo::dataUri($branch->fresh());

        expect($own)->toStartWith('data:image/png;base64,')
            ->and($own)->not->toBe(\App\Support\ReceiptLogo::dataUri(null))
            ->and(saleReceiptHtml($sale->fresh()))->toContain($own);

        $this->actingAs($w['user'])->get(route('sales.pdf', $sale))->assertOk()->assertHeader('content-type', 'application/pdf');
    });

    it('si el archivo del logo de la sucursal ya no existe, vuelve al logo por defecto', function () {
        \Illuminate\Support\Facades\Storage::fake('uploads');
        $branch = testBranch();
        $branch->update(['logo' => 'logos/borrado.webp']);

        expect(\App\Support\ReceiptLogo::dataUri($branch->fresh()))->toBe(\App\Support\ReceiptLogo::dataUri(null));
    });

    it('el logo también sale en los recibos de consignación y de pedido', function () {
        $w = receiptWorld();

        $consignment = app(\App\Services\ConsignmentService::class)->create([
            'customer_id' => $w['customer']->id, 'warehouse_id' => $w['warehouse']->id,
        ], [['product_unit_id' => $w['tornillo']->id, 'quantity' => 5]], $w['user'])
            ->load('customer', 'warehouse', 'branch', 'user', 'items.product', 'items.productUnit');
        $pedido = app(OrderService::class)->create([
            'customer_id' => $w['customer']->id, 'warehouse_id' => $w['warehouse']->id, 'delivery_date' => now()->addDay()->toDateString(),
        ], receiptLines($w), $w['user'])->load('customer', 'user', 'warehouse', 'branch', 'items.product', 'items.productUnit');

        $company = Company::first();

        expect(view('consignments.pdf', ['consignment' => $consignment, 'company' => $company])->render())->toContain('alt="Logo"')
            ->and(view('pedidos.pdf', ['pedido' => $pedido, 'company' => $company])->render())->toContain('alt="Logo"');
    });

    it('un cliente sin datos sale como S/N', function () {
        $w = receiptWorld();
        $sale = app(SaleService::class)->register([
            'warehouse_id' => $w['warehouse']->id, 'payment_id' => $w['cash']->id, 'cashbox_id' => $w['box']->id,
        ], receiptLines($w), $w['user']);

        expect(saleReceiptHtml($sale))->toContain('NIT/CI/CEX:</span> S/N')->and(saleReceiptHtml($sale))->toContain('Cod. Cliente:</span> N/A');
    });
});
