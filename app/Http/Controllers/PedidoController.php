<?php

namespace App\Http\Controllers;

use App\Support\Bc;
use App\Http\Requests\ConfirmPedidoRequest;
use App\Http\Requests\StorePedidoRequest;
use App\Http\Requests\UpdatePedidoRequest;
use App\Models\Cashbox;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\ProductWarehouse;
use App\Services\OrderService;
use App\Services\PosFormData;
use App\Services\SaleService;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Pedidos: órdenes en espera con fecha de entrega. No tocan stock ni caja hasta que se confirman
 * (confirm() los convierte en venta). Usan la misma tabla y modelo que las ventas.
 */
class PedidoController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAnyPedido', Order::class);

        $pedidos = QueryBuilder::for(Order::class)
            ->whereNotNull('delivery_date')
            ->with('customer', 'branch')
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
            ->allowedSorts(['id', 'delivery_date', 'amount'])
            ->defaultSort('delivery_date')
            ->paginate(10)
            ->appends($request->query());

        return view('pedidos.index', compact('pedidos'));
    }

    public function create(PosFormData $data)
    {
        $this->authorize('createPedido', Order::class);

        return view('pedidos.create', $data->build(auth()->user()));
    }

    public function store(StorePedidoRequest $request, OrderService $service)
    {
        $this->authorize('createPedido', Order::class);

        $data = $request->validated();

        try {
            $pedido = $service->create($data, $data['items'], $request->user());
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('pedidos.show', $pedido)
            ->with('success', "Pedido #{$pedido->id} registrado. Entrega: {$pedido->delivery_date->format('d/m/Y')}.");
    }

    public function show(Order $pedido)
    {
        $this->authorizePedido('viewPedido', $pedido);

        $pedido->load('customer', 'user', 'warehouse', 'branch', 'items.product', 'items.productUnit', 'items.warehouse');

        // Cuánto hay hoy de cada línea en el almacén del pedido, para avisar antes de confirmar.
        $availability = $pedido->items->mapWithKeys(function ($item) use ($pedido) {
            $stock = Bc::num(ProductWarehouse::where('product_id', $item->product_id)->where('warehouse_id', $pedido->warehouse_id)->value('stock') ?? 0);

            return [$item->id => (float) Bc::div(Bc::floor(Bc::mul(Bc::div($stock, Bc::max($item->conversion_factor, '0.0001')), 100)), 100)];
        });

        $paymentMethods = PaymentMethod::orderByDesc('is_cash')->orderBy('name')->get();
        $cashboxes = Cashbox::where('branche_id', $pedido->branche_id)->orderBy('name')->get();

        return view('pedidos.show', compact('pedido', 'availability', 'paymentMethods', 'cashboxes'));
    }

    public function edit(Order $pedido, PosFormData $data)
    {
        $this->authorizePedido('updatePedido', $pedido);

        $pedido->load('items');

        return view('pedidos.edit', $data->build(auth()->user()) + [
            'pedido' => $pedido,
            'initialItems' => $pedido->items->map(fn ($i) => [
                'product_unit_id' => $i->product_unit_id,
                'warehouse_id' => $i->warehouse_id,
                'quantity' => (float) $i->quantity,
                'unit_price' => (float) $i->unit_price,
                'discount_type' => 'amount',
                'discount_value' => Bc::gt($i->discount, 0) ? (float) $i->discount : '',
            ])->values()->all(),
        ]);
    }

    public function update(UpdatePedidoRequest $request, Order $pedido, OrderService $service)
    {
        $this->authorizePedido('updatePedido', $pedido);

        $data = $request->validated();

        try {
            $service->update($pedido, $data, $data['items'], $request->user());
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('pedidos.show', $pedido)
            ->with('success', 'Pedido actualizado exitosamente.');
    }

    public function destroy(Order $pedido, OrderService $service)
    {
        $this->authorizePedido('deletePedido', $pedido);

        try {
            $service->delete($pedido);
        } catch (\RuntimeException $e) {
            return redirect()->route('pedidos.index')->with('error', $e->getMessage());
        }

        return redirect()->route('pedidos.index')
            ->with('success', 'Pedido eliminado exitosamente.');
    }

    /**
     * Confirma el pedido: se convierte en venta (baja stock FIFO y cobra en caja).
     */
    public function confirm(ConfirmPedidoRequest $request, Order $pedido, SaleService $service)
    {
        $this->authorizePedido('confirmPedido', $pedido);

        try {
            $service->confirm($pedido, $request->validated(), $request->user());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('sales.show', $pedido)
            ->with('success', $pedido->fresh()->isCredit()
                ? "Pedido #{$pedido->id} confirmado como venta a crédito."
                : "Pedido #{$pedido->id} confirmado: ahora es una venta cobrada.");
    }

    /**
     * Recibo del pedido en PDF (mismo formato que los recibos de venta y consignación).
     */
    public function downloadPdf(Order $pedido)
    {
        $this->authorizePedido('viewPedido', $pedido);

        $pedido->load('customer', 'user', 'warehouse', 'branch', 'items.product', 'items.productUnit', 'items.warehouse');
        $company = \App\Models\Company::first();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pedidos.pdf', compact('pedido', 'company'));
        $pdf->setPaper('letter', 'portrait');

        return $pdf->stream("Pedido-{$pedido->id}.pdf");
    }

    /**
     * Las rutas de pedidos solo aplican a órdenes con fecha de entrega; una venta directa no es un pedido.
     */
    private function authorizePedido(string $ability, Order $pedido): void
    {
        abort_unless($pedido->isPedido(), 404);

        $this->authorize($ability, $pedido);
    }
}
