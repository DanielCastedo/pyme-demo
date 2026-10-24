<?php

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\ProductWarehouse;
use App\Models\Quotation;
use App\Models\Warehouse;

/*
|--------------------------------------------------------------------------
| QuotationController: Proformas, validación de stock, cálculo de items,
| bloqueo de estados aprobados/rechazados, PDF y permisos
|--------------------------------------------------------------------------
*/

/**
 * Prepara un producto con su unidad y stock disponible en el almacén de prueba.
 *
 * @return array{0: Product, 1: ProductUnit, 2: Warehouse}
 */
function setupQuotationProduct(float $stock = 50, float $price = 75.0): array
{
    $branch = testBranch();
    $warehouse = Warehouse::firstOrCreate(
        ['code' => 'ALM-QUOTES'],
        ['name' => 'Almacén Cotizaciones', 'is_active' => true, 'branche_id' => $branch->id]
    );

    $category = Category::factory()->create();
    $product = Product::factory()->create([
        'category_id' => $category->id,
        'name' => 'Amortiguador Delantero Gas',
        'sku' => 'SKU-AMORT-' . uniqid(),
    ]);

    $unit = ProductUnit::create([
        'product_id' => $product->id,
        'name' => 'Pieza',
        'factor' => 1,
        'price' => $price,
        'is_base' => true,
    ]);

    ProductWarehouse::create([
        'product_id' => $product->id,
        'warehouse_id' => $warehouse->id,
        'stock' => $stock,
        'min_stock' => 2,
        'avg_cost' => 45.0,
    ]);

    return [$product, $unit, $warehouse];
}

/** Payload válido de proforma. */
function quotationPayload(ProductUnit $unit, array $overrides = []): array
{
    return $overrides + [
        'date' => now()->toDateString(),
        'expires_at' => now()->addDays(15)->toDateString(),
        'customer_name' => 'Transportes El Rayo S.A.',
        'customer_document' => '8492019012',
        'customer_phone' => '71234567',
        'customer_email' => 'logistica@elrayo.test',
        'notes' => 'Cotización válida por 15 días calendario.',
        'items' => [
            [
                'product_unit_id' => $unit->id,
                'quantity' => 2,
                'unit_price' => (float) $unit->price,
            ],
        ],
    ];
}

// ─────────────────────────────────────────────────────────────────────────────
// Autenticación y permisos
// ─────────────────────────────────────────────────────────────────────────────

