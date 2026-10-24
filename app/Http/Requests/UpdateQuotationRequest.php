<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateQuotationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => 'required|date',
            'expires_at' => 'nullable|date|after_or_equal:date',
            'status' => 'sometimes|in:pending,approved,rejected,expired',
            'customer_id' => 'nullable|exists:customers,id',
            'customer_name' => 'required|string|max:255',
            'customer_document' => 'nullable|string|max:50',
            'customer_phone' => 'nullable|string|max:50',
            'customer_email' => 'nullable|email|max:255',
            'customer_address' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_unit_id' => 'required|exists:product_units,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'date.required' => 'La fecha de la proforma es obligatoria.',
            'expires_at.after_or_equal' => 'La fecha de vencimiento debe ser igual o posterior a la fecha de emisión.',
            'customer_name.required' => 'El nombre o razón social del cliente es obligatorio.',
            'customer_id.exists' => 'El cliente seleccionado no existe en el sistema.',
            'items.required' => 'Debes agregar al menos un producto a la proforma.',
            'items.min' => 'Debes agregar al menos un producto a la proforma.',
            'items.*.product_unit_id.required' => 'La unidad del producto es obligatoria.',
            'items.*.quantity.required' => 'La cantidad es obligatoria.',
            'items.*.quantity.min' => 'La cantidad debe ser mayor a 0.',
            'items.*.unit_price.required' => 'El precio unitario es obligatorio.',
            'items.*.unit_price.min' => 'El precio unitario no puede ser negativo.',
        ];
    }
}
