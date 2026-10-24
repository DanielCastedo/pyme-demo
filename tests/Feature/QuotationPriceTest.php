<?php

use App\Models\Category;
use App\Models\Company;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Quotation;
use App\Models\SubscriptionPlan;
use App\Models\Warehouse;
use App\Services\PurchaseService;

/*
|--------------------------------------------------------------------------
| Proformas: precio editable (igual que en una venta)
|--------------------------------------------------------------------------
| Con el permiso "ventas.editar-precio" se puede cotizar a otro precio, sin bajar del costo. Sin ese permiso se usa
| siempre el precio de catálogo, sin importar lo que envíe el navegador.
*/

beforeEach(function () {
    $this->seed(\Database\Seeders\TypeSeeder::class);

    $company = Company::firstOrCreate(['id' => 1], ['name' => 'Brazil Importaciones', 'currency' => 'Bs']);
    $plan = SubscriptionPlan::firstOrCreate(['slug' => 'empresa'], ['name' => 'Plan Empresa', 'price' => 100, 'billing_cycle' => 'monthly', 'is_active' => true]);
    $company->subscriptions()->firstOrCreate(['company_id' => $company->id], [
        'subscription_plan_id' => $plan->id, 'status' => 'active', 'starts_at' => now()->subDay(), 'ends_at' => now()->addMonth(),
    ]);
});

/**
 * Producto de catálogo a Bs 70 con costo 40, con stock en el almacén de la sucursal del usuario.
 *
 * @return array{0: ProductUnit, 1: Warehouse}
 */
function priceWorld(): array
{
    $warehouse = Warehouse::factory()->create(['branche_id' => testBranch()->id]);
    $product = Product::factory()->create(['category_id' => Category::factory()->create()->id, 'name' => 'Cemento', 'sku' => 'CEM-1']);
    $unit = ProductUnit::factory()->create(['product_id' => $product->id, 'name' => 'Bolsa', 'factor' => 1, 'is_base' => true, 'price' => 70]);
    app(PurchaseService::class)->register(['warehouse_id' => $warehouse->id], [['product_unit_id' => $unit->id, 'quantity' => 100, 'unit_cost' => 40]], userWith());

    return [$unit, $warehouse];
}

function priceForm(ProductUnit $unit, mixed $price, int $qty = 3, array $overrides = []): array
{
    return $overrides + [
        'date' => now()->toDateString(),
        'customer_name' => 'Constructora del Sur',
        'items' => [['product_unit_id' => $unit->id, 'quantity' => $qty, 'unit_price' => $price]],
    ];
}

/** Usuario que puede crear/editar proformas, con o sin el permiso de cambiar precios. */
function quoter(bool $canEditPrice): \App\Models\User
{
    $permissions = ['ver quotations', 'crear quotations', 'editar quotations', 'proformas.exportar'];

    return userWith(...($canEditPrice ? [...$permissions, 'ventas.editar-precio'] : $permissions));
}

