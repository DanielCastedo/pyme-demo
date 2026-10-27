<?php

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductComponent;
use App\Models\ProductUnit;
use App\Models\ProductWarehouse;
use App\Models\Warehouse;

/*
|--------------------------------------------------------------------------
| PosSearchController: buscador de productos del POS (Ventas/Pedidos/Consignaciones)
|--------------------------------------------------------------------------
| Reemplaza el catálogo completo que antes se mandaba entero al navegador: ahora cada búsqueda
| trae como máximo PosFormData::SEARCH_LIMIT productos desde el servidor.
*/

beforeEach(function () {
    $this->branch = testBranch();
    $this->warehouse = Warehouse::factory()->create(['branche_id' => $this->branch->id]);
});

it('exige estar autenticado', function () {
    $this->get(route('pos.products.search'))->assertRedirect(route('login'));
});

it('nunca devuelve más de 15 resultados, aunque haya muchos más productos', function () {
    $this->actingAs(userWith('ver sales'));

    Product::factory()->count(20)->create()->each(function ($p) {
        ProductUnit::factory()->create(['product_id' => $p->id, 'is_base' => true, 'factor' => 1]);
    });

    $response = $this->getJson(route('pos.products.search'))->assertOk();

    expect($response->json('units'))->toHaveCount(15);
});

it('busca por nombre, SKU, código de barras, marca, color y medida', function () {
    $this->actingAs(userWith('ver sales'));

    $product = Product::factory()->create(['name' => 'Martillo de uña', 'sku' => 'HM-0001', 'marca' => 'Stanley', 'color' => 'Rojo', 'medida' => '16oz']);
    $unit = ProductUnit::factory()->create(['product_id' => $product->id, 'is_base' => true, 'factor' => 1, 'barcode' => '7591234567890']);
    $other = Product::factory()->create(['name' => 'Destornillador']);
    ProductUnit::factory()->create(['product_id' => $other->id, 'is_base' => true]);

    foreach (['Martillo', 'HM-0001', '7591234567890', 'Stanley', 'Rojo', '16oz'] as $term) {
        $ids = $this->getJson(route('pos.products.search', ['q' => $term]))->json('units.*.id');
        expect($ids)->toContain($unit->id)->and($ids)->not->toContain($other->id ?? null);
    }
});

it('sin filtros devuelve los primeros del catálogo (para el buscador vacío al enfocar)', function () {
    $this->actingAs(userWith('ver sales'));

    Product::factory()->count(3)->create()->each(fn ($p) => ProductUnit::factory()->create(['product_id' => $p->id, 'is_base' => true]));

    $response = $this->getJson(route('pos.products.search'))->assertOk();

    expect($response->json('units'))->toHaveCount(3);
});

it('busca por código exacto (barcode o SKU) y devuelve como mucho uno', function () {
    $this->actingAs(userWith('ver sales'));

    $product = Product::factory()->create(['sku' => 'FG-0001']);
    $unit = ProductUnit::factory()->create(['product_id' => $product->id, 'is_base' => true, 'barcode' => '7770001112223']);

    $byBarcode = $this->getJson(route('pos.products.search', ['code' => '7770001112223']))->assertOk();
    expect($byBarcode->json('units'))->toHaveCount(1)
        ->and($byBarcode->json('units.0.id'))->toBe($unit->id);

    $bySku = $this->getJson(route('pos.products.search', ['code' => 'FG-0001']))->assertOk();
    expect($bySku->json('units.0.id'))->toBe($unit->id);

    $noMatch = $this->getJson(route('pos.products.search', ['code' => 'NO-EXISTE']))->assertOk();
    expect($noMatch->json('units'))->toBe([]);
});

it('busca por ids puntuales sin limitarse a 15 (para redibujar líneas ya elegidas)', function () {
    $this->actingAs(userWith('ver sales'));

    $units = Product::factory()->count(3)->create()->map(fn ($p) => ProductUnit::factory()->create(['product_id' => $p->id, 'is_base' => true]));

    $response = $this->getJson(route('pos.products.search', ['ids' => $units->pluck('id')->implode(',')]))->assertOk();

    expect($response->json('units.*.id'))->toEqualCanonicalizing($units->pluck('id')->all());
});

it('devuelve el stock y el costo por almacén de cada producto', function () {
    $this->actingAs(userWith('ver sales', 'ventas.editar-precio'));

    $product = Product::factory()->create();
    $unit = ProductUnit::factory()->create(['product_id' => $product->id, 'is_base' => true, 'factor' => 1]);
    ProductWarehouse::create(['product_id' => $product->id, 'warehouse_id' => $this->warehouse->id, 'stock' => 40, 'avg_cost' => 12.5]);

    $result = $this->getJson(route('pos.products.search', ['ids' => (string) $unit->id]))->json('units.0');

    expect((float) $result['stock_by_warehouse'][$this->warehouse->id])->toBe(40.0)
        ->and((float) $result['cost_by_warehouse'][$this->warehouse->id])->toBe(12.5);
});

