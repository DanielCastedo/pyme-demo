<?php

use App\Models\Category;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Quotation;
use App\Models\SubscriptionPlan;
use App\Models\Warehouse;
use App\Services\PurchaseService;

/*
|--------------------------------------------------------------------------
| Proformas: seguridad del cambio de estado, PDF con el formato de recibo y CRUD
|--------------------------------------------------------------------------
*/

beforeEach(function () {
    $company = Company::firstOrCreate(['id' => 1], [
        'name' => 'Brazil Importaciones', 'tax_id' => '1007017028', 'address' => 'Av. Cristo Redentor 1520',
        'phone' => '3-3415000', 'city' => 'Santa Cruz', 'currency' => 'Bs',
    ]);
    $plan = SubscriptionPlan::firstOrCreate(['slug' => 'empresa'], ['name' => 'Plan Empresa', 'price' => 100, 'billing_cycle' => 'monthly', 'is_active' => true]);
    $company->subscriptions()->firstOrCreate(['company_id' => $company->id], [
        'subscription_plan_id' => $plan->id, 'status' => 'active', 'starts_at' => now()->subDay(), 'ends_at' => now()->addMonth(),
    ]);
});

/** Proforma con dos productos: 4 × 12.50 + 3 × 55.00 = 215.00 */
function quotationWithItems(string $number = 'COT-0100', array $overrides = []): Quotation
{
    $customer = Customer::factory()->create(['name' => 'Transportes Bolivia S.A.', 'document' => '1023456029', 'phone' => '70123456']);
    $quotation = quotationFor($customer, $number, $overrides + ['amount' => 215]);
    $category = Category::factory()->create();

    foreach ([['Tornillo autorroscante', 'FG-0001', 12.50, 4], ['Martillo de uña 16oz', 'HER-0210', 55.00, 3]] as [$name, $sku, $price, $qty]) {
        $product = Product::factory()->create(['category_id' => $category->id, 'name' => $name, 'sku' => $sku]);
        $unit = ProductUnit::factory()->create(['product_id' => $product->id, 'name' => 'Unidad', 'factor' => 1, 'is_base' => true, 'price' => $price]);
        $quotation->items()->create([
            'product_id' => $product->id, 'product_unit_id' => $unit->id, 'conversion_factor' => 1,
            'quantity' => $qty, 'unit_price' => $price, 'subtotal' => $price * $qty,
        ]);
    }

    return $quotation;
}

function quotationHtml(Quotation $quotation): string
{
    $quotation->load(['user', 'customer', 'items.product', 'items.productUnit']);

    return view('quotations.pdf', ['quotation' => $quotation, 'company' => Company::first()])->render();
}

// ═════════════════════════════════════════════════════════════════════════
describe('seguridad del cambio de estado (aprobar / rechazar)', function () {
    it('un usuario que solo puede VER proformas no puede aprobar ni rechazar', function (string $status) {
        $quotation = quotationWithItems();

        $this->actingAs(userWith('ver quotations'))
            ->patch(route('quotations.status', $quotation), ['status' => $status])
            ->assertForbidden();

        expect($quotation->fresh()->status)->toBe('pending');
    })->with(['approved', 'rejected', 'expired']);

    it('con "editar quotations" sí puede cambiar el estado', function () {
        $quotation = quotationWithItems();

        $this->actingAs(userWith('ver quotations', 'editar quotations'))
            ->patch(route('quotations.status', $quotation), ['status' => 'approved'])
            ->assertSessionHas('success');

        expect($quotation->fresh()->status)->toBe('approved');
    });

    it('el permiso "admin" también puede cambiar el estado', function () {
        $quotation = quotationWithItems();

        $this->actingAs(userWith('ver quotations', 'admin'))
            ->patch(route('quotations.status', $quotation), ['status' => 'rejected']);

        expect($quotation->fresh()->status)->toBe('rejected');
    });

    it('una proforma aprobada no puede volver atrás ni con permisos', function () {
        $quotation = quotationWithItems('COT-0101', ['status' => 'approved']);

        $this->actingAs(userWith('ver quotations', 'editar quotations'))
            ->patch(route('quotations.status', $quotation), ['status' => 'pending'])
            ->assertSessionHas('error');

        expect($quotation->fresh()->status)->toBe('approved');
    });

    it('rechaza estados inventados', function () {
        $quotation = quotationWithItems();

        $this->actingAs(userWith('ver quotations', 'editar quotations'))
            ->patch(route('quotations.status', $quotation), ['status' => 'aprobadisima'])
            ->assertSessionHasErrors('status');
    });

    it('el detalle muestra Aprobar/Rechazar solo a quien puede cambiar el estado', function () {
        $quotation = quotationWithItems();

        $this->actingAs(userWith('ver quotations', 'editar quotations'))->get(route('quotations.show', $quotation))
            ->assertOk()->assertSee('Aprobar')->assertSee('Rechazar');

        $this->actingAs(userWith('ver quotations'))->get(route('quotations.show', $quotation))
            ->assertOk()->assertDontSee('Aprobar')->assertDontSee('Rechazar');
    });
});

