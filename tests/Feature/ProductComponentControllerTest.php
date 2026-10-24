<?php

use App\Models\Product;
use App\Models\ProductComponent;
use App\Models\User;

beforeEach(function () {
    testBranch();
});

// =========================================================================
// 1. AUTENTICACIÓN Y AUTORIZACIÓN
// =========================================================================

describe('autenticación y autorización', function () {
    it('redirige al login a usuarios no autenticados en cualquier ruta', function (string $method, string $routeFn) {
        $kit = Product::factory()->create();
        $comp = Product::factory()->create();
        $pc = ProductComponent::factory()->create(['product_id' => $kit->id, 'component_product_id' => $comp->id]);

        $url = match ($routeFn) {
            'index' => route('product-components.index'),
            'store' => route('product-components.store'),
            'show' => route('product-components.show', $pc),
            'edit' => route('product-components.edit', $pc),
            'update' => route('product-components.update', $pc),
            'destroy' => route('product-components.destroy', $pc),
        };

        $response = match ($method) {
            'GET' => $this->get($url),
            'POST' => $this->post($url, []),
            'PUT' => $this->put($url, []),
            'DELETE' => $this->delete($url),
        };

        $response->assertRedirect(route('login'));
    })->with([
        ['GET', 'index'],
        ['POST', 'store'],
        ['GET', 'show'],
        ['GET', 'edit'],
        ['PUT', 'update'],
        ['DELETE', 'destroy'],
    ]);

    it('retorna 403 a usuarios autenticados sin los permisos requeridos', function (array $permissions, string $method, string $action) {
        $user = userWith(...$permissions);
        $kit = Product::factory()->create();
        $comp = Product::factory()->create();
        $pc = ProductComponent::factory()->create(['product_id' => $kit->id, 'component_product_id' => $comp->id]);

        $url = match ($action) {
            'index' => route('product-components.index'),
            'store' => route('product-components.store'),
            'show' => route('product-components.show', $pc),
            'edit' => route('product-components.edit', $pc),
            'update' => route('product-components.update', $pc),
            'destroy' => route('product-components.destroy', $pc),
        };

        $response = match ($method) {
            'GET' => $this->actingAs($user)->get($url),
            'POST' => $this->actingAs($user)->post($url, ['product_id' => $kit->id, 'component_product_id' => $comp->id, 'quantity' => 1]),
            'PUT' => $this->actingAs($user)->put($url, ['product_id' => $kit->id, 'component_product_id' => $comp->id, 'quantity' => 1]),
            'DELETE' => $this->actingAs($user)->delete($url),
        };

        $response->assertForbidden();
    })->with([
        [[], 'GET', 'index'],
        [['ver product-components'], 'POST', 'store'], // tiene ver, pero no crear
        [[], 'GET', 'show'],
        [['ver product-components'], 'GET', 'edit'], // tiene ver, pero no editar
        [['ver product-components'], 'PUT', 'update'], // tiene ver, pero no editar
        [['ver product-components'], 'DELETE', 'destroy'], // tiene ver, pero no eliminar
    ]);
});

// =========================================================================
// 2. INDEX Y BÚSQUEDA
// =========================================================================