describe('acceso y políticas de proforma', function () {
    it('redirige al login a un visitante', function () {
        $this->get(route('quotations.index'))->assertRedirect(route('login'));
    });

    it('rechaza (403) a un usuario sin permisos en todas las acciones', function () {
        $this->actingAs(userWith());
        $customer = Customer::factory()->create();
        $quotation = quotationFor($customer, 'COT-0001');

        $this->get(route('quotations.index'))->assertForbidden();
        $this->get(route('quotations.show', $quotation))->assertForbidden();
        $this->get(route('quotations.edit', $quotation))->assertForbidden();
        $this->delete(route('quotations.destroy', $quotation))->assertForbidden();
        $this->get(route('quotations.pdf', $quotation))->assertForbidden();
        $this->patch(route('quotations.status', $quotation), ['status' => 'approved'])->assertForbidden();
    });

    it('aplica las políticas granulares según el permiso del usuario', function () {
        $this->actingAs(userWith('ver quotations'));
        $customer = Customer::factory()->create();
        $quotation = quotationFor($customer, 'COT-0001');

        $this->get(route('quotations.index'))->assertOk();
        $this->get(route('quotations.show', $quotation))->assertOk();
        $this->get(route('quotations.edit', $quotation))->assertForbidden();
        $this->delete(route('quotations.destroy', $quotation))->assertForbidden();
    });

    it('impide modificar o editar una proforma aprobada (la policy devuelve 403)', function () {
        $this->actingAs(userWith('ver quotations', 'editar quotations'));
        $customer = Customer::factory()->create();
        $quotation = quotationFor($customer, 'COT-0001', ['status' => 'approved']);

        $this->get(route('quotations.edit', $quotation))->assertForbidden();
    });

    it('impide modificar o editar una proforma rechazada (la policy devuelve 403)', function () {
        $this->actingAs(userWith('ver quotations', 'editar quotations'));
        $customer = Customer::factory()->create();
        $quotation = quotationFor($customer, 'COT-0001', ['status' => 'rejected']);

        $this->get(route('quotations.edit', $quotation))->assertForbidden();
    });

    it('impide eliminar una proforma aprobada (la policy devuelve 403)', function () {
        $this->actingAs(userWith('ver quotations', 'eliminar quotations'));
        $customer = Customer::factory()->create();
        $quotation = quotationFor($customer, 'COT-0001', ['status' => 'approved']);

        $this->delete(route('quotations.destroy', $quotation))->assertForbidden();

        expect(Quotation::where('id', $quotation->id)->exists())->toBeTrue();
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// index
// ─────────────────────────────────────────────────────────────────────────────

describe('index', function () {
    beforeEach(fn () => $this->actingAs(userWith('ver quotations')));

    it('lista las proformas y pagina de a 10', function () {
        $customer = Customer::factory()->create();
        foreach (range(1, 12) as $i) {
            quotationFor($customer, sprintf('COT-%04d', $i), ['date' => now()->subDays($i)->toDateString()]);
        }

        $response = $this->get(route('quotations.index'))->assertOk()->assertViewIs('quotations.index');

        $quotations = $response->viewData('quotations');

        expect($quotations->total())->toBe(12)
            ->and($quotations->perPage())->toBe(10);
    });

    it('busca por número, notas o cliente (filter[buscar])', function (string $term, string $expectedNumber) {
        $c1 = Customer::factory()->create(['name' => 'Minería San Cristóbal', 'document' => '554433221']);
        $c2 = Customer::factory()->create(['name' => 'Agropecuaria Chaco', 'document' => '998877665']);

        quotationFor($c1, 'COT-1001', ['notes' => 'Descuento especial por volumen']);
        quotationFor($c2, 'COT-1002', ['notes' => 'Entrega en campo']);

        $numbers = $this->get(route('quotations.index', ['filter' => ['buscar' => $term]]))
            ->viewData('quotations')->pluck('number')->all();

        expect($numbers)->toBe([$expectedNumber]);
    })->with([
        'por número' => ['COT-1001', 'COT-1001'],
        'por notas' => ['volumen', 'COT-1001'],
        'por nombre cliente' => ['San Cristóbal', 'COT-1001'],
        'por documento cliente' => ['998877665', 'COT-1002'],
    ]);

    it('filtra por estado (filter[status])', function () {
        $customer = Customer::factory()->create();
        quotationFor($customer, 'COT-0010', ['status' => 'pending']);
        quotationFor($customer, 'COT-0011', ['status' => 'approved']);

        $pending = $this->get(route('quotations.index', ['filter' => ['status' => 'pending']]))
            ->viewData('quotations')->pluck('number')->all();

        expect($pending)->toContain('COT-0010')
            ->and($pending)->not->toContain('COT-0011');
    });

    it('ordena por los campos permitidos y rechaza los no permitidos con 400', function () {
        $this->get(route('quotations.index', ['sort' => 'number']))->assertOk();
        $this->get(route('quotations.index', ['sort' => '-amount']))->assertOk();
        $this->get(route('quotations.index', ['sort' => 'customer_id']))->assertStatus(400);
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// create
// ─────────────────────────────────────────────────────────────────────────────

describe('create', function () {
    beforeEach(fn () => $this->actingAs(userWith('ver quotations', 'crear quotations')));

    it('muestra el formulario de proforma con stock y número sugerido', function () {
        [$product, $unit, $warehouse] = setupQuotationProduct(stock: 25);

        $response = $this->get(route('quotations.create'))
            ->assertOk()
            ->assertViewIs('quotations.create');

        expect($response->viewData('nextNumber'))->toContain('COT-')
            ->and($response->viewData('warehouse')->id)->toBe($warehouse->id);
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// store
// ─────────────────────────────────────────────────────────────────────────────

describe('store', function () {
    beforeEach(fn () => $this->actingAs(userWith('ver quotations', 'crear quotations')));

    it('registra la proforma, calcula el total, sincroniza al cliente y genera número correlativo', function () {
        [$product, $unit, $warehouse] = setupQuotationProduct(stock: 10, price: 100.0);

        $payload = quotationPayload($unit, [
            'customer_name' => 'Constructora Titán S.A.',
            'customer_document' => '99881122',
            'items' => [
                [
                    'product_unit_id' => $unit->id,
                    'quantity' => 3,
                    'unit_price' => 100.0,
                ],
            ],
        ]);

        $response = $this->post(route('quotations.store'), $payload);

        $quotation = Quotation::where('number', 'COT-0001')->first();
        expect($quotation)->not->toBeNull();

        $response->assertRedirect(route('quotations.show', $quotation))
            ->assertSessionHas('success');

        expect((float) $quotation->amount)->toBe(300.00)
            ->and($quotation->customer->name)->toBe('Constructora Titán S.A.')
            ->and($quotation->customer->document)->toBe('99881122')
            ->and($quotation->items->count())->toBe(1)
            ->and((float) $quotation->items->first()->quantity)->toBe(3.0);
    });

    it('permite cotizar más cantidad de la que hay en stock (es solo una cotización, no reserva ni descuenta nada)', function () {
        [$product, $unit, $warehouse] = setupQuotationProduct(stock: 5, price: 50.0);

        $payload = quotationPayload($unit, [
            'items' => [
                [
                    'product_unit_id' => $unit->id,
                    'quantity' => 10, // Stock es 5, pero la proforma no lo bloquea
                    'unit_price' => 50.0,
                ],
            ],
        ]);

        $this->post(route('quotations.store'), $payload)
            ->assertRedirect();

        $quotation = Quotation::firstOrFail();
        expect((float) $quotation->items->first()->quantity)->toBe(10.0);
        expect((float) $product->getStockInWarehouse($warehouse))->toBe(5.0); // el stock real no se tocó
    });

    it('valida que la fecha de vencimiento sea igual o posterior a la fecha de emisión', function () {
        [$product, $unit] = setupQuotationProduct();

        $payload = quotationPayload($unit, [
            'date' => '2026-06-15',
            'expires_at' => '2026-06-10', // Anterior a date
        ]);

        $this->post(route('quotations.store'), $payload)
            ->assertSessionHasErrors('expires_at');
    });

    it('valida que contenga al menos un item válido', function () {
        $payload = [
            'date' => now()->toDateString(),
            'customer_name' => 'Cliente Sin Items',
            'items' => [],
        ];

        $this->post(route('quotations.store'), $payload)
            ->assertSessionHasErrors('items');
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// show, edit y update
// ─────────────────────────────────────────────────────────────────────────────

describe('show, edit y update', function () {
    it('muestra el detalle de la proforma y carga items', function () {
        $this->actingAs(userWith('ver quotations'));
        $customer = Customer::factory()->create();
        $quotation = quotationFor($customer, 'COT-0050');

        $this->get(route('quotations.show', $quotation))
            ->assertOk()
            ->assertViewIs('quotations.show')
            ->assertViewHas('quotation', fn ($q) => $q->id === $quotation->id);
    });

    it('actualiza los datos y recalcula el monto total de la proforma pendiente', function () {
        $this->actingAs(userWith('ver quotations', 'editar quotations'));
        [$product, $unit, $warehouse] = setupQuotationProduct(stock: 50, price: 40.0);
        $customer = Customer::factory()->create();
        $quotation = quotationFor($customer, 'COT-0077', ['status' => 'pending']);

        $updatePayload = quotationPayload($unit, [
            'customer_name' => 'Cliente Modificado',
            'items' => [
                [
                    'product_unit_id' => $unit->id,
                    'quantity' => 5,
                    'unit_price' => 40.0,
                ],
            ],
        ]);

        $this->put(route('quotations.update', $quotation), $updatePayload)
            ->assertRedirect(route('quotations.show', $quotation))
            ->assertSessionHas('success');

        $quotation->refresh();
        expect((float) $quotation->amount)->toBe(200.00)
            ->and($quotation->customer->name)->toBe('Cliente Modificado');
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// cambio de estado y PDF
// ─────────────────────────────────────────────────────────────────────────────

describe('cambio de estado y descarga PDF', function () {
    beforeEach(fn () => $this->actingAs(userWith('ver quotations', 'editar quotations')));

    it('permite cambiar el estado de una proforma pendiente a rechazada o expirada', function (string $targetStatus) {
        $customer = Customer::factory()->create();
        $quotation = quotationFor($customer, 'COT-0088', ['status' => 'pending']);

        $this->patch(route('quotations.status', $quotation), ['status' => $targetStatus])
            ->assertRedirect();

        expect($quotation->refresh()->status)->toBe($targetStatus);
    })->with([
        'rechazada' => ['rejected'],
        'expirada' => ['expired'],
    ]);

    it('no permite cambiar de estado si la proforma ya fue aprobada', function () {
        $customer = Customer::factory()->create();
        $quotation = quotationFor($customer, 'COT-0099', ['status' => 'approved']);

        $this->patch(route('quotations.status', $quotation), ['status' => 'pending'])
            ->assertSessionHas('error', 'Una proforma aprobada no puede cambiar de estado.');

        expect($quotation->refresh()->status)->toBe('approved');
    });

    it('genera y descarga el PDF de la proforma y muestra el botón imprimir en la esquina superior derecha', function () {
        $this->actingAs(userWith('ver quotations', 'proformas.exportar'));
        $customer = Customer::factory()->create(['name' => 'Empresa Minera Sur']);
        $quotation = quotationFor($customer, 'COT-0100');

        $response = $this->get(route('quotations.pdf', $quotation));
        $response->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $responseShow = $this->get(route('quotations.show', $quotation));
        $responseShow->assertOk()
            ->assertSee(route('quotations.pdf', $quotation));
    });

    it('no manda el catálogo entero a la vista: el buscador lo pide al servidor a medida que se escribe', function () {
        [$product, $unit, $warehouse] = setupQuotationProduct();

        $user = userWith('ver quotations', 'crear quotations');
        $response = $this->actingAs($user)->get(route('quotations.create'));
        $response->assertOk()
            ->assertViewMissing('productUnits'); // ver PosSearchController: se pide de a poco, no se precarga

        $found = $this->actingAs($user)
            ->getJson(route('pos.products.search', ['q' => $product->name]))
            ->assertOk()
            ->json('units');

        expect(collect($found)->pluck('id'))->toContain($unit->id);
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// destroy
// ─────────────────────────────────────────────────────────────────────────────

describe('destroy', function () {
    beforeEach(fn () => $this->actingAs(userWith('ver quotations', 'eliminar quotations')));

    it('elimina una proforma pendiente', function () {
        $customer = Customer::factory()->create();
        $quotation = quotationFor($customer, 'COT-0200', ['status' => 'pending']);

        $this->delete(route('quotations.destroy', $quotation))
            ->assertRedirect(route('quotations.index'))
            ->assertSessionHas('success');

        expect(Quotation::where('id', $quotation->id)->exists())->toBeFalse();
    });

    it('responde 404 si la proforma no existe', function () {
        $this->delete(route('quotations.destroy', 99999))->assertNotFound();
    });
});
