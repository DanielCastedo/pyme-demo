<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\StockBatch;
use App\Models\Type;
use App\Models\Warehouse;
use App\Services\PurchaseService;
use App\Services\StockMovementService;
use App\View\Components\LowStockNotifications;

/*
|--------------------------------------------------------------------------
| Lotes con número, vencimiento y costo propio (farmacia)
|--------------------------------------------------------------------------
| Un medicamento tiene varios lotes, cada uno con su costo, existencias y
| fecha de vencimiento. Se compra por caja y se vende por unidad. La salida
| gasta primero lo que vence antes (FEFO); sin vencimiento, lo más antiguo.
*/

beforeEach(function () {
    $this->seed(Database\Seeders\TypeSeeder::class);
    $this->warehouse = Warehouse::factory()->create(['branche_id' => testBranch()->id]);
    $this->product = Product::factory()->create(['category_id' => Category::factory()->create()->id, 'name' => 'Ambroxol jarabe']);
    $this->base = ProductUnit::factory()->create(['product_id' => $this->product->id, 'name' => 'Unidad', 'factor' => 1, 'is_base' => true]);
    $this->box = ProductUnit::factory()->create(['product_id' => $this->product->id, 'name' => 'Caja', 'factor' => 30, 'is_base' => false]);
    $this->user = userWith('ver purchases', 'crear purchases', 'ver products', 'editar products');
});

/** Entrada de stock (por unidad base) con su lote y vencimiento. */
function enterLot(int $warehouseId, int $unitId, int $qty, float $cost, ?string $lot, ?string $expires, $date = null): void
{
    app(StockMovementService::class)->register(Type::where('code', 'ENT')->firstOrFail(), $warehouseId, $date ?? now(), null, test()->user->id, [
        ['product_unit_id' => $unitId, 'quantity' => $qty, 'unit_cost' => $cost, 'lot_number' => $lot, 'expires_at' => $expires],
    ]);
}

it('al comprar por caja guarda el lote, el vencimiento y el costo por unidad con 2 decimales', function () {
    $this->actingAs($this->user)->post(route('purchases.store'), [
        'warehouse_id' => $this->warehouse->id,
        'date' => now()->toDateString(),
        'items' => [[
            'product_unit_id' => $this->box->id, 'quantity' => 2, 'unit_cost' => 25,
            'lot_number' => 'AMB-2405', 'expires_at' => now()->addMonths(6)->toDateString(),
        ]],
    ])->assertSessionHasNoErrors();

    $batch = StockBatch::where('product_id', $this->product->id)->sole();

    expect($batch->lot_number)->toBe('AMB-2405')
        ->and($batch->expires_at->toDateString())->toBe(now()->addMonths(6)->toDateString())
        ->and((float) $batch->qty_initial)->toBe(60.0) // 2 cajas × 30
        ->and((float) $batch->unit_cost)->toBe(0.83); // Bs 25 / 30 = 0.8333, redondeado a 2 decimales
});

it('no deja comprar un lote ya vencido', function () {
    $this->actingAs($this->user)->post(route('purchases.store'), [
        'warehouse_id' => $this->warehouse->id,
        'items' => [['product_unit_id' => $this->base->id, 'quantity' => 1, 'unit_cost' => 5, 'expires_at' => now()->subDay()->toDateString()]],
    ])->assertSessionHasErrors('items.0.expires_at');
});

it('la venta gasta primero el lote que vence antes aunque haya entrado después (FEFO); sin vencimiento, al final', function () {
    enterLot($this->warehouse->id, $this->base->id, 10, 22, 'SIN-VTO', null, now()->subDays(30));
    enterLot($this->warehouse->id, $this->base->id, 10, 20, 'LARGO', now()->addYear()->toDateString(), now()->subDays(20));
    enterLot($this->warehouse->id, $this->base->id, 10, 25, 'CORTO', now()->addDays(40)->toDateString(), now()->subDays(5));

    $cost = \App\Models\ProductWarehouse::removeStock($this->product->id, $this->warehouse->id, 15);

    $remaining = StockBatch::where('product_id', $this->product->id)->pluck('qty_remaining', 'lot_number')->map(fn ($q) => (float) $q);
    expect($remaining->all())->toEqual(['SIN-VTO' => 10.0, 'LARGO' => 5.0, 'CORTO' => 0.0])
        ->and((float) $cost)->toBe(10 * 25 + 5 * 20.0);
});

it('muestra lote y vencimiento en el detalle del producto, avisando lo que vence pronto', function () {
    enterLot($this->warehouse->id, $this->base->id, 10, 22, 'AMB-2411', now()->addYear()->toDateString());
    enterLot($this->warehouse->id, $this->base->id, 10, 25, 'AMB-2405', now()->addDays(20)->toDateString());

    $this->actingAs($this->user)->get(route('products.show', $this->product))
        ->assertOk()
        ->assertSeeInOrder(['AMB-2405', 'vence en 20 días', 'AMB-2411']);
});

it('avisa en la campana de los lotes por vencer y vencidos (no de los lejanos)', function () {
    enterLot($this->warehouse->id, $this->base->id, 5, 25, 'PRONTO', now()->addDays(10)->toDateString());
    enterLot($this->warehouse->id, $this->base->id, 5, 25, 'LEJOS', now()->addYear()->toDateString());
    StockBatch::where('lot_number', 'PRONTO')->first()->replicate()->fill(['lot_number' => 'VENCIDO', 'expires_at' => now()->subDays(3)->toDateString()])->save();

    $this->actingAs(userWith('ver product-warehouses', 'ver products'));
    \Illuminate\Support\Facades\Cache::forget('low-stock-alerts');
    $bell = new LowStockNotifications();

    expect($bell->expiringTotal)->toBe(2)
        ->and($bell->expiring->pluck('lot_number')->all())->toBe(['VENCIDO', 'PRONTO']);

    // La campana se dibuja en el encabezado de cualquier pantalla.
    $this->get(route('products.index'))
        ->assertOk()
        ->assertSeeInOrder(['Vencimientos', 'Lote VENCIDO', 'quedan 5', 'Vencido', 'Lote PRONTO', 'Vence en 10 d']);
});

it('en el listado de productos marca el vencimiento más próximo junto al nombre', function () {
    $sinVencimiento = Product::factory()->create(['category_id' => $this->product->category_id, 'name' => 'Gasa estéril']);
    $gasa = ProductUnit::factory()->create(['product_id' => $sinVencimiento->id, 'factor' => 1, 'is_base' => true]);
    enterLot($this->warehouse->id, $gasa->id, 10, 1, null, null);
    enterLot($this->warehouse->id, $this->base->id, 10, 22, 'LEJOS', now()->addYear()->toDateString());
    enterLot($this->warehouse->id, $this->base->id, 10, 25, 'PRONTO', now()->addDays(20)->toDateString());

    $this->actingAs($this->user)->get(route('products.index'))
        ->assertOk()
        ->assertSeeInOrder(['Ambroxol jarabe', 'Vence en 20 d'])
        ->assertDontSee('Vence '.now()->addYear()->format('d/m/Y')); // solo el más próximo

    expect($sinVencimiento->fresh()->expiry_badge)->toBeNull();
});
