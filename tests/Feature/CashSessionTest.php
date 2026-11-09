<?php

use App\Models\Cashbox;
use App\Models\CashSession;
use App\Models\Category;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductWarehouse;
use App\Models\Transaction;
use App\Models\Type;
use App\Models\Warehouse;
use App\Services\CashSessionService;
use App\Services\SaleService;
use App\Services\TransactionService;

/*
|--------------------------------------------------------------------------
| Apertura y cierre de caja por usuario
|--------------------------------------------------------------------------
| El vendedor abre su caja con un fondo para dar cambio (ej. Bs 100). Ese
| fondo es efectivo que ya está en la caja: no es una venta, no cambia el
| saldo y sigue siendo parte de la caja. Al cerrar se compara el efectivo
| esperado (fondo + ventas en efectivo − salidas) con el contado.
*/

beforeEach(function () {
    config(['caja.require_open_session' => true]); // las demás pruebas cobran sin caja abierta (phpunit.xml)
    $this->seed(Database\Seeders\TypeSeeder::class);
    $this->warehouse = Warehouse::factory()->create(['branche_id' => testBranch()->id]);
    $this->cashbox = Cashbox::create(['name' => 'Caja Principal', 'branche_id' => testBranch()->id, 'cash' => 500, 'balance' => 500]);
    $this->cash = PaymentMethod::create(['name' => 'Efectivo', 'is_cash' => true]);
    $this->qr = PaymentMethod::create(['name' => 'QR Simple', 'is_cash' => false]);
    $product = Product::create(['sku' => 'MED-0001', 'name' => 'Paracetamol', 'category_id' => Category::factory()->create()->id, 'is_active' => true]);
    $this->unit = $product->units()->create(['name' => 'Caja', 'factor' => 1, 'price' => 10, 'is_base' => true, 'barcode' => 'MED-0001']);
    ProductWarehouse::receiveStock($product->id, $this->warehouse->id, 100, 4);
    $this->seller = userWith('ver sales', 'crear sales');
});

function sellFor($user, int $qty, $method): \App\Models\Order
{
    return app(SaleService::class)->register(
        ['warehouse_id' => test()->warehouse->id, 'cashbox_id' => test()->cashbox->id, 'payment_id' => $method->id],
        [['product_unit_id' => test()->unit->id, 'quantity' => $qty]],
        $user
    );
}

it('abrir la caja con Bs 100 de fondo no es una venta: no crea transacción ni cambia el saldo de la caja', function () {
    $session = app(CashSessionService::class)->open($this->seller, $this->cashbox, 100, 'Fondo para cambio');

    expect($session->isOpen())->toBeTrue()
        ->and((float) $session->opening_amount)->toBe(100.0)
        ->and(Transaction::count())->toBe(0)
        ->and((float) $this->cashbox->fresh()->cash)->toBe(500.0)
        ->and((float) $this->cashbox->fresh()->balance)->toBe(500.0)
        ->and($session->summary()['sales_total'])->toBe('0.00');
});

it('sin la caja abierta no se puede cobrar una venta; con la caja abierta el cobro queda en el turno', function () {
    expect(fn () => sellFor($this->seller, 1, $this->cash))->toThrow(RuntimeException::class, 'Abre tu caja antes de cobrar');

    $session = app(CashSessionService::class)->open($this->seller, $this->cashbox, 100);
    $sale = sellFor($this->seller, 2, $this->cash);

    expect($sale->payments()->first()->transaction->cash_session_id)->toBe($session->id);
});

