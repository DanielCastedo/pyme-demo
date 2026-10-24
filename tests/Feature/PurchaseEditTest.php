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
use App\Models\Warehouse;
use App\Services\PurchaseService;

/*
|--------------------------------------------------------------------------
| Editar una compra = AGREGAR productos
|--------------------------------------------------------------------------
| Lo que ya se compró no se modifica. Los productos nuevos entran al inventario (en el almacén elegido o repartidos),
| suman al total de la compra y, si se pagan desde una caja, mueven la caja con una transacción propia.
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

/** Compra ya registrada de 8 unidades a 10 (total 80) en el almacén Central, con otro almacén disponible. */
function editSetup(bool $paid = false): array
{
    $branch = testBranch();
    $central = Warehouse::factory()->create(['branche_id' => $branch->id, 'name' => 'Almacén Central']);
    $norte = Warehouse::factory()->create(['branche_id' => $branch->id, 'name' => 'Almacén Norte']);

    $first = Product::factory()->create(['category_id' => Category::factory()->create()->id]);
    $firstUnit = ProductUnit::factory()->create(['product_id' => $first->id, 'factor' => 1, 'is_base' => true]);
    $second = Product::factory()->create(['category_id' => Category::factory()->create()->id]);
    $secondUnit = ProductUnit::factory()->create(['product_id' => $second->id, 'factor' => 1, 'is_base' => true]);

    $cashbox = Cashbox::factory()->create(['branche_id' => $branch->id, 'balance' => 1000, 'cash' => 1000]);
    $cash = PaymentMethod::factory()->create(['is_cash' => true]);

    $buyer = userWith('ver purchases', 'crear purchases', 'editar purchases', 'compras.anular');
    $data = ['warehouse_id' => $central->id];
    if ($paid) {
        $data += ['pay_now' => true, 'payment_id' => $cash->id, 'cashbox_id' => $cashbox->id];
    }

    $purchase = app(PurchaseService::class)->register($data, [
        ['product_unit_id' => $firstUnit->id, 'quantity' => 8, 'unit_cost' => 10],
    ], $buyer);

    return compact('central', 'norte', 'first', 'firstUnit', 'second', 'secondUnit', 'cashbox', 'cash', 'buyer', 'purchase');
}

function editStockOf(Product $product, Warehouse $warehouse): int
{
    return (int) StockBatch::where('product_id', $product->id)->where('warehouse_id', $warehouse->id)->sum('qty_remaining');
}

describe('acceso', function () {
    it('exige el permiso "editar purchases" para abrir el formulario y para guardar', function () {
        ['purchase' => $purchase, 'central' => $central, 'secondUnit' => $unit] = editSetup();
        $sinPermiso = userWith('ver purchases', 'crear purchases');

        $this->actingAs($sinPermiso)->get(route('purchases.edit', $purchase))->assertForbidden();
        $this->actingAs($sinPermiso)->put(route('purchases.update', $purchase), [
            'warehouse_id' => $central->id,
            'items' => [['product_unit_id' => $unit->id, 'quantity' => 1, 'unit_cost' => 5]],
        ])->assertForbidden();
    });

    it('no permite agregar productos a una compra anulada', function () {
        ['purchase' => $purchase, 'buyer' => $buyer, 'central' => $central, 'secondUnit' => $unit] = editSetup();
        app(PurchaseService::class)->cancel($purchase);

        $this->actingAs($buyer)->get(route('purchases.edit', $purchase))->assertForbidden();
        $this->actingAs($buyer)->put(route('purchases.update', $purchase), [
            'warehouse_id' => $central->id,
            'items' => [['product_unit_id' => $unit->id, 'quantity' => 1, 'unit_cost' => 5]],
        ])->assertForbidden();
    });

    it('el formulario muestra lo ya comprado bloqueado y ofrece agregar', function () {
        ['purchase' => $purchase, 'buyer' => $buyer] = editSetup();

        $this->actingAs($buyer)->get(route('purchases.edit', $purchase))
            ->assertOk()
            ->assertSee('Agregar productos a la Compra #'.$purchase->id)
            ->assertSee('Bloqueado: no se puede modificar')
            ->assertSee('Agregar a la compra');
    });

    it('el detalle y el listado ofrecen "Agregar productos" solo con permiso', function () {
        ['purchase' => $purchase, 'buyer' => $buyer] = editSetup();

        $this->actingAs($buyer)->get(route('purchases.show', $purchase))->assertOk()->assertSee(route('purchases.edit', $purchase), false);
        $this->actingAs($buyer)->get(route('purchases.index'))->assertOk()->assertSee(route('purchases.edit', $purchase), false);

        $soloVer = userWith('ver purchases');
        $this->actingAs($soloVer)->get(route('purchases.show', $purchase))->assertOk()->assertDontSee(route('purchases.edit', $purchase), false);
    });
});