// ═════════════════════════════════════════════════════════════════════════
describe('PDF de la proforma', function () {
    it('se genera como PDF con el permiso de exportar proformas', function () {
        $quotation = quotationWithItems();

        $this->actingAs(userWith('ver quotations', 'proformas.exportar'))->get(route('quotations.pdf', $quotation))
            ->assertOk()->assertHeader('content-type', 'application/pdf');
    });

    it('exige "ver quotations" y "proformas.exportar"', function () {
        $quotation = quotationWithItems();

        $this->actingAs(userWith('ver quotations'))->get(route('quotations.pdf', $quotation))->assertForbidden();
        $this->actingAs(userWith('proformas.exportar'))->get(route('quotations.pdf', $quotation))->assertForbidden();
    });

    it('lleva el formato nuevo: PROFORMA, datos de la empresa y del cliente, y el logo', function () {
        $html = quotationHtml(quotationWithItems('COT-0100'));

        expect($html)->toContain('PROFORMA N°')
            ->and($html)->toContain('COT-0100')
            ->and($html)->toContain('(Cotización de venta)')
            ->and($html)->toContain('Brazil Importaciones')
            ->and($html)->toContain('1007017028')                 // NIT de la empresa
            ->and($html)->toContain('Transportes Bolivia S.A.')
            ->and($html)->toContain('1023456029')
            ->and($html)->toContain('Vigencia hasta')
            ->and($html)->toContain('alt="Logo"');
    });

    it('detalla código, cantidad, unidad, descripción, precio y subtotal, con el total en letras', function () {
        $html = quotationHtml(quotationWithItems());

        expect($html)->toContain('FG-0001')
            ->and($html)->toContain('Tornillo autorroscante')
            ->and($html)->toContain('12.50')
            ->and($html)->toContain('50.00')        // 4 × 12.50
            ->and($html)->toContain('165.00')       // 3 × 55.00
            ->and($html)->toContain('TOTAL BS')
            ->and($html)->toContain('215.00')
            ->and($html)->toContain('Son: Doscientos quince 00/100 Bolivianos');
    });

    it('el QR lleva el número de la proforma', function () {
        $html = quotationHtml(quotationWithItems('COT-0100'));

        expect($html)->toContain('<div class="cap">COT-0100</div>')
            ->and($html)->toContain(\App\Support\ReceiptQr::dataUri('COT-0100'));
    });

    it('muestra las notas propias de la proforma', function () {
        expect(quotationHtml(quotationWithItems('COT-0100', ['notes' => 'Entrega en 5 días hábiles.'])))->toContain('Entrega en 5 días hábiles.');
    });

    it('sin notas propias muestra los términos por defecto y las firmas', function () {
        $html = quotationHtml(quotationWithItems('COT-0101'));

        expect($html)->toContain('Los precios están expresados en Bolivianos')
            ->and($html)->toContain('Aceptación del cliente')
            ->and($html)->toContain('Emitido por');
    });

    it('no usa la palabra factura en ninguna parte', function () {
        expect(mb_strtolower(strip_tags(quotationHtml(quotationWithItems()))))->not->toContain('factura');
    });

    it('funciona sin empresa registrada y con un cliente incompleto', function () {
        $quotation = quotationFor(Customer::factory()->create(['document' => null, 'phone' => null]), 'COT-0300', ['amount' => 10]);
        $quotation->load(['user', 'customer', 'items.product', 'items.productUnit']);

        $html = view('quotations.pdf', ['quotation' => $quotation, 'company' => null])->render();

        expect($html)->toContain('PROFORMA N°')
            ->and($html)->toContain('NIT/CI/CEX:</span> S/N')
            ->and($html)->toContain('No se registraron productos en esta proforma');
    });
});

