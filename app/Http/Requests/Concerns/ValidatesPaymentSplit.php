<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Validation\Rule;

/**
 * Cómo se cobra: un método ("payment_id") o pago mixto ("payments": varios métodos con su monto, ej. Bs 20 por QR +
 * Bs 30 en efectivo). Que las partes sumen exactamente lo que se cobra lo valida OrderPaymentService::parts().
 */
trait ValidatesPaymentSplit
{
    /**
     * @param  bool  $methodRequired  si hay que cobrar algo (el método es obligatorio salvo que venga el pago mixto)
     * @return array<string, mixed>
     */
    protected function paymentSplitRules(bool $methodRequired): array
    {
        return [
            'payment_id' => ['nullable', 'exists:payments,id', Rule::requiredIf(fn () => $methodRequired && empty($this->input('payments')))],
            'payments' => 'nullable|array|max:5',
            'payments.*.payment_id' => 'required|integer|exists:payments,id',
            'payments.*.amount' => 'required|numeric|min:0',
        ];
    }

    /** @return array<string, string> */
    protected function paymentSplitMessages(): array
    {
        return [
            'payment_id.required' => 'Selecciona el método de pago.',
            'payments.max' => 'El pago mixto admite hasta 5 métodos.',
            'payments.*.payment_id.required' => 'Elige el método de pago de cada parte del pago mixto.',
            'payments.*.amount.required' => 'Indica el monto de cada parte del pago mixto.',
            'payments.*.amount.min' => 'El monto de cada parte del pago mixto no puede ser negativo.',
        ];
    }
}
