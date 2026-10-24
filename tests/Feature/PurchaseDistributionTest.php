<?php

use App\Models\Cashbox;
use App\Models\Category;
use App\Models\Company;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Purchase;
use App\Models\StockBatch;
use App\Models\SubscriptionPlan;
use App\Models\Type;
use App\Models\Warehouse;
use App\Services\PurchaseService;
use App\Services\StockMovementService;

/*
|--------------------------------------------------------------------------
| Distribución de una compra entre almacenes
|--------------------------------------------------------------------------
| Un producto comprado puede repartirse entre varios almacenes (ej. 8 unidades = 5 al almacén A y 3 al B):
| una sola compra y un solo pago, y cada almacén recibe su parte con su propia entrada de stock.
*/

beforeEach(function () {
    $this->seed(\Database\Seeders\TypeSeeder::class);

    $company = Company::firstOrCreate(['id' => 1], ['name' => 'Ferretería Brazil Importaciones', 'nit' => '1029384756', 'currency' => 'Bs']);
    $plan = SubscriptionPlan::firstOrCreate(['slug' => 'empresa'], ['name' => 'Plan Empresa', 'price' => 100, 'billing_cycle' => 'monthly', 'is_active' => true]);
    $company->subscriptions()->firstOrCreate(['company_id' => $company->id], [
        'subscription_plan_id' => $plan->id,
        'status' => 'active',
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addMonth(),
    ]);
});

/** Dos almacenes y un producto con presentación base. */
function distributionSetup(): array
{
    $branch = testBranch();
    $principal = Warehouse::factory()->create(['branche_id' => $branch->id, 'name' => 'Almacén Central']);
    $otro = Warehouse::factory()->create(['branche_id' => $branch->id, 'name' => 'Almacén Norte']);

    return [$principal, $otro, ...newProductWithUnit()];
}

/** @return array{0: Product, 1: ProductUnit} */
function newProductWithUnit(): array
{
    $product = Product::factory()->create(['category_id' => Category::factory()->create()->id]);
    $unit = ProductUnit::factory()->create(['product_id' => $product->id, 'factor' => 1, 'is_base' => true]);

    return [$product, $unit];
}

function stockIn(Product $product, Warehouse $warehouse): int
{
    return (int) StockBatch::where('product_id', $product->id)->where('warehouse_id', $warehouse->id)->sum('qty_remaining');
}

/** Reparto de una línea: [[almacén, cantidad], ...]. */
function splitOf(array $parts): array
{
    return array_map(fn ($p) => ['warehouse_id' => $p[0]->id, 'quantity' => $p[1]], $parts);
}

it('reparte 8 unidades: 5 a un almacén y 3 a otro, con una entrada de stock por almacén', function () {
    [$principal, $otro, $product, $unit] = distributionSetup();
    $user = userWith('ver purchases', 'crear purchases');

    $this->actingAs($user)->post(route('purchases.store'), [
        'warehouse_id' => $principal->id,
        'invoice_number' => 'FAC-REPARTO',
        'items' => [[
            'product_unit_id' => $unit->id,
            'quantity' => 8,
            'unit_cost' => 10,
            'distribution' => splitOf([[$principal, 5], [$otro, 3]]),
        ]],
    ])->assertSessionHasNoErrors();

    $purchase = Purchase::where('invoice_number', 'FAC-REPARTO')->firstOrFail()->load('receipts', 'items.distributions');

    expect(stockIn($product, $principal))->toBe(5)
        ->and(stockIn($product, $otro))->toBe(3)
        ->and((float) $purchase->amount)->toEqual(80.0) // se paga una sola vez por las 8
        ->and($purchase->receipts)->toHaveCount(2)
        ->and($purchase->isDistributed())->toBeTrue()
        ->and($purchase->items->first()->distributions)->toHaveCount(2);

    // Cada almacén recibe su parte con el mismo costo por unidad
    expect(StockBatch::where('product_id', $product->id)->pluck('unit_cost')->map(fn ($c) => (float) $c)->unique()->values()->all())->toBe([10.0]);
});

it('sin reparto, todo entra al almacén principal como siempre', function () {
    [$principal, $otro, $product, $unit] = distributionSetup();
    $user = userWith('ver purchases', 'crear purchases');

    $this->actingAs($user)->post(route('purchases.store'), [
        'warehouse_id' => $principal->id,
        'invoice_number' => 'FAC-SIMPLE',
        'items' => [['product_unit_id' => $unit->id, 'quantity' => 8, 'unit_cost' => 10]],
    ])->assertSessionHasNoErrors();

    $purchase = Purchase::where('invoice_number', 'FAC-SIMPLE')->firstOrFail()->load('receipts');

    expect(stockIn($product, $principal))->toBe(8)
        ->and(stockIn($product, $otro))->toBe(0)
        ->and($purchase->receipts)->toHaveCount(1)
        ->and($purchase->isDistributed())->toBeFalse();
});

