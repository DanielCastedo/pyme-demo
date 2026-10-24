<?php

use App\Models\Product;
use App\Models\Promotion;

/*
|--------------------------------------------------------------------------
| PromotionController: CRUD, vigencias, productos asociados y permisos
|--------------------------------------------------------------------------
*/

function promotionPayload(array $overrides = []): array
{
    return $overrides + [
        'name' => 'Cyber Week Repuestos',
        'value' => '15.00',
        'min_quantity' => 2,
        'start_date' => now()->toDateString(),
        'end_date' => now()->addDays(7)->toDateString(),
        'is_active' => '1',
    ];
}

dataset('invalid promotion fields', [
    'sin nombre' => ['name', ''],
    'nombre de más de 255 caracteres' => ['name', str_repeat('a', 256)],
    'sin valor' => ['value', ''],
    'valor negativo' => ['value', '-5'],
    'sin fecha de inicio' => ['start_date', ''],
    'sin fecha de fin' => ['end_date', ''],
]);

// ─────────────────────────────────────────────────────────────────────────────
// Autenticación y permisos
// ─────────────────────────────────────────────────────────────────────────────

describe('acceso', function () {
    it('redirige al login a un visitante', function () {
        $this->get(route('promotions.index'))->assertRedirect(route('login'));
    });

    it('rechaza (403) a un usuario sin permisos en todas las acciones', function () {
        $this->actingAs(userWith());
        $promo = Promotion::factory()->create();

        $this->get(route('promotions.index'))->assertForbidden();
        $this->get(route('promotions.show', $promo))->assertForbidden();
        $this->get(route('promotions.edit', $promo))->assertForbidden();
        $this->post(route('promotions.store'), promotionPayload())->assertForbidden();
        $this->put(route('promotions.update', $promo), promotionPayload())->assertForbidden();
        $this->delete(route('promotions.destroy', $promo))->assertForbidden();
    });

    it('aplica el permiso específico de cada acción (la policy)', function () {
        $this->actingAs(userWith('ver promotions'));
        $promo = Promotion::factory()->create();

        $this->get(route('promotions.index'))->assertOk();
        $this->get(route('promotions.show', $promo))->assertOk();
        $this->get(route('promotions.edit', $promo))->assertForbidden();
        $this->post(route('promotions.store'), promotionPayload())->assertForbidden();
        $this->put(route('promotions.update', $promo), promotionPayload())->assertForbidden();
        $this->delete(route('promotions.destroy', $promo))->assertForbidden();

        expect(Promotion::where('id', $promo->id)->exists())->toBeTrue();
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// index
// ─────────────────────────────────────────────────────────────────────────────

describe('index', function () {
    beforeEach(fn () => $this->actingAs(userWith('ver promotions')));

    it('lista las promociones y pagina de a 10', function () {
        foreach (range(1, 12) as $i) {
            Promotion::factory()->create(['name' => sprintf('Promo %02d', $i)]);
        }

        $response = $this->get(route('promotions.index'))->assertOk()->assertViewIs('promotions.index');

        $promos = $response->viewData('promotions');

        expect($promos->total())->toBeGreaterThanOrEqual(12)
            ->and($promos->perPage())->toBe(10);
    });

    it('busca por nombre (filter[buscar])', function () {
        Promotion::factory()->create(['name' => 'Black Friday Frenos']);
        Promotion::factory()->create(['name' => 'Liquidación Fin de Año']);

        $names = $this->get(route('promotions.index', ['filter' => ['buscar' => 'Friday']]))
            ->viewData('promotions')->pluck('name')->all();

        expect($names)->toContain('Black Friday Frenos')
            ->and($names)->not->toContain('Liquidación Fin de Año');
    });

    it('ordena por los campos permitidos y rechaza los no permitidos con 400', function () {
        $this->get(route('promotions.index', ['sort' => 'name']))->assertOk();
        $this->get(route('promotions.index', ['sort' => '-start_date']))->assertOk();
        $this->get(route('promotions.index', ['sort' => 'value']))->assertStatus(400);
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// store
// ─────────────────────────────────────────────────────────────────────────────

describe('store', function () {
    beforeEach(fn () => $this->actingAs(userWith('ver promotions', 'crear promotions')));

    it('crea la promoción y asocia los productos seleccionados', function () {
        $p1 = Product::factory()->create();
        $p2 = Product::factory()->create();

        $this->post(route('promotions.store'), promotionPayload([
            'product_ids' => [$p1->id, $p2->id],
        ]))
            ->assertRedirect(route('promotions.index'))
            ->assertSessionHas('success');

        $promo = Promotion::where('name', 'Cyber Week Repuestos')->firstOrFail();

        expect($promo->products->pluck('id')->all())->toBe([$p1->id, $p2->id])
            ->and((float) $promo->value)->toBe(15.00)
            ->and($promo->is_active)->toBeTrue();
    });

    it('valida que la fecha de fin sea igual o posterior a la fecha de inicio', function () {
        $this->from(route('promotions.index'))
            ->post(route('promotions.store'), promotionPayload([
                'start_date' => '2026-08-10',
                'end_date' => '2026-08-05', // Anterior a inicio
            ]))
            ->assertRedirect(route('promotions.index'))
            ->assertSessionHasErrors(['end_date']);
    });

    it('valida los campos requeridos y no crea nada', function (string $field, mixed $invalidValue) {
        $initialCount = Promotion::count();

        $this->from(route('promotions.index'))
            ->post(route('promotions.store'), promotionPayload([$field => $invalidValue]))
            ->assertRedirect(route('promotions.index'))
            ->assertSessionHasErrors($field);

        expect(Promotion::count())->toBe($initialCount);
    })->with('invalid promotion fields');
});

// ─────────────────────────────────────────────────────────────────────────────
// show, edit, update y destroy
// ─────────────────────────────────────────────────────────────────────────────

describe('show, edit, update y destroy', function () {
    it('muestra el detalle y el formulario de edición', function () {
        $this->actingAs(userWith('ver promotions', 'editar promotions'));
        $promo = Promotion::factory()->create();

        $this->get(route('promotions.show', $promo))
            ->assertOk()
            ->assertViewIs('promotions.show')
            ->assertViewHas('promotion', fn ($p) => $p->id === $promo->id);

        $this->get(route('promotions.edit', $promo))
            ->assertOk()
            ->assertViewIs('promotions.edit')
            ->assertViewHas('promotion', fn ($p) => $p->id === $promo->id)
            ->assertViewHas('products');
    });

    it('actualiza los datos y sincroniza los productos asociados', function () {
        $this->actingAs(userWith('ver promotions', 'editar promotions'));
        $p1 = Product::factory()->create();
        $p2 = Product::factory()->create();
        $promo = Promotion::factory()->create();
        $promo->products()->sync([$p1->id]);

        $this->put(route('promotions.update', $promo), promotionPayload([
            'name' => 'Promo Actualizada',
            'product_ids' => [$p2->id],
        ]))
            ->assertRedirect(route('promotions.index'))
            ->assertSessionHas('success');

        $promo->refresh();
        expect($promo->name)->toBe('Promo Actualizada')
            ->and($promo->products->pluck('id')->all())->toBe([$p2->id]);
    });

    it('elimina la promoción exitosamente', function () {
        $this->actingAs(userWith('ver promotions', 'eliminar promotions'));
        $promo = Promotion::factory()->create();

        $this->delete(route('promotions.destroy', $promo))
            ->assertRedirect(route('promotions.index'))
            ->assertSessionHas('success');

        expect(Promotion::where('id', $promo->id)->exists())->toBeFalse();
    });

    it('responde 404 si la promoción no existe', function () {
        $this->actingAs(userWith('ver promotions', 'editar promotions', 'eliminar promotions'));
        $this->get(route('promotions.show', 99999))->assertNotFound();
        $this->get(route('promotions.edit', 99999))->assertNotFound();
        $this->delete(route('promotions.destroy', 99999))->assertNotFound();
    });
});
