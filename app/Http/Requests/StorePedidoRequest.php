<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePedidoRequest extends FormRequest
{
    use \App\Http\Requests\Concerns\ValidatesItemWarehouses;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => 'nullable|exists:customers,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'branche_id' => 'nullable|exists:branches,id',
            'delivery_date' => 'required|date|after_or_equal:today',
            'note' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.product_unit_id' => 'required|exists:product_units,id',
            'items.*.warehouse_id' => 'nullable|exists:warehouses,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'nullable|numeric|min:0',
            'items.*.discount_type' => 'nullable|in:percent,amount',
            'items.*.discount_value' => 'nullable|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'warehouse_id.required' => 'Selecciona el almacén de donde saldrá la mercadería.',
            'delivery_date.required' => 'La fecha de entrega es obligatoria.',
            'delivery_date.after_or_equal' => 'La fecha de entrega no puede ser anterior a hoy.',
            'items.required' => 'Agrega al menos un producto al pedido.',
            'items.min' => 'Agrega al menos un producto al pedido.',
            'items.*.quantity.required' => 'La cantidad de cada producto es obligatoria.',
            'items.*.quantity.integer' => 'La cantidad debe ser un número entero (sin decimales).',
            'items.*.quantity.min' => 'La cantidad de cada producto debe ser mayor a 0.',
            'items.*.unit_price.min' => 'El precio de venta no puede ser negativo.',
            'items.*.discount_value.min' => 'El descuento no puede ser negativo.',
        ];
    }
}