it('si se distribuye, todos los productos deben repartirse (no se mezclan con productos sin destino)', function () {
    [$principal, $otro, , $unit] = distributionSetup();
    [, $secondUnit] = newProductWithUnit();
    $user = userWith('ver purchases', 'crear purchases');

    $this->actingAs($user)->post(route('purchases.store'), [
        'warehouse_id' => $principal->id,
        'items' => [
            ['product_unit_id' => $unit->id, 'quantity' => 8, 'unit_cost' => 10, 'distribution' => splitOf([[$principal, 5], [$otro, 3]])],
            ['product_unit_id' => $secondUnit->id, 'quantity' => 4, 'unit_cost' => 20],
        ],
    ])->assertSessionHasErrors('items.1.distribution');

    expect(Purchase::count())->toBe(0)->and(StockBatch::count())->toBe(0);
});

it('permite repartir varios productos, cada uno con su propio reparto', function () {
    [$principal, $otro, $product, $unit] = distributionSetup();
    [$second, $secondUnit] = newProductWithUnit();
    $user = userWith('ver purchases', 'crear purchases');

    $this->actingAs($user)->post(route('purchases.store'), [
        'warehouse_id' => $principal->id,
        'items' => [
            ['product_unit_id' => $unit->id, 'quantity' => 8, 'unit_cost' => 10, 'distribution' => splitOf([[$principal, 5], [$otro, 3]])],
            ['product_unit_id' => $secondUnit->id, 'quantity' => 4, 'unit_cost' => 20, 'distribution' => splitOf([[$otro, 4]])],
        ],
    ])->assertSessionHasNoErrors();

    expect(stockIn($product, $principal))->toBe(5)
        ->and(stockIn($product, $otro))->toBe(3)
        ->and(stockIn($second, $principal))->toBe(0)
        ->and(stockIn($second, $otro))->toBe(4);
});

it('rechaza un reparto que no suma la cantidad comprada y no guarda nada', function (int $first, int $second) {
    [$principal, $otro, , $unit] = distributionSetup();
    $user = userWith('ver purchases', 'crear purchases');

    $this->actingAs($user)->post(route('purchases.store'), [
        'warehouse_id' => $principal->id,
        'items' => [[
            'product_unit_id' => $unit->id,
            'quantity' => 8,
            'unit_cost' => 10,
            'distribution' => splitOf([[$principal, $first], [$otro, $second]]),
        ]],
    ])->assertSessionHasErrors('items.0.distribution');

    expect(Purchase::count())->toBe(0)->and(StockBatch::count())->toBe(0);
})->with([
    'falta mercadería' => [5, 2],
    'sobra mercadería' => [5, 4],
]);

it('rechaza repetir un almacén dentro del reparto de un producto', function () {
    [$principal, , , $unit] = distributionSetup();
    $user = userWith('ver purchases', 'crear purchases');

    $this->actingAs($user)->post(route('purchases.store'), [
        'warehouse_id' => $principal->id,
        'items' => [[
            'product_unit_id' => $unit->id,
            'quantity' => 8,
            'unit_cost' => 10,
            'distribution' => splitOf([[$principal, 5], [$principal, 3]]),
        ]],
    ])->assertSessionHasErrors('items.0.distribution');
});

it('permite usar el mismo almacén en productos distintos', function () {
    [$principal, $otro, $product, $unit] = distributionSetup();
    [$second, $secondUnit] = newProductWithUnit();
    $user = userWith('ver purchases', 'crear purchases');

    $this->actingAs($user)->post(route('purchases.store'), [
        'warehouse_id' => $principal->id,
        'items' => [
            ['product_unit_id' => $unit->id, 'quantity' => 2, 'unit_cost' => 5, 'distribution' => splitOf([[$principal, 1], [$otro, 1]])],
            ['product_unit_id' => $secondUnit->id, 'quantity' => 2, 'unit_cost' => 5, 'distribution' => splitOf([[$principal, 1], [$otro, 1]])],
        ],
    ])->assertSessionHasNoErrors();

    expect(stockIn($product, $principal))->toBe(1)->and(stockIn($second, $otro))->toBe(1);
});