describe('agregar productos', function () {
    it('suma los productos nuevos al total y al inventario sin tocar lo ya comprado', function () {
        ['purchase' => $purchase, 'buyer' => $buyer, 'central' => $central, 'first' => $first, 'second' => $second, 'secondUnit' => $unit] = editSetup();
        $original = $purchase->items()->first();

        $this->actingAs($buyer)->put(route('purchases.update', $purchase), [
            'warehouse_id' => $central->id,
            'items' => [['product_unit_id' => $unit->id, 'quantity' => 5, 'unit_cost' => 20]],
        ])->assertSessionHasNoErrors()->assertRedirect(route('purchases.show', $purchase));

        $purchase->refresh();

        expect((float) $purchase->amount)->toEqual(180.0) // 80 + 5 × 20
            ->and($purchase->items)->toHaveCount(2)
            ->and($purchase->status)->toBe(Purchase::STATUS_RECEIVED)
            ->and(editStockOf($first, $central))->toBe(8)   // lo original, intacto
            ->and(editStockOf($second, $central))->toBe(5); // lo agregado

        $sameOriginal = $purchase->items()->find($original->id);
        expect((float) $sameOriginal->quantity)->toEqual(8.0)->and((float) $sameOriginal->subtotal)->toEqual(80.0);
    });

    it('permite agregar a otro almacén distinto al de la compra', function () {
        ['purchase' => $purchase, 'buyer' => $buyer, 'norte' => $norte, 'central' => $central, 'second' => $second, 'secondUnit' => $unit] = editSetup();

        $this->actingAs($buyer)->put(route('purchases.update', $purchase), [
            'warehouse_id' => $norte->id,
            'items' => [['product_unit_id' => $unit->id, 'quantity' => 3, 'unit_cost' => 20]],
        ])->assertSessionHasNoErrors();

        expect(editStockOf($second, $norte))->toBe(3)->and(editStockOf($second, $central))->toBe(0);
    });

    it('permite repartir lo agregado entre almacenes', function () {
        ['purchase' => $purchase, 'buyer' => $buyer, 'central' => $central, 'norte' => $norte, 'second' => $second, 'secondUnit' => $unit] = editSetup();

        $this->actingAs($buyer)->put(route('purchases.update', $purchase), [
            'warehouse_id' => $central->id,
            'items' => [[
                'product_unit_id' => $unit->id, 'quantity' => 8, 'unit_cost' => 20,
                'distribution' => [['warehouse_id' => $central->id, 'quantity' => 5], ['warehouse_id' => $norte->id, 'quantity' => 3]],
            ]],
        ])->assertSessionHasNoErrors();

        expect(editStockOf($second, $central))->toBe(5)->and(editStockOf($second, $norte))->toBe(3);
    });

    it('un almacén puede recibir varias veces de la misma compra (agregado sobre agregado)', function () {
        ['purchase' => $purchase, 'buyer' => $buyer, 'central' => $central, 'second' => $second, 'secondUnit' => $unit] = editSetup();

        foreach ([2, 3] as $qty) {
            $this->actingAs($buyer)->put(route('purchases.update', $purchase), [
                'warehouse_id' => $central->id,
                'items' => [['product_unit_id' => $unit->id, 'quantity' => $qty, 'unit_cost' => 20]],
            ])->assertSessionHasNoErrors();
        }

        expect(editStockOf($second, $central))->toBe(5)
            ->and((float) $purchase->fresh()->amount)->toEqual(180.0)
            ->and($purchase->fresh()->receipts()->count())->toBe(3);
    });

    it('no deja cambiar proveedor, factura, fecha ni nota de la compra', function () {
        ['purchase' => $purchase, 'buyer' => $buyer, 'central' => $central, 'secondUnit' => $unit] = editSetup();
        $antes = $purchase->only('supplier_id', 'invoice_number', 'note');

        $this->actingAs($buyer)->put(route('purchases.update', $purchase), [
            'warehouse_id' => $central->id,
            'supplier_id' => 999, 'invoice_number' => 'HACK', 'note' => 'cambiada', 'date' => '2000-01-01',
            'items' => [['product_unit_id' => $unit->id, 'quantity' => 1, 'unit_cost' => 5]],
        ])->assertSessionHasNoErrors();

        expect($purchase->fresh()->only('supplier_id', 'invoice_number', 'note'))->toBe($antes);
    });

    it('exige al menos un producto nuevo y valida el reparto', function () {
        ['purchase' => $purchase, 'buyer' => $buyer, 'central' => $central, 'norte' => $norte, 'secondUnit' => $unit] = editSetup();

        $this->actingAs($buyer)->put(route('purchases.update', $purchase), ['warehouse_id' => $central->id])
            ->assertSessionHasErrors('items');

        $this->actingAs($buyer)->put(route('purchases.update', $purchase), [
            'warehouse_id' => $central->id,
            'items' => [[
                'product_unit_id' => $unit->id, 'quantity' => 8, 'unit_cost' => 20,
                'distribution' => [['warehouse_id' => $central->id, 'quantity' => 5], ['warehouse_id' => $norte->id, 'quantity' => 1]],
            ]],
        ])->assertSessionHasErrors('items.0.distribution');

        expect((float) $purchase->fresh()->amount)->toEqual(80.0)->and($purchase->fresh()->items)->toHaveCount(1);
    });
});

