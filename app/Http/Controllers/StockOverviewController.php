<?php

namespace App\Http\Controllers;

use App\Models\ProductWarehouse;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Stock por sucursal y almacén: vista de consulta que junta todo el inventario (dónde está cada producto, cuánto hay
 * y cuánto vale a costo real), con totales por sucursal y filtros. Solo lectura; el detalle de cada registro está en
 * "Stock por Almacén".
 */
class StockOverviewController extends Controller
{
    private const STATUSES = [
        'con_stock' => 'Con stock',
        'sin_stock' => 'Sin stock',
        'bajo_minimo' => 'En stock mínimo o menos',
    ];

    public function index(Request $request)
    {
        $this->authorize('viewAny', ProductWarehouse::class);

        $f = [
            'branch' => $request->filled('branch') ? (int) $request->input('branch') : null,
            'warehouse' => $request->filled('warehouse') ? (int) $request->input('warehouse') : null,
            'product' => $request->filled('product') ? (int) $request->input('product') : null,
            'category' => $request->filled('category') ? (int) $request->input('category') : null,
            'status' => array_key_exists((string) $request->input('status'), self::STATUSES) ? $request->input('status') : null,
        ];

        // Detalle: una fila por producto y almacén, paginada
        $rows = $this->base($f)
            ->orderBy('b.name')->orderBy('w.name')->orderBy('p.name')
            ->select([
                'pw.product_id', 'pw.warehouse_id', 'pw.stock', 'pw.min_stock', 'pw.avg_cost',
                'p.name as product', 'p.sku', 'c.name as category', 'w.name as warehouse', 'b.name as branch',
                DB::raw('COALESCE(bv.value, 0) as value'),
            ])
            ->paginate(15)
            ->withQueryString();

        $rows->getCollection()->transform(function ($r) {
            $stock = (int) $r->stock;
            $min = (int) $r->min_stock;

            return (object) [
                'branch' => $r->branch,
                'warehouse' => $r->warehouse,
                'product' => $r->product,
                'sku' => $r->sku,
                'category' => $r->category ?? '—',
                'stock' => $stock,
                'min_stock' => $min,
                'avg_cost_label' => number_format((float) $r->avg_cost, 2),
                'value_label' => number_format((float) $r->value, 2),
                'status_label' => $stock <= 0 ? 'Sin stock' : ($min > 0 && $stock <= $min ? 'En stock mínimo' : 'Normal'),
                'composite_key' => $r->product_id.'-'.$r->warehouse_id,
            ];
        });

        // Resumen por sucursal (sobre todo lo filtrado, no solo la página)
        $byBranch = $this->base($f)
            ->groupBy('b.id', 'b.name')->orderBy('b.name')
            ->selectRaw('b.name as branch, COUNT(DISTINCT pw.warehouse_id) as warehouses, COUNT(DISTINCT pw.product_id) as products, COALESCE(SUM(pw.stock), 0) as units, COALESCE(SUM(bv.value), 0) as value')
            ->get();

        $totals = $this->base($f)
            ->selectRaw('COUNT(DISTINCT pw.product_id) as products, COALESCE(SUM(pw.stock), 0) as units, COALESCE(SUM(bv.value), 0) as value')
            ->first();

        $alerts = $this->base($f)->where('pw.min_stock', '>', 0)->whereColumn('pw.stock', '<=', 'pw.min_stock')->count();

        return view('stock-overview.index', [
            'rows' => $rows,
            'byBranch' => $byBranch,
            'totals' => $totals,
            'alerts' => $alerts,
            'f' => $f,
            'statuses' => self::STATUSES,
            'branches' => DB::table('branches')->orderBy('name')->pluck('name', 'id'),
            'warehouses' => DB::table('warehouses as w')->join('branches as b', 'b.id', '=', 'w.branche_id')
                ->orderBy('b.name')->orderBy('w.name')->get(['w.id', 'w.name', 'b.name as branch'])
                ->mapWithKeys(fn ($w) => [$w->id => "{$w->name} · {$w->branch}"]),
            'products' => DB::table('products')->orderBy('name')->pluck('name', 'id'),
            'categories' => DB::table('categories')->orderBy('name')->pluck('name', 'id'),
        ]);
    }

    /**
     * Stock por producto y almacén (con su sucursal y el valor a costo real de sus lotes), ya filtrado.
     */
    private function base(array $f): Builder
    {
        $batchValue = DB::table('stock_batches')
            ->where('qty_remaining', '>', 0)
            ->selectRaw('product_id, warehouse_id, SUM(qty_remaining * unit_cost) as value')
            ->groupBy('product_id', 'warehouse_id');

        return DB::table('product_warehouses as pw')
            ->join('products as p', 'p.id', '=', 'pw.product_id')
            ->join('warehouses as w', 'w.id', '=', 'pw.warehouse_id')
            ->join('branches as b', 'b.id', '=', 'w.branche_id')
            ->leftJoin('categories as c', 'c.id', '=', 'p.category_id')
            ->leftJoinSub($batchValue, 'bv', fn ($join) => $join->on('bv.product_id', '=', 'pw.product_id')->on('bv.warehouse_id', '=', 'pw.warehouse_id'))
            ->when($f['branch'], fn ($q, $v) => $q->where('w.branche_id', $v))
            ->when($f['warehouse'], fn ($q, $v) => $q->where('pw.warehouse_id', $v))
            ->when($f['product'], fn ($q, $v) => $q->where('pw.product_id', $v))
            ->when($f['category'], fn ($q, $v) => $q->where('p.category_id', $v))
            ->when($f['status'] === 'con_stock', fn ($q) => $q->where('pw.stock', '>', 0))
            ->when($f['status'] === 'sin_stock', fn ($q) => $q->where('pw.stock', '<=', 0))
            ->when($f['status'] === 'bajo_minimo', fn ($q) => $q->where('pw.min_stock', '>', 0)->whereColumn('pw.stock', '<=', 'pw.min_stock'));
    }
}
