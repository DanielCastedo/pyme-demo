<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSaleRequest extends FormRequest
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
            'payment_type' => 'nullable|in:cash,credit',
            'due_date' => 'required_if:payment_type,credit|nullable|date|after:today',
            'initial_payment' => 'nullable|numeric|min:0',
            'cashbox_id' => 'required_unless:payment_type,credit|nullable|exists:cashboxes,id',
            'payment_id' => 'required_unless:payment_type,credit|nullable|exists:payments,id',
            'note' => 'nullable|string|max:1000',
            'receipt_photo' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:10240',
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
            'warehouse_id.required' => 'Selecciona el almacén de donde sale la mercadería.',
            'cashbox_id.required_unless' => 'Selecciona la caja donde se cobra.',
            'payment_id.required_unless' => 'Selecciona el método de pago.',
            'due_date.required_if' => 'La fecha de vencimiento es obligatoria en una venta a crédito.',
            'due_date.after' => 'La fecha de vencimiento debe ser posterior a hoy.',
            'initial_payment.min' => 'El anticipo no puede ser negativo.',
            'receipt_photo.mimes' => 'El comprobante debe ser una imagen válida (JPG, PNG o WEBP).',
            'receipt_photo.max' => 'El comprobante no debe superar los 10MB.',
            'items.required' => 'Agrega al menos un producto a la venta.',
            'items.min' => 'Agrega al menos un producto a la venta.',
            'items.*.quantity.required' => 'La cantidad de cada producto es obligatoria.',
            'items.*.quantity.integer' => 'La cantidad debe ser un número entero (sin decimales).',
            'items.*.quantity.min' => 'La cantidad de cada producto debe ser mayor a 0.',
            'items.*.unit_price.min' => 'El precio de venta no puede ser negativo.',
            'items.*.discount_value.min' => 'El descuento no puede ser negativo.',
        ];
    }
}
