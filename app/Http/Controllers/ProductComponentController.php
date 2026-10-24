<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Http\Requests\StoreProductComponentRequest;
use App\Http\Requests\UpdateProductComponentRequest;
use App\Models\Product;
use App\Models\ProductComponent;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ProductComponentController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(ProductComponent::class, 'product_component');
    }

    public function index(Request $request)
    {
        $productComponents = QueryBuilder::for(ProductComponent::class)
            ->with('product', 'component')
            ->allowedFilters([
                AllowedFilter::callback('buscar', function ($query, $value) {
                    $query->where(function ($q) use ($value) {
                        $q->whereHas('product', fn ($p) => $p->where('name', 'like', "%{$value}%"))
                            ->orWhereHas('component', fn ($c) => $c->where('name', 'like', "%{$value}%"));
                    });
                }),
                AllowedFilter::exact('currentProduct', 'product_id'),
            ])
            ->allowedSorts(['id', 'quantity'])
            ->defaultSort('product_id')
            ->paginate(10)
            ->appends($request->query());

        $products = DB::table('products')->orderBy('name')->get(['id', 'name', 'sku']);

        // Para el modal de varios componentes: qué productos ya son kit, cuáles ya son parte de un kit y qué lleva cada kit hoy.
        $links = DB::table('product_components as pc')
            ->join('products as c', 'c.id', '=', 'pc.component_product_id')
            ->get(['pc.product_id', 'pc.component_product_id', 'pc.quantity', 'c.name']);

        // Casteados a int explícitamente: DB::table() (a diferencia de Eloquent) no tipa sus resultados,
        // y en producción MySQL/PDO puede devolverlos como texto ("5" en vez de 5) mientras que $products
        // (más abajo) si castea sus ids — comparar "5" === 5 en JS da false y desactiva en silencio la
        // validación de "un kit no puede ser parte de otro kit" (nunca se nota en local, donde sí coinciden).
        $kitIds = $links->pluck('product_id')->map(fn ($id) => (int) $id)->unique()->values();
        $partIds = $links->pluck('component_product_id')->map(fn ($id) => (int) $id)->unique()->values();
        $existing = $links->groupBy('product_id')->map(fn ($group) => $group->map(fn ($l) => [
            'id' => (int) $l->component_product_id,
            'name' => $l->name,
            'qty' => (int) $l->quantity,
        ])->values());

        return view('product-components.index', compact('productComponents', 'products', 'kitIds', 'partIds', 'existing'));
    }

    public function store(StoreProductComponentRequest $request)
    {
        $data = $request->validated();

        if (! $request->isBulk()) {
            ProductComponent::create($data);

            return redirect()->route('product-components.index')
                ->with('success', 'Componente agregado exitosamente.');
        }

        // Varios componentes a la vez: se guardan todos o ninguno.
        DB::transaction(function () use ($data) {
            foreach ($data['components'] as $component) {
                ProductComponent::create([
                    'product_id' => $data['product_id'],
                    'component_product_id' => $component['component_product_id'],
                    'quantity' => $component['quantity'],
                ]);
            }
        });

        $count = count($data['components']);

        return redirect()->route('product-components.index')
            ->with('success', $count === 1 ? 'Componente agregado exitosamente.' : "{$count} componentes agregados al kit.");
    }

    public function show(ProductComponent $productComponent)
    {
        $productComponent->load('product', 'component');

        return view('product-components.show', compact('productComponent'));
    }

    public function edit(ProductComponent $productComponent)
    {
        $products = DB::table('products')->orderBy('name')->get(['id', 'name']);

        return view('product-components.edit', compact('productComponent', 'products'));
    }

    public function update(UpdateProductComponentRequest $request, ProductComponent $productComponent)
    {
        $productComponent->update($request->validated());

        return redirect()->route('product-components.index')
            ->with('success', 'Componente actualizado exitosamente.');
    }

    public function destroy(ProductComponent $productComponent)
    {
        $productComponent->delete();

        return redirect()->route('product-components.index')
            ->with('success', 'Componente eliminado exitosamente.');
    }
}