describe('index y listado', function () {
    it('muestra el listado de componentes asociados', function () {
        $user = userWith('ver product-components');
        $kit = Product::factory()->create(['name' => 'Kit Computadora Gamer']);
        $comp = Product::factory()->create(['name' => 'Tarjeta Grafica RTX']);
        ProductComponent::factory()->create(['product_id' => $kit->id, 'component_product_id' => $comp->id, 'quantity' => 2]);

        $response = $this->actingAs($user)->get(route('product-components.index'));

        $response->assertOk()
            ->assertViewIs('product-components.index')
            ->assertSee('Kit Computadora Gamer')
            ->assertSee('Tarjeta Grafica RTX');
    });

    it('permite filtrar componentes por nombre de producto o de componente con filter[buscar]', function () {
        $user = userWith('ver product-components');
        $kit1 = Product::factory()->create(['name' => 'Kit Taladro Percutor']);
        $comp1 = Product::factory()->create(['name' => 'Broca 8mm']);
        $pc1 = ProductComponent::factory()->create(['product_id' => $kit1->id, 'component_product_id' => $comp1->id]);

        $kit2 = Product::factory()->create(['name' => 'Combo Pintura Blanca']);
        $comp2 = Product::factory()->create(['name' => 'Rodillo Epoxico']);
        $pc2 = ProductComponent::factory()->create(['product_id' => $kit2->id, 'component_product_id' => $comp2->id]);

        $response = $this->actingAs($user)->get(route('product-components.index', ['filter[buscar]' => 'Broca']));
        $response->assertOk();
        $items = $response->viewData('productComponents');
        expect($items->pluck('id')->all())->toContain($pc1->id)
            ->and($items->pluck('id')->all())->not->toContain($pc2->id);
    });

    it('permite filtrar componentes de un producto kit específico con filter[currentProduct]', function () {
        $user = userWith('ver product-components');
        $kit1 = Product::factory()->create();
        $kit2 = Product::factory()->create();
        $comp1 = Product::factory()->create();
        $comp2 = Product::factory()->create();

        $pc1 = ProductComponent::factory()->create(['product_id' => $kit1->id, 'component_product_id' => $comp1->id]);
        $pc2 = ProductComponent::factory()->create(['product_id' => $kit2->id, 'component_product_id' => $comp2->id]);

        $response = $this->actingAs($user)->get(route('product-components.index', ['filter[currentProduct]' => $kit1->id]));
        $response->assertOk();
        $items = $response->viewData('productComponents');
        expect($items->pluck('id')->all())->toContain($pc1->id)
            ->and($items->pluck('id')->all())->not->toContain($pc2->id);
    });

    it('los IDs de kits y componentes (kitIds/partIds) quedan como enteros, no como texto', function () {
        // DB::table() no tipa sus resultados: en producción (MySQL/PDO) pueden llegar como string ("5"),
        // mientras que $products sí castea sus ids a int en la vista. Si kitIds/partIds no se castean
        // también, la comparación en JS ("5" === 5) da false y la validación anti-kit-recursivo se
        // desactiva en silencio (nunca se nota en local, donde ambos coinciden por casualidad).
        $user = userWith('ver product-components');
        $kit = Product::factory()->create();
        $comp = Product::factory()->create();
        ProductComponent::factory()->create(['product_id' => $kit->id, 'component_product_id' => $comp->id]);

        $response = $this->actingAs($user)->get(route('product-components.index'));

        $kitIds = $response->viewData('kitIds');
        $partIds = $response->viewData('partIds');

        expect($kitIds->first())->toBeInt()->and($kitIds->all())->toContain($kit->id)
            ->and($partIds->first())->toBeInt()->and($partIds->all())->toContain($comp->id);
    });
});

// =========================================================================
// 3. REGISTRO Y REGLAS DE CICLOS / COMPONENTES (STORE)
// =========================================================================

