<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => 'required|numeric|min:0.01',
            'payment_id' => 'required|exists:payments,id',
            'cashbox_id' => 'required|exists:cashboxes,id',
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'El monto del cobro es obligatorio.',
            'amount.min' => 'El monto del cobro debe ser mayor a 0.',
            'payment_id.required' => 'Selecciona el método de pago.',
            'cashbox_id.required' => 'Selecciona la caja donde se cobra.',
        ];
    }
}
