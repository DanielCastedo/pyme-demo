<?php

namespace App\View\Components;

use App\Models\ProductWarehouse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * Campana de notificaciones del encabezado: avisa de los productos que llegaron a su stock mínimo (o lo pasaron)
 * en algún almacén. Solo la ven quienes pueden consultar el stock por almacén.
 *
 * Un producto entra en la alerta cuando su stock es menor o igual al mínimo configurado y ese mínimo es mayor a 0
 * (con mínimo 0 se entiende que no se controla). El resultado se guarda 1 minuto para no consultar en cada pantalla.
 */
class LowStockNotifications extends Component
{
    public const LIMIT = 8;

    public bool $visible;

    public int $total = 0;

    /** @var Collection<int, object> */
    public Collection $items;

    public function __construct()
    {
        $user = auth()->user();
        $this->visible = $user !== null && $user->can('viewAny', ProductWarehouse::class);
        $this->items = collect();

        if (! $this->visible) {
            return;
        }

        // La campana nunca debe tumbar una pantalla: si la consulta falla, simplemente no se muestra.
        try {
            ['total' => $this->total, 'items' => $items] = Cache::remember('low-stock-alerts', 60, fn () => $this->load());
            $this->items = collect($items);
        } catch (\Throwable $e) {
            report($e);
            $this->visible = false;
        }
    }

    /**
     * @return array{total: int, items: array<int, object>}
     */
    private function load(): array
    {
        $query = DB::table('product_warehouses as pw')
            ->join('products as p', 'p.id', '=', 'pw.product_id')
            ->join('warehouses as w', 'w.id', '=', 'pw.warehouse_id')
            ->where('p.is_active', true)
            ->where('w.is_active', true)
            ->where('pw.min_stock', '>', 0)
            ->whereColumn('pw.stock', '<=', 'pw.min_stock');

        return [
            'total' => (clone $query)->count(),
            // Lo más urgente primero: sin stock, y luego el que más por debajo del mínimo está
            'items' => $query->orderByRaw('(CAST(pw.stock AS SIGNED) - CAST(pw.min_stock AS SIGNED)) asc')->orderBy('p.name')->limit(self::LIMIT)
                ->get(['pw.product_id', 'pw.warehouse_id', 'pw.stock', 'pw.min_stock', 'p.name as product', 'w.name as warehouse'])
                ->all(),
        ];
    }

    public function render(): View
    {
        return view('components.low-stock-notifications');
    }
}