describe('store y validaciones de kit', function () {
    it('agrega un componente exitosamente a un producto kit', function () {
        $user = userWith('ver product-components', 'crear product-components');
        $kit = Product::factory()->create();
        $comp = Product::factory()->create();

        $payload = [
            'product_id' => $kit->id,
            'component_product_id' => $comp->id,
            'quantity' => 3,
        ];

        $response = $this->actingAs($user)->post(route('product-components.store'), $payload);

        $response->assertRedirect(route('product-components.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('product_components', [
            'product_id' => $kit->id,
            'component_product_id' => $comp->id,
            'quantity' => 3,
        ]);
    });

    it('falla si un producto intenta ser componente de sí mismo', function () {
        $user = userWith('ver product-components', 'crear product-components');
        $product = Product::factory()->create();

        $payload = [
            'product_id' => $product->id,
            'component_product_id' => $product->id,
            'quantity' => 1,
        ];

        $response = $this->actingAs($user)->post(route('product-components.store'), $payload);

        $response->assertSessionHasErrors(['component_product_id']);
    });

    it('falla si el componente ya fue agregado previamente al mismo producto kit', function () {
        $user = userWith('ver product-components', 'crear product-components');
        $kit = Product::factory()->create();
        $comp = Product::factory()->create();

        ProductComponent::factory()->create([
            'product_id' => $kit->id,
            'component_product_id' => $comp->id,
            'quantity' => 1,
        ]);

        $payload = [
            'product_id' => $kit->id,
            'component_product_id' => $comp->id,
            'quantity' => 2,
        ];

        $response = $this->actingAs($user)->post(route('product-components.store'), $payload);

        $response->assertSessionHasErrors(['component_product_id']);
    });

    it('falla por ciclo inmediato si el componente ya usa al producto kit como su propio componente', function () {
        $user = userWith('ver product-components', 'crear product-components');
        $prodA = Product::factory()->create();
        $prodB = Product::factory()->create();

        // prodB ya tiene como componente a prodA
        ProductComponent::factory()->create([
            'product_id' => $prodB->id,
            'component_product_id' => $prodA->id,
            'quantity' => 1,
        ]);

        // Intentar agregar prodB como componente de prodA genera ciclo A -> B -> A
        $payload = [
            'product_id' => $prodA->id,
            'component_product_id' => $prodB->id,
            'quantity' => 1,
        ];

        $response = $this->actingAs($user)->post(route('product-components.store'), $payload);

        $response->assertSessionHasErrors(['component_product_id']);
    });

    it('falla si los campos requeridos están ausentes o la cantidad es menor a 1', function () {
        $user = userWith('ver product-components', 'crear product-components');

        $response = $this->actingAs($user)->post(route('product-components.store'), [
            'product_id' => null,
            'component_product_id' => null,
            'quantity' => 0,
        ]);

        $response->assertSessionHasErrors(['product_id', 'component_product_id', 'quantity']);
    });
});

// =========================================================================
// 4. SHOW, EDIT, UPDATE, DESTROY
// =========================================================================

describe('operaciones show, edit, update y destroy', function () {
    it('muestra la vista show con relaciones cargadas', function () {
        $user = userWith('ver product-components');
        $kit = Product::factory()->create(['name' => 'Kit Especial']);
        $comp = Product::factory()->create(['name' => 'Pieza Alpha']);
        $pc = ProductComponent::factory()->create(['product_id' => $kit->id, 'component_product_id' => $comp->id]);

        $response = $this->actingAs($user)->get(route('product-components.show', $pc));

        $response->assertOk()
            ->assertViewIs('product-components.show')
            ->assertSee('Kit Especial')
            ->assertSee('Pieza Alpha');
    });

    it('muestra la vista edit con los datos del componente', function () {
        $user = userWith('ver product-components', 'editar product-components');
        $kit = Product::factory()->create();
        $comp = Product::factory()->create();
        $pc = ProductComponent::factory()->create(['product_id' => $kit->id, 'component_product_id' => $comp->id]);

        $response = $this->actingAs($user)->get(route('product-components.edit', $pc));

        $response->assertOk()
            ->assertViewIs('product-components.edit');
    });

    it('actualiza la cantidad de un componente exitosamente', function () {
        $user = userWith('ver product-components', 'editar product-components');
        $kit = Product::factory()->create();
        $comp = Product::factory()->create();
        $pc = ProductComponent::factory()->create(['product_id' => $kit->id, 'component_product_id' => $comp->id, 'quantity' => 2]);

        $payload = [
            'product_id' => $kit->id,
            'component_product_id' => $comp->id,
            'quantity' => 5,
        ];

        $response = $this->actingAs($user)->put(route('product-components.update', $pc), $payload);

        $response->assertRedirect(route('product-components.index'))
            ->assertSessionHas('success');

        expect($pc->fresh()->quantity)->toBe(5);
    });

    it('elimina un componente de un kit', function () {
        $user = userWith('ver product-components', 'eliminar product-components');
        $kit = Product::factory()->create();
        $comp = Product::factory()->create();
        $pc = ProductComponent::factory()->create(['product_id' => $kit->id, 'component_product_id' => $comp->id]);

        $response = $this->actingAs($user)->delete(route('product-components.destroy', $pc));

        $response->assertRedirect(route('product-components.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('product_components', ['id' => $pc->id]);
    });
});
