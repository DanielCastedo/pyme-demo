<?php

namespace App\Http\Controllers;

use App\Support\Bc;
use App\Http\Requests\StorePurchasePaymentRequest;
use App\Models\Cashbox;
use App\Models\PaymentMethod;
use App\Models\Purchase;
use App\Models\PurchasePayment;
use App\Services\PurchasePaymentService;

/**
 * Pagos posteriores de una compra a crédito (abonos) y su anulación.
 */
class PurchasePaymentController extends Controller
{
    public function store(StorePurchasePaymentRequest $request, Purchase $purchase, PurchasePaymentService $service)
    {
        $this->authorize('pay', $purchase);

        $data = $request->validated();

        try {
            $service->pay(
                $purchase,
                Bc::num($data['amount']),
                PaymentMethod::findOrFail($data['payment_id']),
                Cashbox::findOrFail($data['cashbox_id']),
                $request->user()
            );
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $purchase->refresh();

        return redirect()->route('purchases.show', $purchase)
            ->with('success', $purchase->is_paid
                ? 'Pago registrado: la compra quedó pagada por completo.'
                : 'Pago registrado. Saldo pendiente: '.$purchase->balance_label.'.');
    }

    public function destroy(Purchase $purchase, PurchasePayment $payment, PurchasePaymentService $service)
    {
        abort_unless($payment->purchase_id === $purchase->id, 404);

        $this->authorize('cancelPayment', $purchase);

        try {
            $service->cancelPayment($payment);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('purchases.show', $purchase)
            ->with('success', 'Pago anulado: el monto volvió al saldo de la compra y se revirtió en la caja.');
    }
}
