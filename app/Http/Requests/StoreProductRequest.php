<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => 'required|exists:categories,id',
            'sku' => [
                'required', 'string', 'max:100', 'unique:products,sku',
                // Si se crea la unidad base, su código de barras será este SKU: no puede repetir el de otra unidad.
                function (string $attribute, mixed $value, \Closure $fail) {
                    if ($this->boolean('create_unit') && \App\Models\ProductUnit::where('barcode', $value)->exists()) {
                        $fail('Ese código ya lo usa otra unidad como código de barras; el SKU del producto será el código de su unidad base.');
                    }
                },
            ],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:100',
            'marca' => 'nullable|string|max:100',
            'medida' => 'nullable|string|max:100',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:10240',
            'is_active' => 'nullable|boolean',

            // Unidad base opcional: se crea junto con el producto (factor 1).
            'create_unit' => 'nullable|boolean',
            'unit_id' => 'required_if:create_unit,1|nullable|exists:units,id',
            'unit_price' => 'required_if:create_unit,1|nullable|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.required' => 'La categoría es obligatoria.',
            'category_id.exists' => 'La categoría seleccionada no existe.',
            'sku.required' => 'El código (SKU) es obligatorio.',
            'sku.unique' => 'Ya existe un producto con ese código.',
            'name.required' => 'El nombre del producto es obligatorio.',
            'unit_id.required_if' => 'Elige la unidad de medida de la unidad base.',
            'unit_id.exists' => 'La unidad de medida elegida no existe.',
            'unit_price.required_if' => 'Indica el precio de venta de la unidad.',
            'unit_price.min' => 'El precio no puede ser negativo.',
            'photo.image' => 'El archivo debe ser una imagen.',
            'photo.mimes' => 'La imagen debe ser JPG, PNG o WEBP.',
            'photo.max' => 'La imagen no debe superar los 10MB.',
        ];
    }
}
