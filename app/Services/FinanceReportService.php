<?php

namespace App\Services;

use App\Support\Bc;
use App\Models\Expense;
use App\Models\Order;
use App\Models\Purchase;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Finanzas: totales y desgloses de ventas, compras y gastos con filtros comunes.
 *
 * Qué entra en cada total:
 *  - Ventas: órdenes vendidas (no pedidos pendientes ni anuladas), por fecha de la venta.
 *  - Compras: compras recibidas (no anuladas), por fecha de la compra.
 *  - Gastos: gastos pagados (no anulados), por fecha del gasto.
 *  - Ganancia bruta = ventas − costo real (FIFO) de la mercadería vendida.
 *  - Ganancia neta = ganancia bruta − gastos.
 *
 * Cada filtro solo se aplica a lo que tiene sentido: cliente filtra ventas; almacén filtra ventas y compras;
 * producto filtra ventas y compras (por sus renglones); los gastos solo se filtran por sucursal, usuario y fecha.
 * Cuando un filtro no aplica a un total, ese total se devuelve como null (la pantalla muestra "—").
 */
class FinanceReportService
{
    /**
     * Filtros normalizados desde la petición. Rango por defecto: del 1° del mes de hoy hasta hoy.
     *
     * @return array{from: string, to: string, branch: ?int, warehouse: ?int, product: ?int, customer: ?int, user: ?int, type: ?string}
     */
    public static function filtersFrom(Request $request): array
    {
        $date = fn ($v, $default) => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $v) ? $v : $default;
        $id = fn ($k) => $request->filled($k) ? (int) $request->input($k) : null;