// ═════════════════════════════════════════════════════════════════════════
describe('CRUD: casos adicionales', function () {
    /** Un almacén con stock para poder crear proformas. */
    function quotationWithStock(): array
    {
        $branch = testBranch();
        $warehouse = Warehouse::factory()->create(['branche_id' => $branch->id]);
        $user = userWith('ver quotations', 'crear quotations', 'editar quotations', 'eliminar quotations', 'proformas.exportar');
        $product = Product::factory()->create(['category_id' => Category::factory()->create()->id, 'name' => 'Cemento', 'sku' => 'CEM-1']);
        $unit = ProductUnit::factory()->create(['product_id' => $product->id, 'name' => 'Bolsa', 'factor' => 1, 'is_base' => true, 'price' => 70]);

        \Database\Seeders\TypeSeeder::class;
        test()->seed(\Database\Seeders\TypeSeeder::class);
        app(PurchaseService::class)->register(['warehouse_id' => $warehouse->id], [['product_unit_id' => $unit->id, 'quantity' => 100, 'unit_cost' => 40]], $user);

        return [$user, $unit];
    }

    function quotationFormData($unit, int $quantity = 2, array $overrides = []): array
    {
        return $overrides + [
            'date' => now()->toDateString(),
            'customer_name' => 'Constructora del Sur',
            'customer_document' => '5566778',
            'items' => [['product_unit_id' => $unit->id, 'quantity' => $quantity, 'unit_price' => 999]], // el precio del cliente se ignora
        ];
    }

    it('el precio y el total salen del catálogo, no de lo que mande el navegador', function () {
        [$user, $unit] = quotationWithStock();

        $this->actingAs($user)->post(route('quotations.store'), quotationFormData($unit, 3))->assertSessionHasNoErrors();

        $quotation = Quotation::latest('id')->firstOrFail();
        expect((float) $quotation->amount)->toEqual(210.0) // 3 × 70, no 3 × 999
            ->and((float) $quotation->items->first()->unit_price)->toEqual(70.0);
    });

    it('la numeración sigue correlativa y pasa de COT-9999 a COT-10000', function () {
        [$user, $unit] = quotationWithStock();
        quotationFor(Customer::factory()->create(), 'COT-9999');

        $this->actingAs($user)->post(route('quotations.store'), quotationFormData($unit))->assertSessionHasNoErrors();
        $this->actingAs($user)->post(route('quotations.store'), quotationFormData($unit))->assertSessionHasNoErrors();

        expect(Quotation::pluck('number')->sort()->values()->all())->toBe(['COT-10000', 'COT-10001', 'COT-9999']);
    });

    it('se puede crear, editar, generar el PDF y eliminar (recorrido completo)', function () {
        [$user, $unit] = quotationWithStock();

        $this->actingAs($user)->post(route('quotations.store'), quotationFormData($unit, 2))->assertSessionHasNoErrors();
        $quotation = Quotation::latest('id')->firstOrFail();

        $this->get(route('quotations.show', $quotation))->assertOk()->assertSee($quotation->number)->assertSee('Constructora del Sur');

        $this->put(route('quotations.update', $quotation), quotationFormData($unit, 5, ['customer_name' => 'Constructora del Norte']))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('quotations.show', $quotation));
        expect((float) $quotation->fresh()->amount)->toEqual(350.0)
            ->and($quotation->fresh()->items)->toHaveCount(1);

        $this->get(route('quotations.pdf', $quotation))->assertOk()->assertHeader('content-type', 'application/pdf');

        $this->delete(route('quotations.destroy', $quotation))->assertRedirect(route('quotations.index'));
        expect(Quotation::count())->toBe(0)
            ->and(\App\Models\QuotationItem::count())->toBe(0); // las líneas se borran con la proforma
    });

    it('una proforma rechazada no se puede editar, pero sí eliminar', function () {
        [$user, $unit] = quotationWithStock();
        $quotation = quotationFor(Customer::factory()->create(), 'COT-0400', ['status' => 'rejected']);

        $this->actingAs($user)->get(route('quotations.edit', $quotation))->assertForbidden();
        $this->delete(route('quotations.destroy', $quotation))->assertRedirect(route('quotations.index'));

        expect(Quotation::count())->toBe(0);
    });
});
