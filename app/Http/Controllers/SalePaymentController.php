<?php

namespace App\Http\Controllers;

use App\Support\Bc;
use App\Http\Requests\StoreOrderPaymentRequest;
use App\Models\Cashbox;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\PaymentMethod;
use App\Services\OrderPaymentService;

/**
 * Cobros posteriores de una venta a crédito (abonos) y su anulación.
 */
class SalePaymentController extends Controller
{
    public function store(StoreOrderPaymentRequest $request, Order $sale, OrderPaymentService $service)
    {
        abort_if($sale->isPending(), 404);

        $this->authorize('collect', $sale);

        $data = $request->validated();

        try {
            $service->collect(
                $sale,
                Bc::num($data['amount']),
                PaymentMethod::findOrFail($data['payment_id']),
                Cashbox::findOrFail($data['cashbox_id']),
                $request->user()
            );
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $sale->refresh();

        return redirect()->route('sales.show', $sale)
            ->with('success', $sale->is_paid
                ? 'Cobro registrado: la venta quedó pagada por completo.'
                : 'Cobro registrado. Saldo pendiente: '.$sale->balance_label.'.');
    }

    public function destroy(Order $sale, OrderPayment $payment, OrderPaymentService $service)
    {
        abort_unless($payment->order_id === $sale->id, 404);

        $this->authorize('cancelPayment', $sale);

        try {
            $service->cancelPayment($payment);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('sales.show', $sale)
            ->with('success', 'Cobro anulado: el monto volvió al saldo de la venta y se revirtió en la caja.');
    }
}
