<?php

namespace App\Http\Controllers;

use App\Support\Bc;
use App\Http\Requests\StorePurchaseRequest;
use App\Http\Requests\UpdatePurchaseRequest;
use App\Models\Cashbox;
use App\Models\Category;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\StockBatch;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Services\PurchaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Compras de mercadería: entran al inventario por lotes FIFO y pueden pagarse desde una caja
 * (ver App\Services\PurchaseService).
 */
class PurchaseController extends Controller
{
    /** Cuántos resultados como máximo devuelve una búsqueda de texto o de código en search(). */
    public const SEARCH_LIMIT = 15;

    public function __construct()
    {
        $this->authorizeResource(Purchase::class, 'purchase');
    }

    public function index(Request $request)
    {
        $purchases = QueryBuilder::for(Purchase::class)
            ->with('warehouse', 'supplier', 'receipts')
            ->allowedFilters([
                AllowedFilter::callback('buscar', function ($query, $value) {
                    $query->where(function ($q) use ($value) {
                        $q->where('id', ltrim($value, '#'))
                            ->orWhereHas('supplier', fn ($s) => $s->where('name', 'like', "%{$value}%"))
                            ->orWhere('invoice_number', 'like', "%{$value}%");
                    });
                }),
                AllowedFilter::exact('currentStatus', 'status'),
            ])
            ->allowedSorts(['id', 'date', 'amount'])
            ->defaultSort('-id')
            ->paginate(10)
            ->appends($request->query());

        return view('purchases.index', compact('purchases'));
    }

    public function create()
    {
        return view('purchases.create', $this->formData());
    }

    /**
     * "Editar" una compra: lo ya comprado no se modifica, solo se pueden AGREGAR productos (ver PurchaseService::addItems).
     * Usa el mismo formulario que una compra nueva, con los productos existentes bloqueados.
     */
    public function edit(Purchase $purchase)
    {
        $purchase->load('supplier', 'items.product', 'items.productUnit', 'items.distributions.warehouse', 'receipts', 'warehouse', 'transaction');

        return view('purchases.create', $this->formData() + [
            'purchase' => $purchase,
            'defaultWarehouseId' => $purchase->warehouse_id,
            // Si la compra se pagó desde una caja, lo que se agrega sugiere pagarse igual (se puede desmarcar)
            'defaultPayNow' => $purchase->transaction !== null,
            'defaultPaymentId' => $purchase->transaction?->payment_id,
            'defaultCashboxId' => $purchase->transaction?->cashbox_id,
            'canCreateSupplier' => false,
        ]);
    }

