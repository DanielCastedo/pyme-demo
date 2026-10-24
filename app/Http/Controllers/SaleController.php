<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSaleRequest;
use App\Models\Branch;
use App\Models\Cashbox;
use App\Models\Customer;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Services\PosFormData;
use App\Services\SaleService;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Ventas: cada venta es una orden ya cobrada (ver App\Services\SaleService).
 */
class SaleController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Order::class, 'sale');
    }

    public function index(Request $request)
    {
        $f = [
            'from' => $request->filled('from') ? $request->input('from') : null,
            'to' => $request->filled('to') ? $request->input('to') : null,
            'customer' => $request->filled('customer') ? (int) $request->input('customer') : null,
            'user' => $request->filled('user') ? (int) $request->input('user') : null,
            'branch' => $request->filled('branch') ? (int) $request->input('branch') : null,
        ];

        $sales = QueryBuilder::for(Order::class)
            ->where('status', '!=', Order::STATUS_PENDING)
            ->with('customer', 'branch', 'user', 'payments.transaction.paymentMethod')
            ->when($f['from'], fn ($q, $from) => $q->whereDate('date', '>=', $from))
            ->when($f['to'], fn ($q, $to) => $q->whereDate('date', '<=', $to))
            ->when($f['customer'], fn ($q, $customerId) => $q->where('customer_id', $customerId))
            ->when($f['user'], fn ($q, $userId) => $q->where('user_id', $userId))
            ->when($f['branch'], fn ($q, $branchId) => $q->where('branche_id', $branchId))
            ->allowedFilters([
                AllowedFilter::exact('currentStatus', 'status'),
            ])
            ->allowedSorts(['id', 'date', 'amount'])
            ->defaultSort('-id')
            ->paginate(10)
            ->appends($request->query());

        return view('sales.index', [
            'sales' => $sales,
            'f' => $f,
            'customers' => Customer::orderBy('name')->pluck('name', 'id'),
            'users' => User::orderBy('name')->pluck('name', 'id'),
            'branches' => Branch::orderBy('name')->pluck('name', 'id'),
        ]);
    }

    /**
     * Pantalla de venta rápida: todo lo necesario se entrega ya cargado para que el usuario
     * solo busque productos y cobre (sucursal, almacén y caja vienen preseleccionados).
     */
    public function create(PosFormData $data)
    {
        return view('sales.create', $data->build(auth()->user()));
    }
    public function store(StoreSaleRequest $request, SaleService $service, \App\Services\ImageOptimizer $images)
    {
        $data = $request->validated();

        if ($request->hasFile('receipt_photo')) {
            $data['receipt_photo'] = $images->store($request->file('receipt_photo'), 'receipts', 'receipt', field: 'receipt_photo');
        }

        try {
            $order = $service->register($data, $data['items'], $request->user());
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('sales.show', $order)
            ->with('success', $order->isCredit()
                ? "Venta #{$order->id} a crédito por {$order->amount_label}. Saldo pendiente: {$order->balance_label}."
                : "Venta #{$order->id} registrada por {$order->amount_label}.");
    }

    public function show(Order $sale)
    {
        abort_if($sale->isPending(), 404);

        $sale->load('customer', 'user', 'warehouse', 'branch', 'items.product', 'items.productUnit', 'items.warehouse', 'payments.transaction.paymentMethod', 'payments.transaction.cashbox', 'payments.user', 'stockMovements.items', 'consignmentSettlement');

        // Cada forma de pago tiene su propia pantalla: contado (solo consulta) y crédito (saldo, abonos y vencimiento).
        if (! $sale->isCredit()) {
            return view('sales.show-cash', ['order' => $sale]);
        }

        return view('sales.show-credit', [
            'order' => $sale,
            'paymentMethods' => PaymentMethod::orderByDesc('is_cash')->orderBy('name')->get(),
            'cashboxes' => Cashbox::where('branche_id', $sale->branche_id)->orderBy('name')->get(),
        ]);
    }

    public function cancel(Order $sale, SaleService $service)
    {
        abort_if($sale->isPending(), 404);

        $this->authorize('cancel', $sale);

        try {
            $service->cancel($sale);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('sales.show', $sale)
            ->with('success', 'Venta anulada: el stock volvió a sus lotes y los cobros se revirtieron en la caja.');
    }

    public function downloadPdf(Order $sale)
    {
        abort_if($sale->isPending(), 404);
        $this->authorize('view', $sale);

        $sale->load('customer', 'user', 'warehouse', 'branch', 'items.product', 'items.productUnit', 'items.warehouse', 'payments.transaction.paymentMethod', 'payments.transaction.cashbox');
        $company = \App\Models\Company::first();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('sales.pdf', compact('sale', 'company'));
        $pdf->setPaper('letter', 'portrait');

        return $pdf->stream("Venta-{$sale->id}.pdf");
    }
}