it('un kit calcula su stock como lo que alcanzan sus componentes, y su costo como la suma de las partes', function () {
    $this->actingAs(userWith('ver sales', 'ventas.editar-precio'));

    $pata = Product::factory()->create();
    $tablero = Product::factory()->create();
    $mesa = Product::factory()->create(); // el kit

    $mesaUnit = ProductUnit::factory()->create(['product_id' => $mesa->id, 'is_base' => true, 'factor' => 1]);

    ProductComponent::create(['product_id' => $mesa->id, 'component_product_id' => $pata->id, 'quantity' => 4]);
    ProductComponent::create(['product_id' => $mesa->id, 'component_product_id' => $tablero->id, 'quantity' => 1]);

    // 40 patas alcanzan para 10 mesas; 3 tableros alcanzan para 3 mesas → el kit alcanza para 3 (el menor).
    ProductWarehouse::create(['product_id' => $pata->id, 'warehouse_id' => $this->warehouse->id, 'stock' => 40, 'avg_cost' => 20]);
    ProductWarehouse::create(['product_id' => $tablero->id, 'warehouse_id' => $this->warehouse->id, 'stock' => 3, 'avg_cost' => 150]);

    $result = $this->getJson(route('pos.products.search', ['ids' => (string) $mesaUnit->id]))->json('units.0');

    expect((float) $result['stock_by_warehouse'][$this->warehouse->id])->toBe(3.0)
        // costo = 4 patas × 20 + 1 tablero × 150 = 230
        ->and((float) $result['cost_by_warehouse'][$this->warehouse->id])->toBe(230.0);
});

it('incluye las presentaciones hermanas del mismo producto (para el desglose de stock)', function () {
    $this->actingAs(userWith('ver sales'));

    $product = Product::factory()->create();
    $pieza = ProductUnit::factory()->create(['product_id' => $product->id, 'is_base' => true, 'name' => 'Unidad', 'factor' => 1]);
    $caja = ProductUnit::factory()->create(['product_id' => $product->id, 'is_base' => false, 'name' => 'Caja x12', 'factor' => 12]);

    $result = $this->getJson(route('pos.products.search', ['ids' => $pieza->id.','.$caja->id]))->json('units');

    $siblingIdsOfPieza = collect($result)->firstWhere('id', $pieza->id)['siblings'];
    expect(collect($siblingIdsOfPieza)->pluck('id')->all())->toEqualCanonicalizing([$pieza->id, $caja->id]);
});

it('un producto inactivo no aparece en la búsqueda', function () {
    $this->actingAs(userWith('ver sales'));

    $product = Product::factory()->create(['name' => 'Producto Inactivo', 'is_active' => false]);
    ProductUnit::factory()->create(['product_id' => $product->id, 'is_base' => true]);

    $response = $this->getJson(route('pos.products.search', ['q' => 'Inactivo']))->assertOk();

    expect($response->json('units'))->toBe([]);
});

/*
|--------------------------------------------------------------------------
| PosSearchController::searchCustomers(): buscador de clientes del POS
|--------------------------------------------------------------------------
| Reemplaza la cartera completa que antes se mandaba entera al navegador (PosFormData::build()):
| ahora cada búsqueda trae como máximo PosFormData::SEARCH_LIMIT clientes a la vez.
*/

it('el buscador de clientes exige estar autenticado', function () {
    $this->get(route('pos.customers.search'))->assertRedirect(route('login'));
});

it('los buscadores exigen acceso a Ventas, Pedidos, Consignaciones o Proformas', function () {
    $this->actingAs(userWith());

    $this->getJson(route('pos.products.search'))->assertForbidden();
    $this->getJson(route('pos.customers.search'))->assertForbidden();

    foreach (['ver orders', 'ver consignments', 'ver quotations'] as $permission) {
        $this->actingAs(userWith($permission));

        $this->getJson(route('pos.products.search'))->assertOk();
        $this->getJson(route('pos.customers.search'))->assertOk();
    }
});

it('nunca devuelve más de 15 clientes, aunque haya muchos más', function () {
    $this->actingAs(userWith('ver sales'));

    Customer::factory()->count(20)->create(['is_active' => true]);

    $response = $this->getJson(route('pos.customers.search'))->assertOk();

    expect($response->json('customers'))->toHaveCount(15);
});

it('busca clientes por nombre o documento', function () {
    $this->actingAs(userWith('ver sales'));

    $customer = Customer::factory()->create(['name' => 'Constructora Andina', 'document' => '1023456029']);
    $other = Customer::factory()->create(['name' => 'Otro Cliente']);

    foreach (['Constructora', '1023456029'] as $term) {
        $ids = $this->getJson(route('pos.customers.search', ['q' => $term]))->json('customers.*.id');
        expect($ids)->toContain($customer->id)->and($ids)->not->toContain($other->id);
    }
});

it('busca por ids puntuales sin limitarse a 15 (para restaurar el cliente ya elegido)', function () {
    $this->actingAs(userWith('ver sales'));

    $customers = Customer::factory()->count(3)->create();

    $response = $this->getJson(route('pos.customers.search', ['ids' => $customers->pluck('id')->implode(',')]))->assertOk();

    expect($response->json('customers.*.id'))->toEqualCanonicalizing($customers->pluck('id')->all());
});

it('trae la deuda actual del cliente por sus ventas a crédito vigentes, solo de los clientes encontrados', function () {
    $this->actingAs(userWith('ver sales'));

    $customer = Customer::factory()->create(['credit_limit' => 500]);
    orderFor($customer, ['payment_type' => Order::PAYMENT_CREDIT, 'amount' => 300, 'paid_amount' => 100]);

    $result = $this->getJson(route('pos.customers.search', ['ids' => (string) $customer->id]))->json('customers.0');

    expect((float) $result['owed'])->toBe(200.0)
        ->and((float) $result['credit_limit'])->toBe(500.0);
});

it('un cliente inactivo no aparece en la búsqueda', function () {
    $this->actingAs(userWith('ver sales'));

    Customer::factory()->create(['name' => 'Cliente Inactivo', 'is_active' => false]);

    $response = $this->getJson(route('pos.customers.search', ['q' => 'Inactivo']))->assertOk();

    expect($response->json('customers'))->toBe([]);
});
