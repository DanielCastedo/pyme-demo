<?php

namespace App\Services;

use App\Support\Bc;
use App\Models\Branch;
use App\Models\Cashbox;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Models\Warehouse;

/**
 * Datos que necesita la pantalla de armado de líneas (parcial sales.partials.pos), compartida por
 * Ventas, Pedidos y Consignaciones: sucursales, almacenes, cajas, clientes y los permisos del
 * usuario para editar precio y dar descuentos.
 *
 * El catálogo de productos NO se entrega acá: con miles (o millones) de productos, mandarlo entero
 * al navegador en cada apertura de la pantalla la vuelve lentísima o la cuelga directamente. En vez
 * de eso, el buscador de la pantalla pide productos a medida que se escribe, contra searchUnits()
 * (ver PosSearchController), que siempre devuelve como máximo 15 a la vez.
 */
class PosFormData
{
    /** Cuántos resultados como máximo devuelve una búsqueda de texto o de código. */
    public const SEARCH_LIMIT = 15;

    /**
     * @return array<string, mixed>
     */
    public function build(User $user): array
    {
        $branches = Branch::where('is_active', true)->orderBy('name')->get(['id', 'name', 'payment_qr_image']);

        return [
            'branches' => $branches,
            'warehouses' => Warehouse::activeForForms(),
            'cashboxes' => Cashbox::orderBy('name')->get(['id', 'name', 'branche_id']),
            'paymentMethods' => PaymentMethod::orderByDesc('is_cash')->orderBy('name')->get(['id', 'name', 'is_cash']),
            'defaultBranchId' => $user->branche_id ?? $branches->first()?->id,
            'canEditPrice' => $user->can('ventas.editar-precio'),
            'canDiscount' => $user->can('ventas.descuento'),
            'canCredit' => $user->can('ventas.credito'),
            'canCreateCustomer' => $user->can('create', Customer::class),
        ];
    }

    /**
     * Busca clientes para el buscador del POS (Ventas/Pedidos/Consignaciones/Proforma). Igual que
     * searchUnits(): 'q' devuelve como máximo SEARCH_LIMIT, 'ids' devuelve puntualmente esos (para
     * restaurar el cliente ya elegido tras un error de validación, sin pedir el listado entero).
     *
     * @param  array{q?: ?string, ids?: array<int, int>}  $filters
     * @return array<int, array<string, mixed>>
     */
    public function searchCustomers(array $filters): array
    {
        $query = DB::table('customers')->where('is_active', true);

        if (! empty($filters['ids'])) {
            $query->whereIn('id', $filters['ids']);
        } elseif (! empty($filters['q'])) {
            $term = '%'.trim((string) $filters['q']).'%';
            $query->where(fn ($q) => $q->where('name', 'like', $term)->orWhere('document', 'like', $term));
        }
        // Sin 'q' ni 'ids': lista los primeros del catálogo (lo que se ve al enfocar el buscador vacío).

        $rows = $query->orderBy('name')
            ->limit(! empty($filters['ids']) ? 500 : self::SEARCH_LIMIT)
            ->get(['id', 'name', 'document', 'document_type', 'credit_limit', 'phone', 'email', 'address']);

        if ($rows->isEmpty()) {
            return [];
        }

        // Deuda actual por ventas a crédito vigentes (para validar el límite al vender), solo de los
        // clientes encontrados, no de la cartera entera.
        $ids = $rows->pluck('id');
        $owed = Order::query()
            ->where('payment_type', Order::PAYMENT_CREDIT)
            ->where('status', Order::STATUS_COMPLETED)
            ->whereIn('customer_id', $ids)
            ->groupBy('customer_id')
            ->selectRaw('customer_id, SUM(amount - paid_amount) as owed')
            ->pluck('owed', 'customer_id');

        return $rows->map(fn ($c) => [
            'id' => $c->id,
            'name' => $c->name,
            'document' => $c->document,
            'document_type' => $c->document_type,
            'credit_limit' => $c->credit_limit === null ? null : (float) $c->credit_limit,
            'owed' => (float) Bc::round($owed[$c->id] ?? 0, 2),
            // Solo los usa Proforma (para autocompletar el formulario del cliente); Ventas los ignora.
            'phone' => $c->phone,
            'email' => $c->email,
            'address' => $c->address,
        ])->values()->all();
    }

