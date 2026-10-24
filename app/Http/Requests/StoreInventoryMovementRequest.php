<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInventoryMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => 'required|exists:products,id',
            'type' => 'required|in:entrada,salida',
            'reason' => 'required|in:compra,venta,ajuste,devolucion,merma',
            'quantity' => 'required|integer|min:1',
            'unit_cost' => 'nullable|numeric|min:0',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'movement_date' => 'required|date',
        ];
    }

    public function messages(): array
    {
        return [
            'quantity.min' => 'La cantidad debe ser mayor a 0.',
            'quantity.integer' => 'La cantidad debe ser un número entero (sin decimales).',
        ];
    }
}