    public function update(UpdatePurchaseRequest $request, Purchase $purchase, PurchaseService $service)
    {
        $data = $request->validated();

        try {
            $purchase = $service->addItems($purchase, $data, $data['items'], $request->user());
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('purchases.show', $purchase)
            ->with('success', "Productos agregados a la compra #{$purchase->id}: el total ahora es {$purchase->amount_label} y la mercadería ya está en el inventario.");
    }

    /**
     * Busca presentaciones de producto para el buscador de "Registrar compra" (como máximo
     * SEARCH_LIMIT a la vez, o hasta 500 si se pide por ids puntuales): el catálogo entero NO se
     * manda al navegador, así no se cuelga la pantalla cuando el catálogo crece (ver PosSearchController,
     * que resuelve lo mismo para Ventas/Pedidos/Consignaciones/Proforma).
     */
    public function search(Request $request)
    {
        $validated = $request->validate([
            'q' => 'nullable|string|max:255',
            'code' => 'nullable|string|max:255',
            'ids' => 'nullable|string|max:2000',
        ]);

        $ids = collect(explode(',', (string) ($validated['ids'] ?? '')))
            ->map(fn ($id) => (int) trim($id))
            ->filter()
            ->values()
            ->all();

        $query = DB::table('product_units as pu')
            ->join('products as p', 'p.id', '=', 'pu.product_id')
            ->where('p.is_active', true);

        if ($ids !== []) {
            $query->whereIn('pu.id', $ids);
        } elseif (! empty($validated['code'])) {
            $code = trim((string) $validated['code']);
            $query->where(fn ($q) => $q->where('pu.barcode', $code)->orWhere('p.sku', $code));
        } elseif (! empty($validated['q'])) {
            $term = '%'.trim((string) $validated['q']).'%';
            $query->where(fn ($q) => $q
                ->where('p.name', 'like', $term)
                ->orWhere('p.sku', 'like', $term)
                ->orWhere('pu.barcode', 'like', $term));
        }

        $rows = $query
            ->orderBy('p.name')->orderByDesc('pu.is_base')
            ->limit($ids !== [] ? 500 : self::SEARCH_LIMIT)
            ->get(['pu.id', 'pu.product_id', 'pu.name as unit', 'pu.barcode', 'pu.factor', 'p.name', 'p.sku', 'p.photo']);

        if ($rows->isEmpty()) {
            return response()->json(['units' => []]);
        }

        $productIds = $rows->pluck('product_id')->unique()->values();

        // Último costo pagado por unidad base de cada producto (el lote más reciente), para sugerirlo
        // al comprar (editable) — solo de los productos encontrados, no de todo el historial.
        $lastCost = StockBatch::query()
            ->whereIn('product_id', $productIds)
            ->whereIn('id', fn ($q) => $q->from('stock_batches')->selectRaw('MAX(id)')->whereIn('product_id', $productIds)->groupBy('product_id'))
            ->pluck('unit_cost', 'product_id');

        return response()->json(['units' => $rows->map(fn ($u) => [
            'id' => $u->id,
            'name' => $u->name,
            'unit' => $u->unit,
            'sku' => $u->sku,
            'barcode' => $u->barcode,
            'factor' => (float) $u->factor,
            'cost' => (float) Bc::round(Bc::mul($lastCost[$u->product_id] ?? 0, $u->factor), 2),
            'photo_url' => $u->photo ? Storage::disk('uploads')->url($u->photo) : null,
        ])->values()]);
    }

    /** Datos del formulario de compra (almacenes, cajas, métodos de pago y proveedores). */
    private function formData(): array
    {
        $user = auth()->user();

        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get(['id', 'name', 'branche_id']);

        return [
            'warehouses' => $warehouses,
            'cashboxes' => Cashbox::orderBy('name')->get(['id', 'name', 'branche_id']),
            'paymentMethods' => PaymentMethod::orderByDesc('is_cash')->orderBy('name')->get(['id', 'name', 'is_cash']),
            'suppliers' => Supplier::where('is_active', true)->orderBy('name')->get(['id', 'name', 'document', 'document_type']),
            'canCreateSupplier' => $user->can('create', Supplier::class),
            'canCredit' => $user->can('compras.credito'),
            'selectedSupplierId' => request('supplier_id'),
            'defaultWarehouseId' => $warehouses->firstWhere('branche_id', $user->branche_id)?->id ?? $warehouses->first()?->id,
            'canCreateProduct' => $user->can('create', Product::class),
            'canCreateCategory' => $user->can('create', Category::class),
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'measureUnits' => Unit::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ];
    }

    public function store(StorePurchaseRequest $request, PurchaseService $service)
    {
        $data = $request->validated();

        try {
            $purchase = $service->register($data, $data['items'], $request->user());
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('purchases.show', $purchase)
            ->with('success', "Compra #{$purchase->id} registrada por {$purchase->amount_label}: la mercadería ya está en el inventario.");
    }

    public function show(Purchase $purchase)
    {
        $purchase->load('supplier', 'items.product', 'items.productUnit', 'items.distributions.warehouse', 'receipts', 'warehouse', 'branch', 'user', 'transaction.cashbox', 'transaction.paymentMethod', 'extraTransactions.cashbox', 'extraTransactions.paymentMethod', 'payments.transaction.cashbox', 'payments.transaction.paymentMethod', 'payments.user');

        return view('purchases.show', [
            'purchase' => $purchase,
            'paymentMethods' => PaymentMethod::orderByDesc('is_cash')->orderBy('name')->get(),
            'cashboxes' => Cashbox::where('branche_id', $purchase->branche_id)->orderBy('name')->get(),
        ]);
    }

    /**
     * Anula la compra: la mercadería sale del inventario y el dinero vuelve a la caja.
     */
    public function cancel(Purchase $purchase, PurchaseService $service)
    {
        $this->authorize('cancel', $purchase);

        try {
            $service->cancel($purchase);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('purchases.show', $purchase)
            ->with('success', 'Compra anulada: el stock bajó y el dinero volvió a la caja.');
    }

    public function downloadPdf(Purchase $purchase)
    {
        $this->authorize('view', $purchase);

        $purchase->load('supplier', 'items.product', 'items.productUnit', 'items.distributions.warehouse', 'receipts', 'warehouse', 'branch', 'user', 'transaction.cashbox', 'transaction.paymentMethod', 'extraTransactions.cashbox', 'extraTransactions.paymentMethod');
        $company = \App\Models\Company::first();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('purchases.pdf', compact('purchase', 'company'));
        $pdf->setPaper('letter', 'portrait');

        return $pdf->stream("Compra-{$purchase->id}.pdf");
    }
}