    /**
     * Busca presentaciones de producto (product_units) para el buscador del POS. Se usa una sola de
     * estas tres formas por llamada:
     *  - 'q': texto libre (nombre, SKU, código de barras, marca, color, medida) → hasta SEARCH_LIMIT.
     *  - 'code': código de barras o SKU exacto (lector de código / tecleado) → como máximo 1.
     *  - 'ids': IDs puntuales (para redibujar líneas ya elegidas: reintento tras un error, o edición).
     *
     * @param  array{q?: ?string, code?: ?string, ids?: array<int, int>}  $filters
     * @param  bool  $includeCost  Solo quien tiene "ventas.editar-precio" debe recibir el costo: sin el
     *                             permiso, no debe llegar al navegador ni siquiera en la respuesta cruda.
     * @return array<int, array<string, mixed>>
     */
    public function searchUnits(array $filters, bool $includeCost = false): array
    {
        $query = DB::table('product_units as pu')
            ->join('products as p', 'p.id', '=', 'pu.product_id')
            ->where('p.is_active', true);

        if (! empty($filters['ids'])) {
            $query->whereIn('pu.id', $filters['ids']);
        } elseif (! empty($filters['code'])) {
            $code = trim((string) $filters['code']);
            $query->where(fn ($q) => $q->where('pu.barcode', $code)->orWhere('p.sku', $code));
        } elseif (! empty($filters['q'])) {
            $term = '%'.trim((string) $filters['q']).'%';
            $query->where(fn ($q) => $q
                ->where('p.name', 'like', $term)
                ->orWhere('p.sku', 'like', $term)
                ->orWhere('pu.barcode', 'like', $term)
                ->orWhere('p.marca', 'like', $term)
                ->orWhere('p.color', 'like', $term)
                ->orWhere('p.medida', 'like', $term));
        }
        // Sin 'q', 'code' ni 'ids': lista los primeros del catálogo (lo que se ve al enfocar el buscador vacío).

        $rows = $query
            ->orderBy('pu.product_id')
            ->orderByDesc('pu.is_base')
            ->limit(! empty($filters['ids']) ? 500 : self::SEARCH_LIMIT)
            ->get(['pu.id', 'pu.product_id', 'p.name', 'pu.name as unit', 'p.sku', 'pu.barcode', 'pu.price', 'pu.factor', 'p.photo', 'p.marca', 'p.color', 'p.medida']);

        if ($rows->isEmpty()) {
            return [];
        }

        $productIds = $rows->pluck('product_id')->unique()->values()->all();

        // Presentaciones hermanas (para el desglose "4 Caja x24 + 4 Unidad"): solo de los productos
        // encontrados, no de todo el catálogo.
        $siblingsByProduct = DB::table('product_units as pu')
            ->whereIn('pu.product_id', $productIds)
            ->orderByDesc('pu.factor')
            ->get(['pu.id', 'pu.product_id', 'pu.name as unit', 'pu.factor'])
            ->groupBy('product_id');

        [$stocks, $costs] = $this->stocksAndCostsFor($productIds);

        return $rows->map(function ($u) use ($stocks, $costs, $siblingsByProduct, $includeCost) {
            return [
                'id' => $u->id,
                'product_id' => $u->product_id,
                'name' => $u->name,
                'unit' => $u->unit,
                'sku' => $u->sku,
                'barcode' => $u->barcode,
                'price' => (float) $u->price,
                'factor' => (float) $u->factor,
                'photo_url' => $u->photo ? \Illuminate\Support\Facades\Storage::disk('uploads')->url($u->photo) : null,
                'marca' => $u->marca,
                'color' => $u->color,
                'medida' => $u->medida,
                'stock_by_warehouse' => $stocks[$u->product_id] ?? (object) [],
                'cost_by_warehouse' => $includeCost ? ($costs[$u->product_id] ?? (object) []) : (object) [],
                'siblings' => ($siblingsByProduct[$u->product_id] ?? collect())
                    ->map(fn ($s) => ['id' => $s->id, 'unit' => $s->unit, 'factor' => (float) $s->factor])
                    ->values(),
            ];
        })->values()->all();
    }

    /**
     * Stock y costo promedio (por unidad base, por almacén) de un conjunto de productos.
     * Si alguno es un kit, también carga sus componentes (aunque no se hayan pedido) para poder
     * calcular cuánto "alcanza" el kit: lo mismo que hacía antes PosFormData::build() para todo el
     * catálogo, pero acá solo para los productos pedidos.
     *
     * @param  array<int, int>  $productIds
     * @return array{0: array<int, array<int, string>>, 1: array<int, array<int, float>>} [stocks, costs] por product_id => warehouse_id => valor
     */
    public function stocksAndCostsFor(array $productIds): array
    {
        $productIds = array_values(array_unique($productIds));
        $stocks = [];
        $costs = [];

        $loadDirect = function (array $ids) use (&$stocks, &$costs) {
            if ($ids === []) {
                return;
            }
            foreach (DB::table('product_warehouses')->whereIn('product_id', $ids)
                ->select('product_id', 'warehouse_id', 'stock', 'avg_cost')->cursor() as $row) {
                $stocks[$row->product_id][$row->warehouse_id] = $row->stock;
                if (Bc::gt($row->avg_cost, 0)) {
                    $costs[$row->product_id][$row->warehouse_id] = (float) $row->avg_cost;
                }
            }
        };

        $loadDirect($productIds);

        // Un kit no guarda stock propio: en cada almacén alcanza para el menor de sus partes y cuesta la suma de las partes.
        $components = DB::table('product_components')->whereIn('product_id', $productIds)
            ->get(['product_id', 'component_product_id', 'quantity'])
            ->groupBy('product_id');

        if ($components->isNotEmpty()) {
            $loadDirect($components->flatten(1)->pluck('component_product_id')->unique()->values()->all());

            foreach ($components as $kitId => $parts) {
                unset($stocks[$kitId], $costs[$kitId]);

                foreach ($parts->flatMap(fn ($c) => array_keys($stocks[$c->component_product_id] ?? []))->unique() as $warehouseId) {
                    $fits = null;
                    $cost = '0';

                    foreach ($parts as $part) {
                        $required = Bc::gt($part->quantity, 0) ? $part->quantity : 1;
                        $partFits = Bc::floor(Bc::div($stocks[$part->component_product_id][$warehouseId] ?? 0, $required));
                        $fits = $fits === null ? $partFits : Bc::min($fits, $partFits);
                        $cost = Bc::add($cost, Bc::mul($costs[$part->component_product_id][$warehouseId] ?? 0, $required));
                    }

                    $stocks[$kitId][$warehouseId] = Bc::int($fits);

                    if (Bc::gt($cost, 0)) {
                        $costs[$kitId][$warehouseId] = (float) $cost;
                    }
                }
            }
        }

        return [$stocks, $costs];
    }
}
