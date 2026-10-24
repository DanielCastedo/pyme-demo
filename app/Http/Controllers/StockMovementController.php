<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStockMovementRequest;
use App\Http\Requests\UpdateStockMovementRequest;
use App\Models\StockMovement;
use App\Models\Type;
use App\Models\Warehouse;
use App\Services\StockMovementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class StockMovementController extends Controller
{
    /** Cuántos resultados como máximo devuelve una búsqueda de texto o de código en search(). */
    public const SEARCH_LIMIT = 15;

    public function __construct()
    {
        $this->authorizeResource(StockMovement::class, 'stock_movement');
    }

    public function index(Request $request)
    {
        $stockMovements = QueryBuilder::for(StockMovement::class)
            ->with('type', 'warehouse', 'user', 'items')
            ->allowedFilters([
                AllowedFilter::callback('buscar', function ($query, $value) {
                    $query->where(function ($q) use ($value) {
                        $q->where('note', 'like', "%{$value}%")
                            ->orWhereHas('warehouse', fn ($w) => $w->where('name', 'like', "%{$value}%"))
                            ->orWhereHas('type', fn ($t) => $t->where('name', 'like', "%{$value}%"));
                    });
                }),
                AllowedFilter::exact('currentType', 'type_id'),
                AllowedFilter::exact('currentWarehouse', 'warehouse_id'),
            ])
            ->allowedSorts(['id', 'date'])
            ->defaultSort('-date')
            ->paginate(10)
            ->appends($request->query());

        $warehouses = Warehouse::orderBy('name')->get(['id', 'name']);

        return view('stock-movements.index', compact('stockMovements', 'warehouses'));
    }

    public function create()
    {
        // Solo Entrada/Salida de stock: los demás tipos (Venta, Compra, Gastos...) son
        // movimientos de dinero (Transaction), no movimientos físicos de almacén.
        $types = Type::whereIn('code', ['ENT', 'SAL'])->orderBy('name')->get();
        $warehouses = Warehouse::orderBy('name')->get();

        return view('stock-movements.create', compact('types', 'warehouses'));
    }

    /**
     * Busca presentaciones de producto para el buscador de "Nuevo Movimiento de Stock" (como máximo
     * SEARCH_LIMIT a la vez): el catálogo entero NO se manda al navegador, así no se cuelga la
     * pantalla cuando el catálogo crece (ver PosSearchController, que resuelve lo mismo en Ventas).
     */
    public function search(Request $request)
    {
        $validated = $request->validate([
            'q' => 'nullable|string|max:255',
        ]);

        $term = '%'.trim((string) ($validated['q'] ?? '')).'%';

        $rows = DB::table('product_units as pu')
            ->join('products as p', 'p.id', '=', 'pu.product_id')
            ->where('p.is_active', true)
            ->where(fn ($q) => $q
                ->where('p.name', 'like', $term)
                ->orWhere('pu.name', 'like', $term)
                ->orWhere('p.sku', 'like', $term)
                ->orWhere('pu.barcode', 'like', $term))
            ->orderBy('p.name')
            ->limit(self::SEARCH_LIMIT)
            ->get(['pu.id', 'pu.name', 'pu.factor', 'p.name as product_name']);

        return response()->json(['units' => $rows->map(fn ($u) => [
            'id' => $u->id,
            'label' => $u->product_name.' — '.$u->name,
            'factor' => (int) $u->factor,
        ])->values()]);
    }

    public function store(StoreStockMovementRequest $request, StockMovementService $service)
    {
        $data = $request->validated();

        try {
            $service->register(
                Type::findOrFail($data['type_id']),
                (int) $data['warehouse_id'],
                $data['date'],
                $data['note'] ?? null,
                auth()->id(),
                $data['items']
            );
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('stock-movements.index')
            ->with('success', 'Movimiento registrado exitosamente.');
    }

    public function show(StockMovement $stockMovement)
    {
        $stockMovement->load('type', 'warehouse', 'user', 'items.product', 'items.productUnit');

        return view('stock-movements.show', compact('stockMovement'));
    }

    public function edit(StockMovement $stockMovement)
    {
        return view('stock-movements.edit', compact('stockMovement'));
    }

    public function update(UpdateStockMovementRequest $request, StockMovement $stockMovement)
    {
        $stockMovement->update($request->validated());

        return redirect()->route('stock-movements.index')
            ->with('success', 'Movimiento actualizado exitosamente.');
    }

    /**
     * Elimina el movimiento revirtiendo sus lotes (ver StockMovementService::revert()).
     */
    public function destroy(StockMovement $stockMovement, StockMovementService $service)
    {
        try {
            $service->revert($stockMovement);
        } catch (\RuntimeException $e) {
            return redirect()->route('stock-movements.index')->with('error', $e->getMessage());
        }

        return redirect()->route('stock-movements.index')
            ->with('success', 'Movimiento eliminado y stock revertido exitosamente.');
    }
}
