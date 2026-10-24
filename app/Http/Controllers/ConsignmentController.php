<?php

namespace App\Http\Controllers;

use App\Http\Requests\SettleConsignmentRequest;
use App\Http\Requests\StoreConsignmentRequest;
use App\Models\Cashbox;
use App\Models\Consignment;
use App\Models\PaymentMethod;
use App\Services\ConsignmentService;
use App\Services\PosFormData;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Consignaciones de salida: mercadería que se entrega a un cliente sin cobrar; después lo vendido pasa a ser una
 * venta y lo devuelto vuelve al stock (ver App\Services\ConsignmentService).
 */
class ConsignmentController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Consignment::class, 'consignment');
    }

    public function index(Request $request)
    {
        $consignments = QueryBuilder::for(Consignment::class)
            ->with('customer', 'items')
            ->allowedFilters([
                AllowedFilter::callback('buscar', function ($query, $value) {
                    $query->where(function ($q) use ($value) {
                        $q->where('id', ltrim($value, '#'))
                            ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$value}%")
                                ->orWhere('document', 'like', "%{$value}%"));
                    });
                }),
                AllowedFilter::exact('currentStatus', 'status'),
            ])
            ->allowedSorts(['id', 'date', 'due_date'])
            ->defaultSort('-id')
            ->paginate(10)
            ->appends($request->query());

        return view('consignments.index', compact('consignments'));
    }

    public function create(PosFormData $data)
    {
        return view('consignments.create', $data->build(auth()->user()));
    }

    public function store(StoreConsignmentRequest $request, ConsignmentService $service)
    {
        $data = $request->validated();

        try {
            $consignment = $service->create($data, $data['items'], $request->user());
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('consignments.show', $consignment)
            ->with('success', "Consignación #{$consignment->id} registrada: la mercadería salió del almacén.");
    }

    public function show(Consignment $consignment)
    {
        $consignment->load(
            'customer', 'user', 'warehouse', 'branch',
            'items.product', 'items.productUnit', 'items.warehouse',
            'settlements.user', 'settlements.order', 'settlements.items.consignmentItem.product'
        );

        return view('consignments.show', [
            'consignment' => $consignment,
            'paymentMethods' => PaymentMethod::orderByDesc('is_cash')->orderBy('name')->get(),
            'cashboxes' => Cashbox::where('branche_id', $consignment->branche_id)->orderBy('name')->get(),
        ]);
    }

    /**
     * Liquida la consignación: lo vendido se factura como venta y lo devuelto vuelve al stock.
     */
    public function settle(SettleConsignmentRequest $request, Consignment $consignment, ConsignmentService $service)
    {
        $this->authorize('settle', $consignment);

        $data = $request->validated();

        try {
            $settlement = $service->settle($consignment, $data['lines'], $data, $request->user());
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $consignment->refresh();

        $parts = [];
        if ($settlement->order_id) {
            $parts[] = "venta #{$settlement->order_id} por ".number_format((float) $settlement->sold_amount, 2);
        }
        if ($settlement->return_movement_id) {
            $parts[] = 'devolución al stock registrada';
        }

        return redirect()->route('consignments.show', $consignment)
            ->with('success', 'Liquidación registrada: '.implode(' y ', $parts).($consignment->isSettled() ? '. La consignación quedó liquidada por completo.' : '.'));
    }

    /**
     * Anula una consignación sin liquidaciones: la mercadería vuelve a los lotes de donde salió.
     */
    public function cancel(Consignment $consignment, ConsignmentService $service)
    {
        $this->authorize('cancel', $consignment);

        try {
            $service->cancel($consignment);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('consignments.show', $consignment)
            ->with('success', 'Consignación anulada: la mercadería volvió al stock.');
    }

    public function downloadPdf(Consignment $consignment)
    {
        $this->authorize('view', $consignment);
        $consignment->load('customer', 'warehouse', 'branch', 'user', 'items.product', 'items.productUnit', 'items.warehouse');
        $company = \App\Models\Company::first();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('consignments.pdf', compact('consignment', 'company'));
        $pdf->setPaper('letter', 'portrait');

        return $pdf->stream("Consignacion-{$consignment->id}.pdf");
    }
}
