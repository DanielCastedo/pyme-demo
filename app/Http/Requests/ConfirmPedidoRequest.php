<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmPedidoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_type' => 'nullable|in:cash,credit',
            'due_date' => 'required_if:payment_type,credit|nullable|date|after:today',
            'initial_payment' => 'nullable|numeric|min:0',
            'payment_id' => 'required_unless:payment_type,credit|nullable|exists:payments,id',
            'cashbox_id' => 'required_unless:payment_type,credit|nullable|exists:cashboxes,id',
        ];
    }

    public function messages(): array
    {
        return [
            'payment_id.required_unless' => 'Selecciona el método de pago.',
            'cashbox_id.required_unless' => 'Selecciona la caja donde se cobra.',
            'due_date.required_if' => 'La fecha de vencimiento es obligatoria en una venta a crédito.',
            'due_date.after' => 'La fecha de vencimiento debe ser posterior a hoy.',
        ];
    }
}
