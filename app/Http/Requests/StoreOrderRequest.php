<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => 'nullable|exists:customers,id',
            'customer_name' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:30',
            'deposit' => 'nullable|numeric|min:0',
            'expected_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Debes agregar al menos un producto al pedido.',
            'items.*.quantity.required' => 'La cantidad de cada producto es obligatoria.',
            'items.*.quantity.integer' => 'La cantidad debe ser un número entero (sin decimales).',
            'items.*.quantity.min' => 'La cantidad debe ser mayor a 0.',
        ];
    }
}
