<?php

namespace App\Http\Requests;

use App\Support\Bc;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $factor = $this->input('factor');

        if (is_numeric($factor) && Bc::isInteger($factor)) {
            $this->merge(['factor' => (int) $factor]);
        }
    }

    public function rules(): array
    {
        $productUnitId = $this->route('product_unit')->id;

        return [
            'product_id' => 'required|exists:products,id',
            'unit_id' => 'required|exists:units,id',
            'factor' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'is_base' => 'nullable|boolean',
            'barcode' => ['nullable', 'string', 'max:255', Rule::unique('product_units', 'barcode')->ignore($productUnitId)],
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => 'El producto es obligatorio.',
            'product_id.exists' => 'El producto seleccionado no existe.',
            'unit_id.required' => 'Elige la unidad de medida (créala antes en Unidades de medida si no está).',
            'unit_id.exists' => 'La unidad de medida elegida no existe.',
            'factor.required' => 'El factor de conversión es obligatorio.',
            'factor.integer' => 'El factor de conversión debe ser un número entero (sin decimales).',
            'factor.min' => 'El factor de conversión debe ser al menos 1.',
            'price.required' => 'El precio es obligatorio.',
            'barcode.unique' => 'Ya existe otra unidad con ese código de barras.',
        ];
    }
}
