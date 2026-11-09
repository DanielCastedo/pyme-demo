<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesPaymentSplit;
use Illuminate\Foundation\Http\FormRequest;

class StoreOrderPaymentRequest extends FormRequest
{
    use ValidatesPaymentSplit;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => 'required|numeric|min:0.01',
            'cashbox_id' => 'required|exists:cashboxes,id',
            ...$this->paymentSplitRules(true),
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'El monto del cobro es obligatorio.',
            'amount.min' => 'El monto del cobro debe ser mayor a 0.',
            'cashbox_id.required' => 'Selecciona la caja donde se cobra.',
            ...$this->paymentSplitMessages(),
        ];
    }
}
