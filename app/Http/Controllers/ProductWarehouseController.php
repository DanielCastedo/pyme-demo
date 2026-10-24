<?php

namespace App\Http\Controllers;

use App\Support\Bc;
use App\Http\Requests\StoreProductWarehouseRequest;
use App\Http\Requests\UpdateProductWarehouseRequest;
use App\Models\Product;
use App\Models\ProductWarehouse;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ProductWarehouseController extends Controller
{
    /**
     * product_warehouses no tiene "id" propio (clave compuesta product_id + warehouse_id),
     * así que no se puede usar authorizeResource()/Route::resource() con binding implícito.
     * Cada acción autoriza a mano.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', ProductWarehouse::class);

        $productWarehouses = QueryBuilder::for(ProductWarehouse::class)
            ->with('product', 'warehouse')
            ->allowedFilters([
                AllowedFilter::callback('buscar', function ($query, $value) {
                    $query->where(function ($q) use ($value) {
                        $q->whereHas('product', fn ($p) => $p->where('name', 'like', "%{$value}%")->orWhere('sku', 'like', "%{$value}%"))
                            ->orWhereHas('warehouse', fn ($w) => $w->where('name', 'like', "%{$value}%"));
                    });
                }),
                // Solo los que llegaron a su stock mínimo (lo usa la campana de notificaciones)
                AllowedFilter::callback('bajoMinimo', function ($query, $value) {
                    if (filter_var($value, FILTER_VALIDATE_BOOLEAN)) {
                        $query->where('min_stock', '>', 0)->whereColumn('stock', '<=', 'min_stock');
                    }
                }),
                AllowedFilter::exact('currentProduct', 'product_id'),
                AllowedFilter::exact('currentWarehouse', 'warehouse_id'),
            ])
            ->allowedSorts(['stock', 'min_stock', 'avg_cost'])
            ->defaultSort('product_id')
            ->paginate(10)
            ->appends($request->query());

        $products = DB::table('products')->orderBy('name')->get(['id', 'name']);
        $warehouses = Warehouse::orderBy('name')->get();

        return view('product-warehouses.index', compact('productWarehouses', 'products', 'warehouses'));
    }

    public function store(StoreProductWarehouseRequest $request)
    {
        $this->authorize('create', ProductWarehouse::class);

        $data = $request->validated();

        DB::transaction(function () use ($data) {
            DB::table('product_warehouses')->insert([
                'product_id' => $data['product_id'],
                'warehouse_id' => $data['warehouse_id'],
                'stock' => 0,
                'min_stock' => $data['min_stock'] ?? 0,
                'avg_cost' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // El stock inicial entra como un lote de apertura con su propio costo.
            if (Bc::gt($data['stock'] ?? 0, 0)) {
                ProductWarehouse::receiveStock(
                    (int) $data['product_id'],
                    (int) $data['warehouse_id'],
                    Bc::num($data['stock']),
                    Bc::num($data['avg_cost'] ?? 0)
                );
            }
        });

        return redirect()->route('product-warehouses.index')
            ->with('success', 'Registro de stock creado exitosamente.');
    }

    public function show(string $combo)
    {
        $productWarehouse = $this->findOrFail($combo);

        $this->authorize('view', $productWarehouse);

        $batches = $productWarehouse->availableBatches();

        return view('product-warehouses.show', compact('productWarehouse', 'batches'));
    }

    public function edit(string $combo)
    {
        $productWarehouse = $this->findOrFail($combo);

        $this->authorize('update', $productWarehouse);

        return view('product-warehouses.edit', compact('productWarehouse'));
    }

    public function update(UpdateProductWarehouseRequest $request, string $combo)
    {
        $productWarehouse = $this->findOrFail($combo);

        $this->authorize('update', $productWarehouse);

        DB::table('product_warehouses')
            ->where('product_id', $productWarehouse->product_id)
            ->where('warehouse_id', $productWarehouse->warehouse_id)
            ->update(['min_stock' => $request->validated()['min_stock'], 'updated_at' => now()]);

        return redirect()->route('product-warehouses.index')
            ->with('success', 'Stock actualizado exitosamente.');
    }

    public function destroy(string $combo)
    {
        $productWarehouse = $this->findOrFail($combo);

        $this->authorize('delete', $productWarehouse);

        if ($productWarehouse->stock > 0) {
            return redirect()->route('product-warehouses.index')
                ->with('error', 'No se puede eliminar: aún hay stock en lotes. Sácalo con un movimiento de salida primero.');
        }

        DB::table('product_warehouses')
            ->where('product_id', $productWarehouse->product_id)
            ->where('warehouse_id', $productWarehouse->warehouse_id)
            ->delete();

        return redirect()->route('product-warehouses.index')
            ->with('success', 'Registro de stock eliminado exitosamente.');
    }

    /**
     * Resuelve el identificador combinado "{product_id}-{warehouse_id}" (ver
     * ProductWarehouse::getCompositeKeyAttribute()) a la fila real.
     */
    private function findOrFail(string $combo): ProductWarehouse
    {
        $parts = explode('-', $combo);

        abort_if(count($parts) !== 2, 404);

        return ProductWarehouse::with('product', 'warehouse')
            ->where('product_id', (int) $parts[0])
            ->where('warehouse_id', (int) $parts[1])
            ->firstOrFail();
    }
}
