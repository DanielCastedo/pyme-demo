<?php

use App\Models\Cashbox;
use App\Models\Category;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchasePayment;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\PurchaseService;

/*
|--------------------------------------------------------------------------
| Compras a crédito: abonos con N° de recibo y seguimiento del saldo
|--------------------------------------------------------------------------
| Cada factura del proveedor lleva sus pagos parciales con el N° de recibo,
| se ve cuánto queda pendiente y el sistema impide pagar de más: una factura
| de Bs 1.000 no puede terminar pagándose Bs 1.120 (pago que pasa el saldo,
| recibo cargado dos veces o factura registrada dos veces).
*/

beforeEach(function () {
    $this->seed(Database\Seeders\TypeSeeder::class);
    $this->warehouse = Warehouse::factory()->create(['branche_id' => testBranch()->id]);
    $this->cashbox = Cashbox::create(['name' => 'Caja Principal', 'branche_id' => testBranch()->id, 'cash' => 5000, 'balance' => 5000]);
    $this->cash = PaymentMethod::create(['name' => 'Efectivo', 'is_cash' => true]);
    $this->supplier = Supplier::create(['name' => 'Droguería Andina', 'document' => '3049582019']);
    $product = Product::create(['sku' => 'MED-0001', 'name' => 'Paracetamol', 'category_id' => Category::factory()->create()->id, 'is_active' => true]);
    $this->unit = $product->units()->create(['name' => 'Caja', 'factor' => 1, 'price' => 40, 'is_base' => true, 'barcode' => 'MED-0001']);
    $this->user = userWith('ver purchases', 'crear purchases', 'compras.credito', 'compras.pagar', 'ver suppliers');
});

/** Factura a crédito de Bs 1.000 (100 cajas a Bs 10) del proveedor, sin anticipo. */
function creditInvoice(string $invoice = 'FAC-0457'): Purchase
{
    return app(PurchaseService::class)->register([
        'warehouse_id' => test()->warehouse->id, 'supplier_id' => test()->supplier->id, 'invoice_number' => $invoice,
        'payment_type' => 'credit', 'due_date' => now()->addDays(30)->toDateString(),
    ], [['product_unit_id' => test()->unit->id, 'quantity' => 100, 'unit_cost' => 10]], test()->user);
}

function payInvoice(Purchase $purchase, $amount, ?string $receipt)
{
    return test()->actingAs(test()->user)->post(route('purchases.payments.store', $purchase), [
        'amount' => $amount, 'payment_id' => test()->cash->id, 'cashbox_id' => test()->cashbox->id, 'receipt_number' => $receipt,
    ]);
}

it('cada abono guarda su N° de recibo y el saldo baja hasta quedar pagada', function () {
    $purchase = creditInvoice();

    payInvoice($purchase, 400, 'REC-0101')->assertSessionHas('success', fn ($m) => str_contains($m, 'Saldo pendiente: 600.00'));
    payInvoice($purchase, 600, 'REC-0102')->assertSessionHas('success', fn ($m) => str_contains($m, 'pagada por completo'));

    expect($purchase->fresh()->balance)->toBe(0.0)
        ->and($purchase->payments()->pluck('receipt_number')->all())->toBe(['REC-0101', 'REC-0102']);
});

it('una factura de Bs 1.000 no se puede pagar Bs 1.120: el abono que pasa el saldo se rechaza', function () {
    $purchase = creditInvoice();
    payInvoice($purchase, 600, 'REC-0101');

    // Un abono que llevaría lo pagado a 1.120 (600 + 520) se rechaza y avisa el saldo real.
    payInvoice($purchase, 520, 'REC-0102')->assertSessionHas('error', fn ($e) => str_contains($e, 'supera el saldo de la compra (400.00)'));

    // Con la factura ya saldada, ni siquiera se puede registrar otro pago.
    payInvoice($purchase, 400, 'REC-0102')->assertSessionHas('success');
    payInvoice($purchase, 120, 'REC-0103')->assertForbidden();

    expect((float) $purchase->fresh()->paid_amount)->toBe(1000.0)
        ->and(PurchasePayment::count())->toBe(2);
});

it('no deja registrar dos veces el mismo recibo del proveedor, aunque sea en otra factura', function () {
    $first = creditInvoice('FAC-0457');
    $second = creditInvoice('FAC-0458');
    payInvoice($first, 300, 'REC-0101');

    payInvoice($second, 300, 'rec-0101') // mismo recibo, distinta mayúscula
        ->assertSessionHas('error', fn ($e) => str_contains($e, 'REC-0101') || str_contains($e, 'rec-0101'))
        ->assertSessionHas('error', fn ($e) => str_contains($e, 'ya está registrado') && str_contains($e, "compra #{$first->id}"));

    expect((float) $second->fresh()->paid_amount)->toBe(0.0);
});

it('un recibo anulado se puede volver a cargar (por ejemplo si se registró con otro monto)', function () {
    $this->user->givePermissionTo(\Spatie\Permission\Models\Permission::findOrCreate('compras.anular', 'web'));
    $purchase = creditInvoice();
    payInvoice($purchase, 300, 'REC-0101');
    $this->delete(route('purchases.payments.destroy', [$purchase, $purchase->payments()->first()]))->assertSessionHas('success');

    payInvoice($purchase, 350, 'REC-0101')->assertSessionHas('success');

    expect((float) $purchase->fresh()->paid_amount)->toBe(350.0);
});

it('no deja registrar dos veces la misma factura del mismo proveedor', function () {
    $first = creditInvoice('FAC-0457');

    expect(fn () => creditInvoice('fac-0457'))
        ->toThrow(RuntimeException::class, "ya está registrada en la compra #{$first->id}");

    expect(Purchase::count())->toBe(1);
});

it('el anticipo de la compra a crédito guarda su N° de recibo', function () {
    $purchase = app(PurchaseService::class)->register([
        'warehouse_id' => $this->warehouse->id, 'supplier_id' => $this->supplier->id, 'invoice_number' => 'FAC-0500',
        'payment_type' => 'credit', 'due_date' => now()->addDays(30)->toDateString(),
        'initial_payment' => 200, 'initial_receipt_number' => 'REC-0900', 'payment_id' => $this->cash->id, 'cashbox_id' => $this->cashbox->id,
    ], [['product_unit_id' => $this->unit->id, 'quantity' => 100, 'unit_cost' => 10]], $this->user);

    expect($purchase->payments()->sole()->receipt_number)->toBe('REC-0900')
        ->and($purchase->balance)->toBe(800.0);
});

it('el proveedor muestra su estado de cuenta: facturas, pagos con N° de recibo y saldo pendiente', function () {
    $a = creditInvoice('FAC-0457');
    creditInvoice('FAC-0458');
    payInvoice($a, 400, 'REC-0101');

    $this->actingAs($this->user)->get(route('suppliers.show', $this->supplier))
        ->assertOk()
        ->assertSeeInOrder(['Estado de cuenta', 'Facturado a crédito', '2,000.00', 'Pagado', '400.00', 'Saldo pendiente', '1,600.00'])
        ->assertSeeInOrder(['Factura FAC-0457', '1,000.00', 'Recibo REC-0101', '400.00', '600.00']);
});

it('cuentas por pagar muestra la factura y se puede buscar por su número', function () {
    creditInvoice('FAC-0457');
    creditInvoice('FAC-0999');

    $this->actingAs($this->user)->get(route('payables.index', ['filter' => ['buscar' => 'FAC-0999']]))
        ->assertOk()
        ->assertSee('FAC-0999')
        ->assertDontSee('FAC-0457');
});
