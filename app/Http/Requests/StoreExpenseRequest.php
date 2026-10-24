<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // la autorización la hace ExpenseController (authorizeResource)
    }

    public function rules(): array
    {
        return [
            'date' => 'required|date|before_or_equal:today',
            'concept' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'amount' => 'required|numeric|min:0.01|max:9999999999',
            'receipt_number' => 'nullable|string|max:100',
            'expense_category_id' => 'nullable|exists:expense_categories,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'payment_id' => 'required|exists:payments,id',
            'cashbox_id' => 'required|exists:cashboxes,id',
        ];
    }

    public function messages(): array
    {
        return [
            'date.required' => 'La fecha del gasto es obligatoria.',
            'date.before_or_equal' => 'La fecha del gasto no puede ser futura.',
            'concept.required' => 'Indica en qué se gastó (concepto).',
            'amount.required' => 'El monto es obligatorio.',
            'amount.min' => 'El monto debe ser mayor a 0.',
            'payment_id.required' => 'Elige el método de pago.',
            'cashbox_id.required' => 'Elige la caja de donde sale el dinero.',
        ];
    }
}
