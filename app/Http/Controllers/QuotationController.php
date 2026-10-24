<?php

namespace App\Http\Controllers;

use App\Support\Bc;
use App\Http\Requests\StoreQuotationRequest;
use App\Http\Requests\UpdateQuotationRequest;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Quotation;
use App\Models\Warehouse;
use App\Services\OrderLinePricer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class QuotationController extends Controller
{
    public function __construct(private readonly OrderLinePricer $pricer)
    {
        $this->authorizeResource(Quotation::class, 'quotation');
    }

    public function index(Request $request)
    {
        $quotations = QueryBuilder::for(Quotation::class)
            ->with(['user', 'customer', 'items.product', 'items.productUnit'])
            ->allowedFilters([
                AllowedFilter::callback('buscar', function ($query, $value) {
                    $query->where(function ($q) use ($value) {
                        $q->where('number', 'like', "%{$value}%")
                            ->orWhere('notes', 'like', "%{$value}%")
                            ->orWhereHas('customer', function ($cq) use ($value) {
                                $cq->where('name', 'like', "%{$value}%")
                                    ->orWhere('document', 'like', "%{$value}%");
                            });
                    });
                }),
                AllowedFilter::exact('status'),
            ])
            ->allowedSorts(['id', 'number', 'date', 'expires_at', 'amount'])
            ->defaultSort('-date', '-id')
            ->paginate(10)
            ->appends($request->query());

        return view('quotations.index', compact('quotations'));
    }

    public function create()
    {
        $user = auth()->user();
        $branch = $user->branch;
        $warehouse = $branch?->warehouses()->first() ?? Warehouse::first();

        $lastQuotation = Quotation::latest('id')->first();
        $nextSeq = $lastQuotation ? ((int) preg_replace('/[^0-9]/', '', $lastQuotation->number) + 1) : 1;
        $nextNumber = 'COT-' . str_pad($nextSeq, 4, '0', STR_PAD_LEFT);

        return view('quotations.create', compact('warehouse', 'nextNumber'));
    }

    public function store(StoreQuotationRequest $request)
    {
        $data = $request->validated();

        $user = auth()->user();
        $branch = $user->branch;
        $warehouse = $branch?->warehouses()->first() ?? Warehouse::first();

        // La proforma es solo una cotización (stock referencial, no reserva ni descuenta nada): no se
        // bloquea por cantidad mayor al stock. Recién al confirmarla como venta real se valida el stock.
        try {
            $lines = $this->priceLines($data['items'], $user, $warehouse);
        } catch (\RuntimeException $e) {
            return back()->withInput()->withErrors(['items' => $e->getMessage()]);
        }

        $quotation = DB::transaction(function () use ($data, $lines) {
            // Cliente único del sistema (ver Customer::syncFromProforma): reutiliza o actualiza sin duplicar documentos.
            $customer = Customer::syncFromProforma($data);

            $lastQuotation = Quotation::where('number', 'REGEXP', '^COT-[0-9]+$')
                ->latest('id')
                ->lockForUpdate()
                ->first();

            $nextSeq = 1;
            if ($lastQuotation && preg_match('/COT-(\d+)/', $lastQuotation->number, $matches)) {
                $nextSeq = ((int) $matches[1]) + 1;
            }

            while (Quotation::where('number', 'COT-' . str_pad($nextSeq, 4, '0', STR_PAD_LEFT))->exists()) {
                $nextSeq++;
            }

            $number = 'COT-' . str_pad($nextSeq, 4, '0', STR_PAD_LEFT);

            $quotation = Quotation::create([
                'number' => $number,
                'customer_id' => $customer->id,
                'date' => $data['date'],
                'expires_at' => $data['expires_at'] ?? now()->addDays(15)->toDateString(),
                'status' => 'pending',
                'amount' => 0,
                'notes' => $data['notes'] ?? null,
                'user_id' => auth()->id(),
            ]);

            $quotation->update(['amount' => $this->storeItems($quotation, $lines)]);

            return $quotation;
        });

        return redirect()->route('quotations.show', $quotation)
            ->with('success', "Proforma {$quotation->number} registrada exitosamente.");
    }

    public function show(Quotation $quotation)
    {
        $quotation->load(['user', 'customer', 'items.product', 'items.productUnit']);
        $company = Company::first();

        return view('quotations.show', compact('quotation', 'company'));
    }

    public function edit(Quotation $quotation)
    {
        if ($quotation->status === 'approved') {
            return redirect()->route('quotations.show', $quotation)
                ->with('error', 'Esta proforma ya fue aprobada y no puede modificarse ya que se utiliza para generar ventas.');
        }

        if ($quotation->status === 'rejected') {
            return redirect()->route('quotations.show', $quotation)
                ->with('error', 'Esta proforma ha sido rechazada y no puede modificarse.');
        }

        $this->authorize('update', $quotation);

        $quotation->load(['customer', 'items.product', 'items.productUnit']);

        $user = auth()->user();
        $branch = $user->branch;
        $warehouse = $branch?->warehouses()->first() ?? Warehouse::first();

        $initialItems = $quotation->items->map(fn ($item) => [
            'product_unit_id' => $item->product_unit_id,
            'quantity' => (float) $item->quantity,
            'unit_price' => (float) $item->unit_price,
        ])->values();

        return view('quotations.edit', compact('quotation', 'warehouse', 'initialItems'));
    }

    public function update(UpdateQuotationRequest $request, Quotation $quotation)
    {
        if ($quotation->status === 'approved') {
            return redirect()->route('quotations.show', $quotation)
                ->with('error', 'Esta proforma ya fue aprobada y no puede modificarse ya que se utiliza para generar ventas.');
        }

        if ($quotation->status === 'rejected') {
            return redirect()->route('quotations.show', $quotation)
                ->with('error', 'Esta proforma ha sido rechazada y no puede modificarse.');
        }

        $this->authorize('update', $quotation);

        $data = $request->validated();

        $user = auth()->user();
        $branch = $user->branch;
        $warehouse = $branch?->warehouses()->first() ?? Warehouse::first();

        // La proforma es solo una cotización (stock referencial, no reserva ni descuenta nada): no se
        // bloquea por cantidad mayor al stock. Recién al confirmarla como venta real se valida el stock.
        try {
            $lines = $this->priceLines($data['items'], $user, $warehouse);
        } catch (\RuntimeException $e) {
            return back()->withInput()->withErrors(['items' => $e->getMessage()]);
        }

        DB::transaction(function () use ($data, $quotation, $lines) {
            // Cliente único del sistema (ver Customer::syncFromProforma): reutiliza o actualiza sin duplicar documentos.
            $customer = Customer::syncFromProforma($data);

            $quotation->update([
                'customer_id' => $customer->id,
                'date' => $data['date'],
                'expires_at' => $data['expires_at'] ?? $quotation->expires_at,
                'status' => $data['status'] ?? $quotation->status,
                'notes' => $data['notes'] ?? null,
            ]);

            $quotation->items()->delete();

            $quotation->update(['amount' => $this->storeItems($quotation, $lines)]);
        });

        return redirect()->route('quotations.show', $quotation)
            ->with('success', "Proforma {$quotation->number} actualizada exitosamente.");
    }

    public function destroy(Quotation $quotation)
    {
        if ($quotation->status === 'approved') {
            return redirect()->route('quotations.show', $quotation)
                ->with('error', 'No se puede eliminar una proforma que ha sido aprobada.');
        }

        $this->authorize('delete', $quotation);

        $number = $quotation->number;
        $quotation->delete();

        return redirect()->route('quotations.index')
            ->with('success', "Proforma {$number} eliminada exitosamente.");
    }

    public function downloadPdf(Quotation $quotation)
    {
        $this->authorize('view', $quotation);

        $quotation->load(['user.branch', 'customer', 'items.product', 'items.productUnit']);
        $company = Company::first();

        $pdf = Pdf::loadView('quotations.pdf', compact('quotation', 'company'));
        $pdf->setPaper('letter', 'portrait');

        return $pdf->stream("Proforma-{$quotation->number}.pdf");
    }

    public function changeStatus(Request $request, Quotation $quotation)
    {
        $this->authorize('changeStatus', $quotation);

        $request->validate([
            'status' => 'required|in:pending,approved,rejected,expired',
        ]);

        if ($quotation->status === 'approved' && $request->status !== 'approved') {
            return back()->with('error', 'Una proforma aprobada no puede cambiar de estado.');
        }

        $quotation->update(['status' => $request->status]);

        return back()->with('success', "El estado de la proforma se actualizó a '{$quotation->status_label}'.");
    }

    /**
     * Precio de cada línea. Quien tiene el permiso "ventas.editar-precio" puede cotizar a otro precio (igual que en una
     * venta: no puede quedar por debajo del costo del producto); para el resto se usa siempre el precio de catálogo,
     * sin importar lo que envíe el navegador.
     *
     * @param  array<int, array{product_unit_id: int, quantity: int|string, unit_price?: float|string|null}>  $items
     * @return array<int, array{unit: ProductUnit, quantity: string, price: string, discount: string, subtotal: string}>
     *
     * @throws \RuntimeException si el precio es menor al costo o falta un permiso.
     */
    private function priceLines(array $items, $user, ?Warehouse $warehouse): array
    {
        $canEditPrice = $user->can('ventas.editar-precio');

        return array_map(function ($item) use ($canEditPrice, $user, $warehouse) {
            $line = ['product_unit_id' => $item['product_unit_id'], 'quantity' => $item['quantity']];

            if ($canEditPrice) {
                $line['unit_price'] = $item['unit_price'] ?? null;
            }

            return $this->pricer->price($line, $user, $warehouse?->id);
        }, $items);
    }

    /**
     * Crea las líneas de la proforma con los precios ya calculados y devuelve el total.
     *
     * @param  array<int, array{unit: ProductUnit, quantity: string, price: string, discount: string, subtotal: string}>  $lines
     */
    private function storeItems(Quotation $quotation, array $lines): string
    {
        $total = '0';

        foreach ($lines as $line) {
            $quotation->items()->create([
                'product_id' => $line['unit']->product_id,
                'product_unit_id' => $line['unit']->id,
                'conversion_factor' => $line['unit']->factor,
                'quantity' => (int) $line['quantity'],
                'unit_price' => $line['price'],
                'subtotal' => $line['subtotal'],
            ]);

            $total = Bc::add($total, $line['subtotal']);
        }

        return Bc::round($total, 2);
    }
}