        $from = $date($request->input('from'), now()->startOfMonth()->toDateString());
        $to = $date($request->input('to'), now()->toDateString());

        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        return [
            'from' => $from,
            'to' => $to,
            'branch' => $id('branch'),
            'warehouse' => $id('warehouse'),
            'product' => $id('product'),
            'customer' => $id('customer'),
            'user' => $id('user'),
            'type' => in_array($request->input('type'), ['sale', 'purchase', 'expense'], true) ? $request->input('type') : null,
        ];
    }

    public static function purchasesApply(array $f): bool
    {
        return $f['customer'] === null;
    }

    public static function expensesApply(array $f): bool
    {
        return $f['customer'] === null && $f['warehouse'] === null && $f['product'] === null;
    }

    /**
     * Totales del período con los filtros aplicados.
     *
     * @return array{sales: float, sales_count: int, cost: float, gross: float, margin: ?float, purchases: ?float, purchases_count: ?int, expenses: ?float, expenses_count: ?int, net: ?float}
     */
    public function summary(array $f): array
    {
        $sales = $this->salesItems($f)
            ->selectRaw($this->salesAggregates())
            ->selectRaw('COUNT(DISTINCT oi.order_id) as orders')
            ->first();

        $costMov = Bc::num($this->movementCost($f)->sum(DB::raw('smi.base_quantity * COALESCE(smi.unit_cost, 0)')));

        $revenue = Bc::round($sales->revenue ?? 0, 2);
        $cost = Bc::round(Bc::add($sales->cost_items ?? 0, $costMov), 2);
        $gross = Bc::round(Bc::sub($revenue, $cost), 2);

        $purchases = $purchasesCount = null;
        if (self::purchasesApply($f)) {
            if ($f['product']) {
                $row = DB::table('purchase_items as pi')
                    ->joinSub($this->purchases($f), 'pp', 'pp.id', '=', 'pi.purchase_id')
                    ->where('pi.product_id', $f['product'])
                    ->selectRaw('COALESCE(SUM(pi.subtotal), 0) as total, COUNT(DISTINCT pi.purchase_id) as n')->first();
            } else {
                $row = $this->purchases($f)->selectRaw('COALESCE(SUM(p.amount), 0) as total, COUNT(*) as n')->first();
            }
            $purchases = (float) Bc::round($row->total, 2);
            $purchasesCount = (int) $row->n;
        }

        $expenses = $expensesCount = null;
        if (self::expensesApply($f)) {
            $row = $this->expenses($f)->selectRaw('COALESCE(SUM(e.amount), 0) as total, COUNT(*) as n')->first();
            $expenses = (float) Bc::round($row->total, 2);
            $expensesCount = (int) $row->n;
        }

        return [
            'sales' => (float) $revenue,
            'sales_count' => (int) ($sales->orders ?? 0),
            'cost' => (float) $cost,
            'gross' => (float) $gross,
            'margin' => Bc::gt($revenue, 0) ? (float) Bc::round(Bc::mul(Bc::div($gross, $revenue), 100), 1) : null,
            'purchases' => $purchases,
            'purchases_count' => $purchasesCount,
            'expenses' => $expenses,
            'expenses_count' => $expensesCount,
            'net' => $expenses === null ? null : (float) Bc::round(Bc::sub($gross, $expenses), 2),
        ];
    }

    /**
     * Todos los movimientos del período (ventas, compras y gastos) del más reciente al más antiguo.
     */
    public function movements(array $f, int $perPage = 15): LengthAwarePaginator
    {
        $queries = [];

        if (in_array($f['type'], [null, 'sale'], true)) {
            $amount = $f['product']
                ? '(SELECT COALESCE(SUM(oi.subtotal), 0) FROM order_items oi WHERE oi.order_id = o.id AND oi.product_id = '.(int) $f['product'].')'
                : 'o.amount';
            $queries[] = $this->ordersBase($f)
                ->leftJoin('customers as c', 'c.id', '=', 'o.customer_id')
                ->join('branches as b', 'b.id', '=', 'o.branche_id')
                ->join('users as u', 'u.id', '=', 'o.user_id')
                ->when($f['product'], fn ($q, $v) => $q->whereExists(fn ($s) => $s->from('order_items as x')->whereColumn('x.order_id', 'o.id')->where('x.product_id', $v)))
                ->selectRaw("'sale' as type, o.id as id, o.created_at as moment, COALESCE(c.name, 'Cliente general') as detail, b.name as branch, u.name as user_name, {$amount} as amount");
        }

        if (in_array($f['type'], [null, 'purchase'], true) && self::purchasesApply($f)) {
            $amount = $f['product']
                ? '(SELECT COALESCE(SUM(pi.subtotal), 0) FROM purchase_items pi WHERE pi.purchase_id = p.id AND pi.product_id = '.(int) $f['product'].')'
                : 'p.amount';
            $queries[] = $this->purchases($f)
                ->leftJoin('suppliers as s', 's.id', '=', 'p.supplier_id')
                ->join('branches as b', 'b.id', '=', 'p.branche_id')
                ->join('users as u', 'u.id', '=', 'p.user_id')
                ->when($f['product'], fn ($q, $v) => $q->whereExists(fn ($s) => $s->from('purchase_items as x')->whereColumn('x.purchase_id', 'p.id')->where('x.product_id', $v)))
                ->selectRaw("'purchase' as type, p.id as id, p.created_at as moment, COALESCE(s.name, 'Sin proveedor') as detail, b.name as branch, u.name as user_name, {$amount} as amount");
        }

        if (in_array($f['type'], [null, 'expense'], true) && self::expensesApply($f)) {
            $queries[] = $this->expenses($f)
                ->join('branches as b', 'b.id', '=', 'e.branche_id')
                ->join('users as u', 'u.id', '=', 'e.user_id')
                ->selectRaw("'expense' as type, e.id as id, e.created_at as moment, e.concept as detail, b.name as branch, u.name as user_name, e.amount as amount");
        }

        if ($queries === []) {
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, $perPage);
        }

        $union = array_shift($queries);
        foreach ($queries as $q) {
            $union->unionAll($q);
        }

        $page = DB::query()->fromSub($union, 'm')->orderByDesc('moment')->orderByDesc('id')->paginate($perPage);

        $labels = ['sale' => 'Venta', 'purchase' => 'Compra', 'expense' => 'Gasto'];
        $routes = ['sale' => 'sales.show', 'purchase' => 'purchases.show', 'expense' => 'expenses.show'];

        $page->getCollection()->transform(function ($row) use ($labels, $routes) {
            $row->type_label = $labels[$row->type];
            $row->number = '#'.$row->id;
            $row->date_label = \Illuminate\Support\Carbon::parse($row->moment)->format('d/m/Y H:i');
            $row->amount_label = ($row->type === 'sale' ? '+ ' : '− ').number_format((float) $row->amount, 2);
            $row->url = route($routes[$row->type], $row->id);

            return $row;
        });

        return $page;
    }

    /**
     * Ventas por usuario (quién vendió más), con su costo y ganancia.
     */
    public function salesByUser(array $f, int $perPage = 10): LengthAwarePaginator
    {
        $sales = $this->salesItems($f)->selectRaw('so.user_id as grp, '.$this->salesAggregates().', COUNT(DISTINCT oi.order_id) as orders')->groupBy('so.user_id');
        $cost = $this->movementCost($f)->selectRaw('so.user_id as grp, SUM(smi.base_quantity * COALESCE(smi.unit_cost, 0)) as cost_mov')->groupBy('so.user_id');

        return DB::table('users as u')
            ->joinSub($sales, 'r', 'r.grp', '=', 'u.id')
            ->leftJoinSub($cost, 'c', 'c.grp', '=', 'u.id')
            ->selectRaw('u.id, u.name, r.orders, r.revenue, (COALESCE(r.cost_items, 0) + COALESCE(c.cost_mov, 0)) as cost')
            ->orderByDesc('r.revenue')
            ->paginate($perPage, ['*'], 'usuarios_page');
    }

    /**
     * Ganancia por producto: lo vendido (ingreso, costo FIFO, ganancia) frente a lo comprado en el mismo período.
     * Incluye productos que solo tuvieron compras.
     */
    public function productProfit(array $f, string $order = 'ganancia', int $perPage = 15): LengthAwarePaginator
    {
        $sales = $this->salesItems($f)->selectRaw('oi.product_id as grp, '.$this->salesAggregates())->groupBy('oi.product_id');
        $cost = $this->movementCost($f)->selectRaw('COALESCE(smi.kit_product_id, smi.product_id) as grp, SUM(smi.base_quantity * COALESCE(smi.unit_cost, 0)) as cost_mov')->groupBy(DB::raw('COALESCE(smi.kit_product_id, smi.product_id)'));

        $query = DB::table('products as pr')
            ->leftJoinSub($sales, 'r', 'r.grp', '=', 'pr.id')
            ->leftJoinSub($cost, 'c', 'c.grp', '=', 'pr.id');

        $costExpr = '(COALESCE(r.cost_items, 0) + COALESCE(c.cost_mov, 0))';

        if (self::purchasesApply($f)) {
            $purchases = DB::table('purchase_items as pi')
                ->joinSub($this->purchases($f), 'pp', 'pp.id', '=', 'pi.purchase_id')
                ->when($f['product'], fn ($q, $v) => $q->where('pi.product_id', $v))
                ->selectRaw('pi.product_id as grp, SUM(pi.quantity * pi.conversion_factor) as qty, SUM(pi.subtotal) as amount')
                ->groupBy('pi.product_id');

            $query->leftJoinSub($purchases, 'pu', 'pu.grp', '=', 'pr.id')
                ->where(fn ($q) => $q->whereNotNull('r.grp')->orWhereNotNull('pu.grp'))
                ->selectRaw('COALESCE(pu.qty, 0) as bought_qty, COALESCE(pu.amount, 0) as bought_amount');
        } else {
            $query->whereNotNull('r.grp')->selectRaw('0 as bought_qty, 0 as bought_amount');
        }

        $orderBy = match ($order) {
            'ventas' => 'COALESCE(r.revenue, 0)',
            'cantidad' => 'COALESCE(r.qty, 0)',
            'compras' => 'COALESCE(pu.amount, 0)',
            default => '(COALESCE(r.revenue, 0) - '.$costExpr.')',
        };

        if ($order === 'compras' && ! self::purchasesApply($f)) {
            $orderBy = '(COALESCE(r.revenue, 0) - '.$costExpr.')';
        }

        return $query
            ->selectRaw("pr.id, pr.name, pr.sku, COALESCE(r.qty, 0) as sold_qty, COALESCE(r.revenue, 0) as revenue, {$costExpr} as cost")
            ->orderByRaw("{$orderBy} DESC")
            ->orderBy('pr.name')
            ->paginate($perPage);
    }

    // ── Bloques de consulta ─────────────────────────────────────────────

    private function salesAggregates(): string
    {
        return 'COALESCE(SUM(oi.subtotal), 0) as revenue, COALESCE(SUM(oi.quantity * oi.conversion_factor), 0) as qty, '
            .'COALESCE(SUM(CASE WHEN oi.unit_cost IS NOT NULL THEN oi.quantity * oi.conversion_factor * oi.unit_cost ELSE 0 END), 0) as cost_items';
    }

    /** Órdenes vendidas que cumplen los filtros (base con alias "o"). */
    private function ordersBase(array $f): Builder
    {
        return DB::table('orders as o')
            ->where('o.status', Order::STATUS_COMPLETED)
            ->whereBetween('o.date', [$f['from'], $f['to']])
            ->when($f['branch'], fn ($q, $v) => $q->where('o.branche_id', $v))
            ->when($f['warehouse'], fn ($q, $v) => $q->where('o.warehouse_id', $v))
            ->when($f['customer'], fn ($q, $v) => $q->where('o.customer_id', $v))
            ->when($f['user'], fn ($q, $v) => $q->where('o.user_id', $v));
    }

    /** Renglones de venta de esas órdenes (alias "oi") con el usuario de la orden disponible como "so.user_id". */
    private function salesItems(array $f): Builder
    {
        return DB::table('order_items as oi')
            ->joinSub($this->ordersBase($f)->select('o.id', 'o.user_id'), 'so', 'so.id', '=', 'oi.order_id')
            ->when($f['product'], fn ($q, $v) => $q->where('oi.product_id', $v));
    }

    /**
     * Costo real (FIFO) de lo vendido: lo que consumió cada salida de stock de esas ventas. Las ventas que
     * vienen de consignación no tienen salida propia; su costo está en sus renglones (ver salesAggregates).
     */
    private function movementCost(array $f): Builder
    {
        return DB::table('stock_movement_items as smi')
            ->join('stock_movements as sm', 'sm.id', '=', 'smi.stock_movement_id')
            ->joinSub($this->ordersBase($f)->select('o.id', 'o.user_id'), 'so', 'so.id', '=', 'sm.order_id')
            // El costo de las partes de un kit se atribuye al kit vendido (es el producto que tiene el ingreso).
            ->when($f['product'], fn ($q, $v) => $q->whereRaw('COALESCE(smi.kit_product_id, smi.product_id) = ?', [$v]));
    }

    /** Compras recibidas que cumplen los filtros (alias "p"; solo id y usuario para usarse como subconsulta). */
    private function purchases(array $f): Builder
    {
        return DB::table('purchases as p')
            ->where('p.status', Purchase::STATUS_RECEIVED)
            ->whereBetween('p.date', [$f['from'], $f['to']])
            ->when($f['branch'], fn ($q, $v) => $q->where('p.branche_id', $v))
            ->when($f['warehouse'], fn ($q, $v) => $q->where('p.warehouse_id', $v))
            ->when($f['user'], fn ($q, $v) => $q->where('p.user_id', $v));
    }

    /** Gastos pagados que cumplen los filtros (alias "e"). */
    private function expenses(array $f): Builder
    {
        return DB::table('expenses as e')
            ->where('e.status', Expense::STATUS_PAID)
            ->whereBetween('e.date', [$f['from'], $f['to']])
            ->when($f['branch'], fn ($q, $v) => $q->where('e.branche_id', $v))
            ->when($f['user'], fn ($q, $v) => $q->where('e.user_id', $v));
    }
}