it('anular la compra baja el stock de todos los almacenes y devuelve el dinero', function () {
    [$principal, $otro, $product, $unit] = distributionSetup();
    $user = userWith('ver purchases', 'crear purchases', 'compras.anular');
    $cashbox = Cashbox::factory()->create(['branche_id' => testBranch()->id, 'balance' => 1000, 'cash' => 1000]);
    $cash = PaymentMethod::factory()->create(['is_cash' => true]);

    $purchase = app(PurchaseService::class)->register([
        'warehouse_id' => $principal->id,
        'pay_now' => true,
        'payment_id' => $cash->id,
        'cashbox_id' => $cashbox->id,
    ], [[
        'product_unit_id' => $unit->id, 'quantity' => 8, 'unit_cost' => 10,
        'distribution' => splitOf([[$principal, 5], [$otro, 3]]),
    ]], $user);

    expect((float) $cashbox->fresh()->cash)->toEqual(920.0);

    $this->actingAs($user)->post(route('purchases.cancel', $purchase))->assertRedirect(route('purchases.show', $purchase));

    expect($purchase->fresh()->status)->toBe(Purchase::STATUS_CANCELED)
        ->and(stockIn($product, $principal))->toBe(0)
        ->and(stockIn($product, $otro))->toBe(0)
        ->and((float) $cashbox->fresh()->cash)->toEqual(1000.0);
});

it('no se puede anular si parte de lo repartido ya salió, y no queda la compra a medias', function () {
    [$principal, $otro, $product, $unit] = distributionSetup();
    $user = userWith('ver purchases', 'crear purchases', 'compras.anular');

    $purchase = app(PurchaseService::class)->register(['warehouse_id' => $principal->id], [[
        'product_unit_id' => $unit->id, 'quantity' => 8, 'unit_cost' => 10,
        'distribution' => splitOf([[$principal, 5], [$otro, 3]]),
    ]], $user);

    // Sale mercadería del almacén Norte (consume parte de su lote)
    app(StockMovementService::class)->register(Type::where('code', 'SAL')->firstOrFail(), $otro->id, now(), 'venta', $user->id, [
        ['product_unit_id' => $unit->id, 'quantity' => 2],
    ]);

    $this->actingAs($user)->post(route('purchases.cancel', $purchase))->assertSessionHas('error');

    // Nada se revirtió: la compra sigue recibida y el stock intacto en ambos almacenes
    expect($purchase->fresh()->status)->toBe(Purchase::STATUS_RECEIVED)
        ->and(stockIn($product, $principal))->toBe(5)
        ->and(stockIn($product, $otro))->toBe(1);
});

it('el detalle y el listado muestran los almacenes de la compra repartida', function () {
    [$principal, $otro, , $unit] = distributionSetup();
    $user = userWith('ver purchases', 'crear purchases');

    $purchase = app(PurchaseService::class)->register(['warehouse_id' => $principal->id], [[
        'product_unit_id' => $unit->id, 'quantity' => 8, 'unit_cost' => 10,
        'distribution' => splitOf([[$principal, 5], [$otro, 3]]),
    ]], $user);

    $this->actingAs($user)->get(route('purchases.show', $purchase))
        ->assertOk()
        ->assertSee('Almacén Central')
        ->assertSee('Almacén Norte');

    $this->actingAs($user)->get(route('purchases.index'))->assertOk()->assertSee('Varios (2)');
});

it('el PDF de una compra repartida se genera con el destino de cada producto', function () {
    [$principal, $otro, , $unit] = distributionSetup();
    $user = userWith('ver purchases', 'crear purchases', 'compras.exportar');

    $purchase = app(PurchaseService::class)->register(['warehouse_id' => $principal->id], [[
        'product_unit_id' => $unit->id, 'quantity' => 8, 'unit_cost' => 10,
        'distribution' => splitOf([[$principal, 5], [$otro, 3]]),
    ]], $user);

    $this->actingAs($user)->get(route('purchases.pdf', $purchase))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

it('las compras antiguas (sin reparto guardado) siguen mostrándose y anulándose', function () {
    [$principal, , $product, $unit] = distributionSetup();
    $user = userWith('ver purchases', 'compras.anular');

    // Una compra "de antes": con su movimiento de entrada pero sin filas en purchase_receipts
    $purchase = app(PurchaseService::class)->register(['warehouse_id' => $principal->id], [
        ['product_unit_id' => $unit->id, 'quantity' => 4, 'unit_cost' => 10],
    ], $user);
    $purchase->receipts()->delete();

    $this->actingAs($user)->get(route('purchases.show', $purchase))->assertOk()->assertSee('Almacén Central');
    $this->actingAs($user)->get(route('purchases.index'))->assertOk();

    $this->actingAs($user)->post(route('purchases.cancel', $purchase->fresh()))->assertSessionHasNoErrors();

    expect($purchase->fresh()->status)->toBe(Purchase::STATUS_CANCELED)->and(stockIn($product, $principal))->toBe(0);
});

it('el formulario de nueva compra ofrece distribuir en almacenes', function () {
    distributionSetup();

    $this->actingAs(userWith('ver purchases', 'crear purchases'))
        ->get(route('purchases.create'))
        ->assertOk()
        ->assertSee('warehouse-field', false);
});
