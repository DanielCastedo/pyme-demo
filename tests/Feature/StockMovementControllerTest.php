<?php

use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\StockMovement;
use App\Models\Type;
use App\Models\Warehouse;
use App\Services\StockMovementService;

/*
|--------------------------------------------------------------------------
| StockMovementController: Movimientos de stock (entradas, salidas), reversión y permisos
|--------------------------------------------------------------------------
*/

function stockMovementPayload(Type $type, Warehouse $warehouse, ProductUnit $unit, array $overrides = []): array
{
    return $overrides + [
        'type_id' => $type->id,
        'warehouse_id' => $warehouse->id,
        'date' => now()->toDateString(),
        'note' => 'Ingreso por recepción de lote importado',
        'items' => [
            [
                'product_unit_id' => $unit->id,
                'quantity' => 20,
                'unit_cost' => 15.50,
            ],
        ],
    ];
}

// ─────────────────────────────────────────────────────────────────────────────
// Autenticación y permisos
// ─────────────────────────────────────────────────────────────────────────────

describe('acceso', function () {
    it('redirige al login a un visitante', function () {
        $this->get(route('stock-movements.index'))->assertRedirect(route('login'));
    });

    it('rechaza (403) a un usuario sin permisos en todas las acciones', function () {
        $this->actingAs(userWith());
        $branch = testBranch();
        $warehouse = Warehouse::factory()->create(['branche_id' => $branch->id]);
        $type = Type::factory()->create();
        $movement = StockMovement::factory()->create(['warehouse_id' => $warehouse->id, 'type_id' => $type->id]);

        $this->get(route('stock-movements.index'))->assertForbidden();
        $this->get(route('stock-movements.show', $movement))->assertForbidden();
        $this->get(route('stock-movements.create'))->assertForbidden();
        $this->get(route('stock-movements.edit', $movement))->assertForbidden();
        $this->delete(route('stock-movements.destroy', $movement))->assertForbidden();
    });

    it('aplica el permiso específico de cada acción (la policy)', function () {
        $this->actingAs(userWith('ver stock-movements'));
        $branch = testBranch();
        $warehouse = Warehouse::factory()->create(['branche_id' => $branch->id]);
        $type = Type::factory()->create();
        $movement = StockMovement::factory()->create(['warehouse_id' => $warehouse->id, 'type_id' => $type->id]);

        $this->get(route('stock-movements.index'))->assertOk();
        $this->get(route('stock-movements.show', $movement))->assertOk();
        $this->get(route('stock-movements.create'))->assertForbidden();
        $this->get(route('stock-movements.edit', $movement))->assertForbidden();
        $this->delete(route('stock-movements.destroy', $movement))->assertForbidden();
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// index
// ─────────────────────────────────────────────────────────────────────────────

describe('index', function () {
    beforeEach(fn () => $this->actingAs(userWith('ver stock-movements')));

    it('lista los movimientos de stock y pagina de a 10', function () {
        $branch = testBranch();
        $warehouse = Warehouse::factory()->create(['branche_id' => $branch->id]);
        $type = Type::factory()->create();

        foreach (range(1, 12) as $i) {
            StockMovement::factory()->create([
                'warehouse_id' => $warehouse->id,
                'type_id' => $type->id,
                'date' => now()->subDays($i)->toDateString(),
            ]);
        }

        $response = $this->get(route('stock-movements.index'))->assertOk()->assertViewIs('stock-movements.index');

        $movements = $response->viewData('stockMovements');

        expect($movements->total())->toBeGreaterThanOrEqual(12)
            ->and($movements->perPage())->toBe(10);
    });

    it('busca por nota, nombre de almacén o tipo (filter[buscar])', function (string $term, string $expectedNote) {
        $branch = testBranch();
        $w1 = Warehouse::factory()->create(['branche_id' => $branch->id, 'name' => 'Almacén Principal']);
        $w2 = Warehouse::factory()->create(['branche_id' => $branch->id, 'name' => 'Bodega Repuestos']);
        $t1 = Type::factory()->create(['name' => 'Entrada']);
        $t2 = Type::factory()->create(['name' => 'Salida']);

        StockMovement::factory()->create(['warehouse_id' => $w1->id, 'type_id' => $t1->id, 'note' => 'Carga inicial contenedores']);
        StockMovement::factory()->create(['warehouse_id' => $w2->id, 'type_id' => $t2->id, 'note' => 'Merma por rotura']);

        $notes = $this->get(route('stock-movements.index', ['filter' => ['buscar' => $term]]))
            ->viewData('stockMovements')->pluck('note')->all();

        expect($notes)->toContain($expectedNote);
    })->with([
        'por nota' => ['contenedores', 'Carga inicial contenedores'],
        'por almacén' => ['Repuestos', 'Merma por rotura'],
        'por tipo' => ['Entrada', 'Carga inicial contenedores'],
    ]);

    it('ordena por campos permitidos y rechaza los no permitidos con 400', function () {
        $this->get(route('stock-movements.index', ['sort' => 'date']))->assertOk();
        $this->get(route('stock-movements.index', ['sort' => '-id']))->assertOk();
        $this->get(route('stock-movements.index', ['sort' => 'note']))->assertStatus(400);
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// create y store
// ─────────────────────────────────────────────────────────────────────────────

describe('create y store', function () {
    beforeEach(fn () => $this->actingAs(userWith('ver stock-movements', 'crear stock-movements')));

    it('muestra el formulario de creación con tipos y almacenes, sin precargar el catálogo entero', function () {
        Type::firstOrCreate(['code' => 'ENT'], ['name' => 'Entrada']);

        $this->get(route('stock-movements.create'))
            ->assertOk()
            ->assertViewIs('stock-movements.create')
            ->assertViewHas('types')
            ->assertViewHas('warehouses')
            ->assertViewMissing('productUnits'); // ver StockMovementController::search(): se pide de a poco
    });

    it('registra el movimiento llamando al servicio de stock', function () {
        $branch = testBranch();
        $warehouse = Warehouse::factory()->create(['branche_id' => $branch->id]);
        $type = Type::firstOrCreate(['code' => 'ENT'], ['name' => 'Entrada']);
        $product = Product::factory()->create();
        $unit = ProductUnit::factory()->create(['product_id' => $product->id]);

        $payload = stockMovementPayload($type, $warehouse, $unit);

        $mockService = mock(StockMovementService::class);
        $mockService->shouldReceive('register')->once()->with(
            Mockery::on(fn ($t) => $t->id === $type->id),
            $warehouse->id,
            $payload['date'],
            $payload['note'],
            auth()->id(),
            $payload['items']
        );
        $this->app->instance(StockMovementService::class, $mockService);

        $this->post(route('stock-movements.store'), $payload)
            ->assertRedirect(route('stock-movements.index'))
            ->assertSessionHas('success');
    });

    it('valida campos obligatorios del movimiento', function () {
        $this->post(route('stock-movements.store'), [])
            ->assertSessionHasErrors(['type_id', 'warehouse_id', 'date', 'items']);
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// show, edit, update y destroy
// ─────────────────────────────────────────────────────────────────────────────

describe('show, edit, update y destroy', function () {
    it('muestra el detalle del movimiento de stock', function () {
        $this->actingAs(userWith('ver stock-movements'));
        $branch = testBranch();
        $warehouse = Warehouse::factory()->create(['branche_id' => $branch->id]);
        $movement = StockMovement::factory()->create(['warehouse_id' => $warehouse->id]);

        $this->get(route('stock-movements.show', $movement))
            ->assertOk()
            ->assertViewIs('stock-movements.show')
            ->assertViewHas('stockMovement', fn ($m) => $m->id === $movement->id);
    });

    it('actualiza la nota del movimiento con permiso de edición', function () {
        $this->actingAs(userWith('ver stock-movements', 'editar stock-movements'));
        $branch = testBranch();
        $warehouse = Warehouse::factory()->create(['branche_id' => $branch->id]);
        $movement = StockMovement::factory()->create(['warehouse_id' => $warehouse->id, 'note' => 'Nota previa']);

        $this->put(route('stock-movements.update', $movement), ['note' => 'Nota editada'])
            ->assertRedirect(route('stock-movements.index'))
            ->assertSessionHas('success');

        expect($movement->refresh()->note)->toBe('Nota editada');
    });

    it('revierte y elimina el movimiento con permiso de eliminación', function () {
        $this->actingAs(userWith('ver stock-movements', 'eliminar stock-movements'));
        $branch = testBranch();
        $warehouse = Warehouse::factory()->create(['branche_id' => $branch->id]);
        $movement = StockMovement::factory()->create(['warehouse_id' => $warehouse->id]);

        $mockService = mock(StockMovementService::class);
        $mockService->shouldReceive('revert')->once()->with(Mockery::on(fn ($m) => $m->id === $movement->id));
        $this->app->instance(StockMovementService::class, $mockService);

        $this->delete(route('stock-movements.destroy', $movement))
            ->assertRedirect(route('stock-movements.index'))
            ->assertSessionHas('success');
    });
});