it('el cierre cuadra el efectivo: fondo + ventas en efectivo − salidas, y registra el faltante', function () {
    $session = app(CashSessionService::class)->open($this->seller, $this->cashbox, 100);
    sellFor($this->seller, 5, $this->cash); // 50 en efectivo
    sellFor($this->seller, 3, $this->qr);   // 30 por QR: no es efectivo
    app(TransactionService::class)->register([ // 20 de gasto en efectivo durante el turno
        'type_id' => Type::where('code', 'GTO')->value('id'), 'payment_id' => $this->cash->id, 'cashbox_id' => $this->cashbox->id,
        'amount' => 20, 'memo' => 'Bolsas', 'user_id' => $this->seller->id,
    ]);

    $summary = $session->summary();
    expect($summary['opening'])->toBe('100.00')
        ->and($summary['cash_in'])->toBe('50.00')
        ->and($summary['other_in'])->toBe('30.00')
        ->and($summary['cash_out'])->toBe('20.00')
        ->and($summary['sales_total'])->toBe('80.00') // el fondo no cuenta como venta
        ->and($summary['expected_cash'])->toBe('130.00')
        ->and($summary['by_method'])->toEqual(['Efectivo' => '50.00', 'QR Simple' => '30.00']);

    $closed = app(CashSessionService::class)->close($session, 125, $this->seller, 'Faltaron monedas');

    expect($closed->isOpen())->toBeFalse()
        ->and((float) $closed->expected_cash)->toBe(130.0)
        ->and((float) $closed->difference)->toBe(-5.0)
        ->and($closed->difference_label)->toBe('Faltante 5.00');

    // Ya cerrada, no se puede seguir cobrando en ese turno.
    expect(fn () => sellFor($this->seller, 1, $this->cash))->toThrow(RuntimeException::class, 'Abre tu caja');
});

it('no deja abrir dos cajas a la vez ni un fondo mayor al efectivo disponible', function () {
    $other = userWith('ver sales', 'crear sales');

    expect(fn () => app(CashSessionService::class)->open($this->seller, $this->cashbox, 600))
        ->toThrow(RuntimeException::class, 'no alcanza para un fondo de 600.00');

    app(CashSessionService::class)->open($this->seller, $this->cashbox, 300);

    expect(fn () => app(CashSessionService::class)->open($this->seller, $this->cashbox, 10))
        ->toThrow(RuntimeException::class, 'Ya tienes abierta la caja');

    // Otro vendedor en la misma caja: solo queda disponible lo que no está comprometido en el otro fondo.
    expect(fn () => app(CashSessionService::class)->open($other, $this->cashbox, 250))
        ->toThrow(RuntimeException::class, 'tiene 200.00 en efectivo disponible');
});

it('cada vendedor tiene su propio turno en la misma caja', function () {
    $other = userWith('ver sales', 'crear sales');
    $mine = app(CashSessionService::class)->open($this->seller, $this->cashbox, 100);
    $theirs = app(CashSessionService::class)->open($other, $this->cashbox, 50);

    sellFor($this->seller, 2, $this->cash);
    sellFor($other, 7, $this->cash);

    expect($mine->summary()['expected_cash'])->toBe('120.00')
        ->and($theirs->summary()['expected_cash'])->toBe('120.00');
});

it('desde Mi caja se abre y se cierra por formulario, y el POS avisa si falta abrir la caja', function () {
    $this->actingAs($this->seller)->get(route('sales.create'))->assertOk()->assertSee('Abrir mi caja');

    $this->post(route('cash-sessions.store'), ['cashbox_id' => $this->cashbox->id, 'opening_amount' => 100])
        ->assertRedirect(route('cash-sessions.index'))->assertSessionHas('success');
    $session = CashSession::sole();

    $this->get(route('sales.create'))->assertOk()->assertSee('Cobras en tu caja');
    sellFor($this->seller, 3, $this->cash);

    $this->get(route('cash-sessions.index'))->assertOk()
        ->assertSeeInOrder(['Caja abierta: Caja Principal', 'Fondo inicial', '100.00', 'Ventas en efectivo', '30.00', 'Efectivo esperado', '130.00']);

    $this->post(route('cash-sessions.close', $session), ['counted_cash' => 130])
        ->assertRedirect(route('cash-sessions.show', $session))
        ->assertSessionHas('success', fn ($m) => str_contains($m, 'Cuadra'));

    $this->get(route('cash-sessions.show', $session))->assertOk()->assertSee('Cuadra');
});

it('otro vendedor no ve ni cierra mi caja; quien administra cajas sí', function () {
    $session = app(CashSessionService::class)->open($this->seller, $this->cashbox, 100);

    $this->actingAs(userWith('ver sales', 'crear sales'))
        ->get(route('cash-sessions.show', $session))->assertForbidden();
    $this->post(route('cash-sessions.close', $session), ['counted_cash' => 100])->assertForbidden();

    $this->actingAs(userWith('ver cashboxes'))
        ->post(route('cash-sessions.close', $session), ['counted_cash' => 100])
        ->assertRedirect(route('cash-sessions.show', $session));

    expect($session->fresh()->isOpen())->toBeFalse();
});
