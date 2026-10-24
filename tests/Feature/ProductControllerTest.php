<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| ProductController: CRUD, fotos, SKU único, filtros y permisos
|--------------------------------------------------------------------------
*/

/** Datos válidos de un producto. */
function productPayload(Category $category, array $overrides = []): array
{
    return $overrides + [
        'category_id' => $category->id,
        'sku' => 'PRD-001-TEST',
        'name' => 'Filtro de Aceite PH6607',
        'description' => 'Filtro de rosca para motores japoneses',
        'is_active' => '1',
    ];
}

/** Casos inválidos para store y update. */
dataset('invalid product fields', [
    'sin categoría' => ['category_id', ''],
    'categoría inexistente' => ['category_id', 99999],
    'sin SKU' => ['sku', ''],
    'SKU de más de 100 caracteres' => ['sku', str_repeat('k', 101)],
    'sin nombre' => ['name', ''],
    'nombre de más de 255 caracteres' => ['name', str_repeat('n', 256)],
    'is_active no booleano' => ['is_active', 'invalido'],
]);

// ─────────────────────────────────────────────────────────────────────────────
// Autenticación y permisos
// ─────────────────────────────────────────────────────────────────────────────

describe('acceso', function () {
    it('redirige al login a un visitante', function () {
        $this->get(route('products.index'))->assertRedirect(route('login'));
    });

    it('rechaza (403) a un usuario sin permisos en todas las acciones', function () {
        $this->actingAs(userWith());
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);

        $this->get(route('products.index'))->assertForbidden();
        $this->get(route('products.show', $product))->assertForbidden();
        $this->get(route('products.edit', $product))->assertForbidden();
        $this->post(route('products.store'), productPayload($category))->assertForbidden();
        $this->put(route('products.update', $product), productPayload($category))->assertForbidden();
        $this->delete(route('products.destroy', $product))->assertForbidden();
    });

    it('aplica el permiso específico de cada acción (la policy) aunque pueda ver productos', function () {
        $this->actingAs(userWith('ver products'));
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);

        $this->get(route('products.index'))->assertOk();
        $this->get(route('products.show', $product))->assertOk();
        $this->get(route('products.edit', $product))->assertForbidden();
        $this->post(route('products.store'), productPayload($category))->assertForbidden();
        $this->put(route('products.update', $product), productPayload($category))->assertForbidden();
        $this->delete(route('products.destroy', $product))->assertForbidden();

        expect(Product::where('id', $product->id)->exists())->toBeTrue();
    });

    it('deja pasar todo al permiso "admin" (bypass de las policies)', function () {
        $this->actingAs(userWith('ver products', 'admin'));
        $category = Category::factory()->create();

        $this->post(route('products.store'), productPayload($category, ['sku' => 'PRD-ADM-1']))
            ->assertRedirect(route('products.index'));

        $product = Product::where('sku', 'PRD-ADM-1')->firstOrFail();
        $this->put(route('products.update', $product), productPayload($category, ['name' => 'Editado Admin']))
            ->assertRedirect();
        $this->delete(route('products.destroy', $product))->assertRedirect();

        expect(Product::where('sku', 'PRD-ADM-1')->exists())->toBeFalse();
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// index
// ─────────────────────────────────────────────────────────────────────────────

describe('index', function () {
    beforeEach(fn () => $this->actingAs(userWith('ver products')));

    it('lista los productos ordenados por nombre y pagina de a 10', function () {
        $category = Category::factory()->create();
        foreach (range(1, 12) as $i) {
            Product::factory()->create([
                'category_id' => $category->id,
                'name' => sprintf('Producto %02d', $i),
                'sku' => sprintf('SKU-%02d', $i),
            ]);
        }

        $response = $this->get(route('products.index'))->assertOk()->assertViewIs('products.index');

        $products = $response->viewData('products');

        expect($products->total())->toBeGreaterThanOrEqual(12)
            ->and($products->perPage())->toBe(10);
    });

    it('busca por SKU, nombre y nombre de categoría (filter[buscar])', function (string $term, string $expectedName) {
        $catLubricantes = Category::factory()->create(['name' => 'Lubricantes y Fluidos']);
        $catFrenos = Category::factory()->create(['name' => 'Frenos y Discos']);

        Product::factory()->create([
            'category_id' => $catLubricantes->id,
            'sku' => 'SKU-LUB-100',
            'name' => 'Aceite 10W40 Sintético',
        ]);

        Product::factory()->create([
            'category_id' => $catFrenos->id,
            'sku' => 'SKU-DISC-500',
            'name' => 'Pastillas Delanteras Cerámicas',
        ]);

        $names = $this->get(route('products.index', ['filter' => ['buscar' => $term]]))
            ->viewData('products')->pluck('name')->all();

        expect($names)->toContain($expectedName);
    })->with([
        'por SKU' => ['DISC-500', 'Pastillas Delanteras Cerámicas'],
        'por nombre' => ['10W40', 'Aceite 10W40 Sintético'],
        'por categoría' => ['Lubricantes', 'Aceite 10W40 Sintético'],
    ]);

    it('filtra por categoría (filter[currentCategory])', function () {
        $c1 = Category::factory()->create();
        $c2 = Category::factory()->create();

        Product::factory()->create(['category_id' => $c1->id, 'name' => 'Prod C1']);
        Product::factory()->create(['category_id' => $c2->id, 'name' => 'Prod C2']);

        $names = $this->get(route('products.index', ['filter' => ['currentCategory' => $c1->id]]))
            ->viewData('products')->pluck('name')->all();

        expect($names)->toContain('Prod C1')
            ->and($names)->not->toContain('Prod C2');
    });

    it('ordena por los campos permitidos y rechaza los no permitidos con 400', function () {
        $this->get(route('products.index', ['sort' => 'name']))->assertOk();
        $this->get(route('products.index', ['sort' => '-sku']))->assertOk();
        $this->get(route('products.index', ['sort' => 'price_sale']))->assertStatus(400);
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// store
// ─────────────────────────────────────────────────────────────────────────────

describe('store', function () {
    beforeEach(fn () => $this->actingAs(userWith('ver products', 'crear products')));

    it('crea el producto, redirige al listado y avisa', function () {
        $category = Category::factory()->create();

        $this->post(route('products.store'), productPayload($category))
            ->assertRedirect(route('products.index'))
            ->assertSessionHas('success', 'Producto registrado exitosamente.');

        $this->assertDatabaseHas('products', [
            'category_id' => $category->id,
            'sku' => 'PRD-001-TEST',
            'name' => 'Filtro de Aceite PH6607',
            'is_active' => true,
        ]);
    });

    it('no permite registrar productos con SKU duplicado', function () {
        $category = Category::factory()->create();
        Product::factory()->create(['sku' => 'SKU-EXISTE', 'category_id' => $category->id]);

        $this->from(route('products.index'))
            ->post(route('products.store'), productPayload($category, ['sku' => 'SKU-EXISTE']))
            ->assertRedirect(route('products.index'))
            ->assertSessionHasErrors(['sku' => 'Ya existe un producto con ese código.']);
    });

    it('sube y guarda la foto del producto en el disco uploads', function () {
        Storage::fake('uploads');
        $category = Category::factory()->create();

        $photo = UploadedFile::fake()->image('producto.jpg', 400, 400);

        $this->post(route('products.store'), productPayload($category, ['photo' => $photo]))
            ->assertRedirect(route('products.index'))
            ->assertSessionHasNoErrors();

        $product = Product::where('sku', 'PRD-001-TEST')->firstOrFail();

        expect($product->photo)->not->toBeNull();
        Storage::disk('uploads')->assertExists($product->photo);
    });

    it('valida los campos y no crea nada', function (string $field, mixed $invalidValue) {
        $category = Category::factory()->create();
        $initialCount = Product::count();

        $this->from(route('products.index'))
            ->post(route('products.store'), productPayload($category, [$field => $invalidValue]))
            ->assertRedirect(route('products.index'))
            ->assertSessionHasErrors($field);

        expect(Product::count())->toBe($initialCount);
    })->with('invalid product fields');

    it('alta rápida (JSON): crea el producto con su unidad base y responde con ambos para usarlos sin recargar', function () {
        $category = Category::factory()->create();
        $unit = Unit::firstOrCreate(['name' => 'Unidad']);

        $response = $this->postJson(route('products.store'), productPayload($category, [
            'create_unit' => 1,
            'unit_id' => $unit->id,
            'unit_price' => 25.5,
        ]))->assertCreated();

        $product = Product::where('sku', 'PRD-001-TEST')->firstOrFail();

        $response->assertJson([
            'success' => true,
            'unit' => [
                'product_id' => $product->id,
                'name' => $product->name,
                'unit' => 'Unidad',
                'sku' => $product->sku,
                'barcode' => $product->sku,
                'factor' => 1.0,
                'cost' => 0.0,
            ],
        ]);
        expect($response->json('unit.id'))->toBe($product->units->first()->id);
    });

    it('alta rápida (JSON) devuelve los errores de validación en JSON, sin redirigir', function () {
        $category = Category::factory()->create();

        $this->postJson(route('products.store'), productPayload($category, ['sku' => '']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('sku');
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// show y edit
// ─────────────────────────────────────────────────────────────────────────────

describe('show y edit', function () {
    it('muestra el detalle del producto cargando relaciones', function () {
        $this->actingAs(userWith('ver products'));
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);

        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertViewIs('products.show')
            ->assertViewHas('product', fn ($p) => $p->id === $product->id);
    });

    it('muestra el formulario de edición con la lista de categorías', function () {
        $this->actingAs(userWith('ver products', 'editar products'));
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);

        $this->get(route('products.edit', $product))
            ->assertOk()
            ->assertViewIs('products.edit')
            ->assertViewHas('product', fn ($p) => $p->id === $product->id)
            ->assertViewHas('categories');
    });

    it('responde 404 si el producto no existe', function () {
        $this->actingAs(userWith('ver products', 'editar products'));
        $this->get(route('products.show', 99999))->assertNotFound();
        $this->get(route('products.edit', 99999))->assertNotFound();
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// update
// ─────────────────────────────────────────────────────────────────────────────

describe('update', function () {
    beforeEach(fn () => $this->actingAs(userWith('ver products', 'editar products')));

    it('actualiza los datos, redirige al listado y avisa', function () {
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id, 'name' => 'Prod Viejo']);

        $this->put(route('products.update', $product), [
            'category_id' => $category->id,
            'sku' => $product->sku,
            'name' => 'Prod Nuevo',
            'is_active' => '1',
        ])
            ->assertRedirect(route('products.index'))
            ->assertSessionHas('success', 'Producto actualizado exitosamente.');

        expect($product->refresh()->name)->toBe('Prod Nuevo');
    });

    it('permite guardar manteniendo el propio SKU sin choque', function () {
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id, 'sku' => 'SKU-MIO']);

        $this->put(route('products.update', $product), productPayload($category, ['sku' => 'SKU-MIO']))
            ->assertRedirect(route('products.index'))
            ->assertSessionHasNoErrors();
    });

    it('no permite cambiar el SKU al de otro producto existente', function () {
        $category = Category::factory()->create();
        Product::factory()->create(['category_id' => $category->id, 'sku' => 'SKU-OTRO']);
        $product = Product::factory()->create(['category_id' => $category->id, 'sku' => 'SKU-ACTUAL']);

        $this->from(route('products.edit', $product))
            ->put(route('products.update', $product), productPayload($category, ['sku' => 'SKU-OTRO']))
            ->assertRedirect(route('products.edit', $product))
            ->assertSessionHasErrors(['sku' => 'Ya existe otro producto con ese código.']);

        expect($product->refresh()->sku)->toBe('SKU-ACTUAL');
    });

    it('reemplaza la foto y elimina la anterior del disco', function () {
        Storage::fake('uploads');
        $category = Category::factory()->create();

        $oldFile = UploadedFile::fake()->image('old.jpg');
        $oldPath = $oldFile->store('products', 'uploads');

        $product = Product::factory()->create(['category_id' => $category->id, 'photo' => $oldPath]);
        Storage::disk('uploads')->assertExists($oldPath);

        $newFile = UploadedFile::fake()->image('new.png');

        $this->put(route('products.update', $product), productPayload($category, ['photo' => $newFile]))
            ->assertRedirect(route('products.index'))
            ->assertSessionHasNoErrors();

        $product->refresh();
        expect($product->photo)->not->toBe($oldPath);
        Storage::disk('uploads')->assertMissing($oldPath);
        Storage::disk('uploads')->assertExists($product->photo);
    });

    it('valida los campos y no modifica el producto', function (string $field, mixed $invalidValue) {
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id, 'name' => 'Original']);

        $this->from(route('products.edit', $product))
            ->put(route('products.update', $product), productPayload($category, [$field => $invalidValue]))
            ->assertRedirect(route('products.edit', $product))
            ->assertSessionHasErrors($field);

        expect($product->refresh()->name)->toBe('Original');
    })->with('invalid product fields');
});

// ─────────────────────────────────────────────────────────────────────────────
// destroy
// ─────────────────────────────────────────────────────────────────────────────

describe('destroy', function () {
    beforeEach(fn () => $this->actingAs(userWith('ver products', 'eliminar products')));

    it('elimina un producto y borra su foto asociada', function () {
        Storage::fake('uploads');
        $category = Category::factory()->create();

        $photo = UploadedFile::fake()->image('prod.jpg');
        $path = $photo->store('products', 'uploads');

        $product = Product::factory()->create(['category_id' => $category->id, 'photo' => $path]);

        $this->delete(route('products.destroy', $product))
            ->assertRedirect(route('products.index'))
            ->assertSessionHas('success', 'Producto eliminado exitosamente.');

        expect(Product::where('id', $product->id)->exists())->toBeFalse();
        Storage::disk('uploads')->assertMissing($path);
    });

    it('responde 404 si el producto no existe', function () {
        $this->delete(route('products.destroy', 99999))->assertNotFound();
    });
});
