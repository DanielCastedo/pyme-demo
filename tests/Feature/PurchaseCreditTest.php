<?php

use App\Models\Cashbox;
use App\Models\Category;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\PurchaseService;

/*
|--------------------------------------------------------------------------
| Compras a crédito: registro, anticipo, límite del anticipo, permiso y anulación
|--------------------------------------------------------------------------
*/

beforeEach(function () {
    $this->seed(\Database\Seeders\TypeSeeder::class);
});

/** Un almacén, un producto comprable y un proveedor: lo mínimo para registrar una compra. */
function creditPurchaseWorld(): array
{
    $branch = testBranch();
    $warehouse = Warehouse::factory()->create(['branche_id' => $branch->id]);
    $supplier = Supplier::create(['name' => 'Distribuidora del Este', 'document' => '5566778']);
    $category = Category::factory()->create();
    $product = Product::factory()->create(['category_id' => $category->id]);
    $unit = ProductUnit::factory()->create(['product_id' => $product->id, 'factor' => 1, 'is_base' => true]);

    return [
        'branch' => $branch, 'warehouse' => $warehouse, 'supplier' => $supplier, 'unit' => $unit,
        'cashbox' => Cashbox::factory()->create(['branche_id' => $branch->id, 'balance' => 5000, 'cash' => 5000]),
        'cash' => PaymentMethod::factory()->cash()->create(),
    ];
}

function creditPurchasePayload(array $w, array $overrides = []): array
{
    return $overrides + [
        'warehouse_id' => $w['warehouse']->id,
        'supplier_id' => $w['supplier']->id,
        'payment_type' => 'credit',
        'due_date' => now()->addDays(30)->toDateString(),
        'items' => [
            ['product_unit_id' => $w['unit']->id, 'quantity' => 10, 'unit_cost' => 25.00],
        ],
    ];
}

describe('registro de compras a crédito (HTTP)', function () {
    it('rechaza (con error, sin crear nada) a un usuario sin permiso compras.credito', function () {
        $user = userWith('ver purchases', 'crear purchases');
        $w = creditPurchaseWorld();

        $this->actingAs($user)->post(route('purchases.store'), creditPurchasePayload($w))
            ->assertRedirect()
            ->assertSessionHas('error');

        expect(Purchase::count())->toBe(0);
    });

    it('exige un proveedor registrado', function () {
        $user = userWith('ver purchases', 'crear purchases', 'compras.credito');
        $w = creditPurchaseWorld();

        $this->actingAs($user)->post(route('purchases.store'), creditPurchasePayload($w, ['supplier_id' => '']))
            ->assertSessionHasErrors('supplier_id');

        expect(Purchase::count())->toBe(0);
    });

    it('exige una fecha de vencimiento posterior a hoy', function () {
        $user = userWith('ver purchases', 'crear purchases', 'compras.credito');
        $w = creditPurchaseWorld();

        $this->actingAs($user)->post(route('purchases.store'), creditPurchasePayload($w, ['due_date' => now()->toDateString()]))
            ->assertSessionHasErrors('due_date');

        expect(Purchase::count())->toBe(0);
    });

    it('registra una compra a crédito sin anticipo: queda con saldo completo y sin movimiento de caja', function () {
        $user = userWith('ver purchases', 'crear purchases', 'compras.credito');
        $w = creditPurchaseWorld();

        $this->actingAs($user)->post(route('purchases.store'), creditPurchasePayload($w))
            ->assertSessionHasNoErrors();

        $purchase = Purchase::firstOrFail();
        expect($purchase->isCredit())->toBeTrue()
            ->and((float) $purchase->amount)->toBe(250.0)
            ->and((float) $purchase->paid_amount)->toBe(0.0)
            ->and($purchase->balance)->toBe(250.0)
            ->and($purchase->due_date->toDateString())->toBe(now()->addDays(30)->toDateString())
            ->and($purchase->payment_status_label)->toBe('Pendiente')
            ->and($w['cashbox']->fresh()->balance)->toEqual(5000);
    });

    it('registra una compra a crédito con anticipo: descuenta de la caja y reduce el saldo', function () {
        $user = userWith('ver purchases', 'crear purchases', 'compras.credito');
        $w = creditPurchaseWorld();

        $payload = creditPurchasePayload($w, [
            'initial_payment' => 100,
            'payment_id' => $w['cash']->id,
            'cashbox_id' => $w['cashbox']->id,
        ]);

        $this->actingAs($user)->post(route('purchases.store'), $payload)->assertSessionHasNoErrors();

        $purchase = Purchase::firstOrFail();
        expect((float) $purchase->paid_amount)->toBe(100.0)
            ->and($purchase->balance)->toBe(150.0)
            ->and($purchase->payment_status_label)->toBe('Parcial')
            ->and($purchase->payments()->count())->toBe(1)
            ->and((float) $w['cashbox']->fresh()->balance)->toBe(4900.0);
    });

    it('rechaza un anticipo mayor o igual al total (debe ser al contado)', function () {
        $user = userWith('ver purchases', 'crear purchases', 'compras.credito');
        $w = creditPurchaseWorld();

        $payload = creditPurchasePayload($w, [
            'initial_payment' => 250, // el total exacto de la compra
            'payment_id' => $w['cash']->id,
            'cashbox_id' => $w['cashbox']->id,
        ]);

        $this->actingAs($user)->post(route('purchases.store'), $payload)->assertSessionHas('error');

        expect(Purchase::count())->toBe(0);
    });
});

describe('anulación de una compra a crédito', function () {
    it('revierte el stock, todos los pagos y deja el saldo en 0', function () {
        $user = userWith('ver purchases', 'crear purchases', 'compras.credito', 'compras.anular');
        $w = creditPurchaseWorld();

        $purchase = app(PurchaseService::class)->register(creditPurchasePayload($w, [
            'initial_payment' => 100,
            'payment_id' => $w['cash']->id,
            'cashbox_id' => $w['cashbox']->id,
        ]), [['product_unit_id' => $w['unit']->id, 'quantity' => 10, 'unit_cost' => 25.00]], $user);

        expect((float) $w['cashbox']->fresh()->balance)->toBe(4900.0);

        $this->actingAs($user)->post(route('purchases.cancel', $purchase))
            ->assertRedirect(route('purchases.show', $purchase));

        $purchase->refresh();
        expect($purchase->status)->toBe(Purchase::STATUS_CANCELED)
            ->and((float) $purchase->paid_amount)->toBe(0.0)
            ->and($purchase->balance)->toBe(0.0)
            ->and($purchase->payments()->where('canceled', false)->count())->toBe(0)
            ->and((float) $w['cashbox']->fresh()->balance)->toBe(5000.0);
    });
});