// ═════════════════════════════════════════════════════════════════════════
describe('con permiso para editar precios', function () {
    it('guarda el precio cotizado y calcula el total con él', function (float $price, float $expectedTotal) {
        [$unit] = priceWorld();

        $this->actingAs(quoter(true))->post(route('quotations.store'), priceForm($unit, $price, 3))->assertSessionHasNoErrors();

        $quotation = Quotation::latest('id')->firstOrFail();
        expect((float) $quotation->items->first()->unit_price)->toEqual($price)
            ->and((float) $quotation->items->first()->subtotal)->toEqual($expectedTotal)
            ->and((float) $quotation->amount)->toEqual($expectedTotal);
    })->with([
        'más barato que el catálogo (pero sobre el costo)' => [55.5, 166.5],
        'igual al catálogo' => [70.0, 210.0],
        'más caro que el catálogo' => [85.25, 255.75],
        'justo al costo' => [40.0, 120.0],
    ]);

    it('no deja cotizar por debajo del costo y no guarda nada', function () {
        [$unit] = priceWorld();

        $this->actingAs(quoter(true))->post(route('quotations.store'), priceForm($unit, 39.99))
            ->assertSessionHasErrors('items');

        expect(session('errors')->first('items'))->toContain('no puede ser menor a su costo')
            ->and(Quotation::count())->toBe(0);
    });

    it('si el precio del catálogo está por debajo del costo, cotizar a ese precio sí se permite', function () {
        [$unit] = priceWorld();
        $unit->update(['price' => 30]); // catálogo bajo el costo (40)

        $this->actingAs(quoter(true))->post(route('quotations.store'), priceForm($unit, 30))->assertSessionHasNoErrors();

        expect((float) Quotation::latest('id')->firstOrFail()->items->first()->unit_price)->toEqual(30.0);
    });

    it('el precio es obligatorio en el formulario y no admite negativos', function (mixed $price) {
        [$unit] = priceWorld();

        $this->actingAs(quoter(true))->post(route('quotations.store'), priceForm($unit, $price))
            ->assertSessionHasErrors('items.0.unit_price');

        expect(Quotation::count())->toBe(0);
    })->with(['vacío' => [null], 'negativo' => [-5], 'texto' => ['caro']]);

    it('al editar la proforma se puede cambiar el precio y se recalcula el total', function () {
        [$unit] = priceWorld();
        $user = quoter(true);

        $this->actingAs($user)->post(route('quotations.store'), priceForm($unit, 60, 2));
        $quotation = Quotation::latest('id')->firstOrFail();

        $this->put(route('quotations.update', $quotation), priceForm($unit, 65, 4))->assertSessionHasNoErrors();

        expect((float) $quotation->fresh()->items->first()->unit_price)->toEqual(65.0)
            ->and((float) $quotation->fresh()->amount)->toEqual(260.0);
    });

    it('al editar no deja bajar del costo', function () {
        [$unit] = priceWorld();
        $user = quoter(true);
        $this->actingAs($user)->post(route('quotations.store'), priceForm($unit, 60, 2));
        $quotation = Quotation::latest('id')->firstOrFail();

        $this->put(route('quotations.update', $quotation), priceForm($unit, 10, 2))->assertSessionHasErrors('items');

        expect((float) $quotation->fresh()->items->first()->unit_price)->toEqual(60.0);
    });

    it('el PDF muestra el precio cotizado', function () {
        [$unit] = priceWorld();
        $user = quoter(true);
        $this->actingAs($user)->post(route('quotations.store'), priceForm($unit, 55.5, 3));
        $quotation = Quotation::latest('id')->firstOrFail();

        $quotation->load(['user', 'customer', 'items.product', 'items.productUnit']);
        $html = view('quotations.pdf', ['quotation' => $quotation, 'company' => Company::first()])->render();

        expect($html)->toContain('55.50')->and($html)->toContain('166.50');
        $this->get(route('quotations.pdf', $quotation))->assertOk()->assertHeader('content-type', 'application/pdf');
    });
});

// ═════════════════════════════════════════════════════════════════════════
describe('sin permiso para editar precios', function () {
    it('siempre usa el precio de catálogo, aunque el navegador mande otro', function (float $sent) {
        [$unit] = priceWorld();

        $this->actingAs(quoter(false))->post(route('quotations.store'), priceForm($unit, $sent, 3))->assertSessionHasNoErrors();

        $quotation = Quotation::latest('id')->firstOrFail();
        expect((float) $quotation->items->first()->unit_price)->toEqual(70.0)
            ->and((float) $quotation->amount)->toEqual(210.0);
    })->with(['más barato' => [10.0], 'más caro' => [999.0], 'igual' => [70.0]]);

    it('al editar tampoco puede cambiar el precio', function () {
        [$unit] = priceWorld();
        $user = quoter(false);
        $this->actingAs($user)->post(route('quotations.store'), priceForm($unit, 70, 2));
        $quotation = Quotation::latest('id')->firstOrFail();

        $this->put(route('quotations.update', $quotation), priceForm($unit, 1, 5))->assertSessionHasNoErrors();

        expect((float) $quotation->fresh()->items->first()->unit_price)->toEqual(70.0)
            ->and((float) $quotation->fresh()->amount)->toEqual(350.0);
    });
});

// ═════════════════════════════════════════════════════════════════════════
describe('formulario', function () {
    it('con permiso: precio editable y el costo llega al navegador', function () {
        [$unit, $warehouse] = priceWorld();

        $this->actingAs(quoter(true))->get(route('quotations.create'))
            ->assertOk()
            ->assertSee('const canEditPrice = true', false)
            ->assertSee('no puede quedar por debajo del costo');

        $this->getJson(route('pos.products.search', ['ids' => $unit->id]))
            ->assertOk()
            ->assertJsonPath('units.0.cost_by_warehouse.'.$warehouse->id, 40);
    });

    it('sin permiso: precio fijo y el costo NO llega al navegador', function () {
        [$unit, $warehouse] = priceWorld();

        $this->actingAs(quoter(false))->get(route('quotations.create'))
            ->assertOk()
            ->assertSee('const canEditPrice = false', false);

        $this->getJson(route('pos.products.search', ['ids' => $unit->id]))
            ->assertOk()
            ->assertJsonMissingPath('units.0.cost_by_warehouse.'.$warehouse->id);
    });

    it('al editar, el formulario recibe el precio guardado para mostrarlo', function () {
        [$unit] = priceWorld();
        $user = quoter(true);
        $this->actingAs($user)->post(route('quotations.store'), priceForm($unit, 58.75, 2));
        $quotation = Quotation::latest('id')->firstOrFail();

        $this->get(route('quotations.edit', $quotation))
            ->assertOk()
            ->assertSee('"unit_price":58.75', false);
    });
});
