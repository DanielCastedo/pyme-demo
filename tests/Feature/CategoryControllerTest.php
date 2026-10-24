<?php

use App\Models\Category;

/*
|--------------------------------------------------------------------------
| CategoryController: CRUD, validaciones, permisos y búsquedas
|--------------------------------------------------------------------------
*/

/** Datos válidos de una categoría; cada prueba sobrescribe solo lo que le interesa. */
function categoryPayload(array $overrides = []): array
{
    return $overrides + [
        'name' => 'Filtros y Lubricantes',
        'description' => 'Repuestos y fluidos automotrices de importación',
    ];
}

/** Casos inválidos para store y update: [campo, valor inválido]. */
dataset('invalid category fields', [
    'sin nombre' => ['name', ''],
    'nombre de más de 255 caracteres' => ['name', str_repeat('a', 256)],
]);

// ─────────────────────────────────────────────────────────────────────────────
// Autenticación y permisos
// ─────────────────────────────────────────────────────────────────────────────

describe('acceso', function () {
    it('redirige al login a un visitante', function () {
        $this->get(route('categories.index'))->assertRedirect(route('login'));
    });

    it('rechaza (403) a un usuario sin permisos en todas las acciones', function () {
        $this->actingAs(userWith());
        $category = Category::factory()->create();

        $this->get(route('categories.index'))->assertForbidden();
        $this->get(route('categories.show', $category))->assertForbidden();
        $this->get(route('categories.edit', $category))->assertForbidden();
        $this->post(route('categories.store'), categoryPayload())->assertForbidden();
        $this->put(route('categories.update', $category), categoryPayload())->assertForbidden();
        $this->delete(route('categories.destroy', $category))->assertForbidden();
    });

    it('aplica el permiso específico de cada acción (la policy) aunque pueda ver categorías', function () {
        $this->actingAs(userWith('ver categories'));
        $category = Category::factory()->create();

        $this->get(route('categories.index'))->assertOk();
        $this->get(route('categories.show', $category))->assertOk();
        $this->get(route('categories.edit', $category))->assertForbidden();
        $this->post(route('categories.store'), categoryPayload())->assertForbidden();
        $this->put(route('categories.update', $category), categoryPayload())->assertForbidden();
        $this->delete(route('categories.destroy', $category))->assertForbidden();

        expect(Category::count())->toBe(1);
    });

    it('deja pasar todo al permiso "admin" (bypass de las policies)', function () {
        $this->actingAs(userWith('ver categories', 'admin'));

        $this->post(route('categories.store'), categoryPayload())->assertRedirect(route('categories.index'));

        $category = Category::firstOrFail();
        $this->put(route('categories.update', $category), categoryPayload(['name' => 'Editado por admin']))->assertRedirect();
        $this->delete(route('categories.destroy', $category))->assertRedirect();

        expect(Category::count())->toBe(0);
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// index
// ─────────────────────────────────────────────────────────────────────────────

describe('index', function () {
    beforeEach(fn () => $this->actingAs(userWith('ver categories')));

    it('lista las categorías ordenadas por nombre y pagina de a 10', function () {
        foreach (range(1, 12) as $i) {
            Category::factory()->create(['name' => sprintf('Categoría %02d', $i)]);
        }

        $response = $this->get(route('categories.index'))->assertOk()->assertViewIs('categories.index');

        $categories = $response->viewData('categories');

        expect($categories->total())->toBe(12)
            ->and($categories->count())->toBe(10)
            ->and($categories->perPage())->toBe(10)
            ->and($categories->first()->name)->toBe('Categoría 01')
            ->and($categories->last()->name)->toBe('Categoría 10');

        $second = $this->get(route('categories.index', ['page' => 2]))->viewData('categories');
        expect($second->pluck('name')->all())->toBe(['Categoría 11', 'Categoría 12']);
    });

    it('muestra el mensaje vacío cuando no hay categorías', function () {
        $this->get(route('categories.index'))
            ->assertOk()
            ->assertSee('No hay categorías registradas');
    });

    it('busca por nombre (filter[buscar])', function () {
        Category::factory()->create(['name' => 'Filtros de Aire']);
        Category::factory()->create(['name' => 'Aceites Sintéticos']);
        Category::factory()->create(['name' => 'Baterías 12V']);

        $names = $this->get(route('categories.index', ['filter' => ['buscar' => 'Filtros']]))
            ->viewData('categories')->pluck('name')->all();

        expect($names)->toBe(['Filtros de Aire']);
    });

    it('devuelve una lista vacía si la búsqueda no encuentra nada', function () {
        Category::factory()->count(2)->create();

        $categories = $this->get(route('categories.index', ['filter' => ['buscar' => 'InexistenteXYZ']]))
            ->viewData('categories');

        expect($categories->total())->toBe(0);
    });

    it('ordena por los campos permitidos y rechaza los no permitidos con 400', function () {
        Category::factory()->create(['name' => 'Baterías']);
        Category::factory()->create(['name' => 'Aceites']);

        $byNameDesc = $this->get(route('categories.index', ['sort' => '-name']))->viewData('categories')->pluck('name')->all();
        expect($byNameDesc)->toBe(['Baterías', 'Aceites']);

        $this->get(route('categories.index', ['sort' => 'created_at']))->assertStatus(400);
    });

    it('conserva la búsqueda en los enlaces de paginación', function () {
        Category::factory()->count(12)->create(['name' => 'Repuesto']);

        $paginator = $this->get(route('categories.index', ['filter' => ['buscar' => 'Repuesto']]))->viewData('categories');

        expect($paginator->total())->toBe(12)
            ->and(urldecode($paginator->url(2)))->toContain('filter[buscar]=Repuesto');
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// store
// ─────────────────────────────────────────────────────────────────────────────

describe('store', function () {
    beforeEach(fn () => $this->actingAs(userWith('ver categories', 'crear categories')));

    it('crea la categoría, redirige al listado y avisa', function () {
        $this->post(route('categories.store'), categoryPayload())
            ->assertRedirect(route('categories.index'))
            ->assertSessionHas('success', 'Categoría registrada exitosamente.');

        $this->assertDatabaseHas('categories', [
            'name' => 'Filtros y Lubricantes',
            'description' => 'Repuestos y fluidos automotrices de importación',
        ]);
    });

    it('solo exige el nombre: la descripción es opcional', function () {
        $this->post(route('categories.store'), ['name' => 'Solo nombre'])
            ->assertRedirect(route('categories.index'))
            ->assertSessionHasNoErrors();

        $category = Category::firstOrFail();
        expect($category->name)->toBe('Solo nombre')
            ->and($category->description)->toBeNull();
    });

    it('valida los campos y no crea nada', function (string $field, mixed $invalidValue) {
        $this->from(route('categories.index'))
            ->post(route('categories.store'), categoryPayload([$field => $invalidValue]))
            ->assertRedirect(route('categories.index'))
            ->assertSessionHasErrors($field);

        expect(Category::count())->toBe(0);
    })->with('invalid category fields');

    it('muestra el mensaje en español cuando falta el nombre', function () {
        $this->post(route('categories.store'), ['name' => ''])
            ->assertSessionHasErrors(['name' => 'El nombre de la categoría es obligatorio.']);
    });

    it('vuelve al formulario con lo escrito cuando hay errores (old input)', function () {
        $this->from(route('categories.index'))
            ->post(route('categories.store'), ['name' => '', 'description' => 'Descripción previa'])
            ->assertRedirect(route('categories.index'))
            ->assertSessionHasInput('description', 'Descripción previa');
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// show
// ─────────────────────────────────────────────────────────────────────────────

describe('show', function () {
    beforeEach(fn () => $this->actingAs(userWith('ver categories')));

    it('muestra el detalle de la categoría', function () {
        $category = Category::factory()->create(['name' => 'Llantas']);

        $this->get(route('categories.show', $category))
            ->assertOk()
            ->assertViewIs('categories.show')
            ->assertViewHas('category', fn ($c) => $c->id === $category->id);
    });

    it('responde 404 si la categoría no existe', function () {
        $this->get(route('categories.show', 99999))->assertNotFound();
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// edit
// ─────────────────────────────────────────────────────────────────────────────

describe('edit', function () {
    beforeEach(fn () => $this->actingAs(userWith('ver categories', 'editar categories')));

    it('muestra el formulario con los datos actuales', function () {
        $category = Category::factory()->create([
            'name' => 'Frenos',
            'description' => 'Discos y pastillas',
        ]);

        $this->get(route('categories.edit', $category))
            ->assertOk()
            ->assertViewIs('categories.edit')
            ->assertViewHas('category', fn ($c) => $c->name === 'Frenos');
    });

    it('responde 404 si la categoría no existe', function () {
        $this->get(route('categories.edit', 99999))->assertNotFound();
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// update
// ─────────────────────────────────────────────────────────────────────────────

describe('update', function () {
    beforeEach(fn () => $this->actingAs(userWith('ver categories', 'editar categories')));

    it('actualiza los datos, redirige al listado y avisa', function () {
        $category = Category::factory()->create(['name' => 'Nombre Viejo']);

        $this->put(route('categories.update', $category), [
            'name' => 'Nombre Nuevo',
            'description' => 'Descripción actualizada',
        ])
            ->assertRedirect(route('categories.index'))
            ->assertSessionHas('success', 'Categoría actualizada exitosamente.');

        $category->refresh();
        expect($category->name)->toBe('Nombre Nuevo')
            ->and($category->description)->toBe('Descripción actualizada');
    });

    it('permite quitar la descripción dejándola vacía', function () {
        $category = Category::factory()->create(['description' => 'Con descripción']);

        $this->put(route('categories.update', $category), ['name' => $category->name, 'description' => ''])
            ->assertRedirect(route('categories.index'))
            ->assertSessionHasNoErrors();

        expect($category->refresh()->description)->toBeNull();
    });

    it('valida los campos y no modifica la categoría', function (string $field, mixed $invalidValue) {
        $category = Category::factory()->create(['name' => 'Original']);

        $this->from(route('categories.edit', $category))
            ->put(route('categories.update', $category), categoryPayload([$field => $invalidValue]))
            ->assertRedirect(route('categories.edit', $category))
            ->assertSessionHasErrors($field);

        expect($category->refresh()->name)->toBe('Original');
    })->with('invalid category fields');

    it('responde 404 si la categoría no existe', function () {
        $this->put(route('categories.update', 99999), categoryPayload())->assertNotFound();
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// destroy
// ─────────────────────────────────────────────────────────────────────────────

describe('destroy', function () {
    beforeEach(fn () => $this->actingAs(userWith('ver categories', 'eliminar categories')));

    it('elimina la categoría, redirige y avisa', function () {
        $category = Category::factory()->create();

        $this->delete(route('categories.destroy', $category))
            ->assertRedirect(route('categories.index'))
            ->assertSessionHas('success', 'Categoría eliminada exitosamente.');

        expect(Category::count())->toBe(0);
    });

    it('elimina solo la categoría indicada', function () {
        $cat1 = Category::factory()->create(['name' => 'Cat 1']);
        $cat2 = Category::factory()->create(['name' => 'Cat 2']);

        $this->delete(route('categories.destroy', $cat1))->assertRedirect();

        expect(Category::count())->toBe(1)
            ->and(Category::first()->id)->toBe($cat2->id);
    });

    it('responde 404 si la categoría no existe', function () {
        $this->delete(route('categories.destroy', 99999))->assertNotFound();
    });
});