describe('caja', function () {
    it('si se paga lo agregado, la caja baja con una transacción aparte y la original no cambia', function () {
        ['purchase' => $purchase, 'buyer' => $buyer, 'central' => $central, 'secondUnit' => $unit, 'cashbox' => $cashbox, 'cash' => $cash] = editSetup(paid: true);
        $originalTransaction = $purchase->transaction_id;

        expect((float) $cashbox->fresh()->cash)->toEqual(920.0);

        $this->actingAs($buyer)->put(route('purchases.update', $purchase), [
            'warehouse_id' => $central->id,
            'pay_now' => 1, 'payment_id' => $cash->id, 'cashbox_id' => $cashbox->id,
            'items' => [['product_unit_id' => $unit->id, 'quantity' => 5, 'unit_cost' => 20]],
        ])->assertSessionHasNoErrors();

        $purchase->refresh();

        expect((float) $cashbox->fresh()->cash)->toEqual(820.0) // 920 - 100
            ->and($purchase->transaction_id)->toBe($originalTransaction)
            ->and($purchase->extraTransactions)->toHaveCount(1)
            ->and((float) $purchase->extraTransactions->first()->amount)->toEqual(100.0);
    });

    it('si no se marca pagar, no se mueve la caja (queda solo lo que ya se pagó)', function () {
        ['purchase' => $purchase, 'buyer' => $buyer, 'central' => $central, 'secondUnit' => $unit, 'cashbox' => $cashbox] = editSetup(paid: true);

        $this->actingAs($buyer)->put(route('purchases.update', $purchase), [
            'warehouse_id' => $central->id,
            'items' => [['product_unit_id' => $unit->id, 'quantity' => 5, 'unit_cost' => 20]],
        ])->assertSessionHasNoErrors();

        expect((float) $cashbox->fresh()->cash)->toEqual(920.0)->and($purchase->fresh()->extraTransactions)->toHaveCount(0);
    });

    it('permite pagar lo agregado aunque la compra original no se haya pagado', function () {
        ['purchase' => $purchase, 'buyer' => $buyer, 'central' => $central, 'secondUnit' => $unit, 'cashbox' => $cashbox, 'cash' => $cash] = editSetup(paid: false);

        $this->actingAs($buyer)->put(route('purchases.update', $purchase), [
            'warehouse_id' => $central->id,
            'pay_now' => 1, 'payment_id' => $cash->id, 'cashbox_id' => $cashbox->id,
            'items' => [['product_unit_id' => $unit->id, 'quantity' => 5, 'unit_cost' => 20]],
        ])->assertSessionHasNoErrors();

        expect((float) $cashbox->fresh()->cash)->toEqual(900.0)->and($purchase->fresh()->transaction_id)->toBeNull();
    });

    it('si la caja no tiene fondos no se guarda nada (ni stock, ni total, ni pago)', function () {
        ['purchase' => $purchase, 'buyer' => $buyer, 'central' => $central, 'second' => $second, 'secondUnit' => $unit, 'cashbox' => $cashbox, 'cash' => $cash] = editSetup(paid: true);

        $this->actingAs($buyer)->put(route('purchases.update', $purchase), [
            'warehouse_id' => $central->id,
            'pay_now' => 1, 'payment_id' => $cash->id, 'cashbox_id' => $cashbox->id,
            'items' => [['product_unit_id' => $unit->id, 'quantity' => 1000, 'unit_cost' => 20]], // 20 000 > caja
        ])->assertSessionHas('error');

        $purchase->refresh();

        expect((float) $purchase->amount)->toEqual(80.0)
            ->and($purchase->items)->toHaveCount(1)
            ->and(editStockOf($second, $central))->toBe(0)
            ->and((float) $cashbox->fresh()->cash)->toEqual(920.0);
    });

    it('anular la compra revierte todo: el stock de lo original y de lo agregado, y todos los pagos', function () {
        ['purchase' => $purchase, 'buyer' => $buyer, 'central' => $central, 'norte' => $norte, 'first' => $first, 'second' => $second, 'secondUnit' => $unit, 'cashbox' => $cashbox, 'cash' => $cash] = editSetup(paid: true);

        $this->actingAs($buyer)->put(route('purchases.update', $purchase), [
            'warehouse_id' => $central->id,
            'pay_now' => 1, 'payment_id' => $cash->id, 'cashbox_id' => $cashbox->id,
            'items' => [[
                'product_unit_id' => $unit->id, 'quantity' => 8, 'unit_cost' => 20,
                'distribution' => [['warehouse_id' => $central->id, 'quantity' => 5], ['warehouse_id' => $norte->id, 'quantity' => 3]],
            ]],
        ])->assertSessionHasNoErrors();

        expect((float) $cashbox->fresh()->cash)->toEqual(760.0); // 1000 - 80 - 160

        $this->actingAs($buyer)->post(route('purchases.cancel', $purchase))->assertSessionHasNoErrors();

        expect($purchase->fresh()->status)->toBe(Purchase::STATUS_CANCELED)
            ->and(editStockOf($first, $central))->toBe(0)
            ->and(editStockOf($second, $central))->toBe(0)
            ->and(editStockOf($second, $norte))->toBe(0)
            ->and((float) $cashbox->fresh()->cash)->toEqual(1000.0)
            ->and(StockBatch::count())->toBe(0);
    });

    it('el detalle muestra el pago de lo agregado', function () {
        ['purchase' => $purchase, 'buyer' => $buyer, 'central' => $central, 'secondUnit' => $unit, 'cashbox' => $cashbox, 'cash' => $cash] = editSetup(paid: true);

        $this->actingAs($buyer)->put(route('purchases.update', $purchase), [
            'warehouse_id' => $central->id,
            'pay_now' => 1, 'payment_id' => $cash->id, 'cashbox_id' => $cashbox->id,
            'items' => [['product_unit_id' => $unit->id, 'quantity' => 5, 'unit_cost' => 20]],
        ]);

        $this->actingAs($buyer)->get(route('purchases.show', $purchase))
            ->assertOk()
            ->assertSee('Pago de productos agregados');
    });
});
