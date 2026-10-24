<?php

namespace App\Http\Requests;

use App\Models\Type;
use Illuminate\Foundation\Http\FormRequest;

class StoreStockMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type_id' => [
                'required',
                'exists:types,id',
                function ($attribute, $value, $fail) {
                    $type = Type::find($value);

                    if ($type && ! in_array($type->code, ['ENT', 'SAL'])) {
                        $fail('El tipo seleccionado no es de entrada ni de salida de stock.');
                    }
                },
            ],
            'date' => 'required|date',
            'warehouse_id' => 'required|exists:warehouses,id',
            'note' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_unit_id' => 'required|exists:product_units,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_cost' => 'nullable|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'type_id.required' => 'El tipo de movimiento es obligatorio.',
            'date.required' => 'La fecha es obligatoria.',
            'warehouse_id.required' => 'El almacén es obligatorio.',
            'items.required' => 'Agrega al menos un producto al movimiento.',
            'items.min' => 'Agrega al menos un producto al movimiento.',
            'items.*.product_unit_id.required' => 'Selecciona el producto/unidad de cada línea.',
            'items.*.quantity.required' => 'La cantidad de cada línea es obligatoria.',
            'items.*.quantity.integer' => 'La cantidad debe ser un número entero (sin decimales).',
            'items.*.quantity.min' => 'La cantidad debe ser mayor a 0.',
            'items.*.quantity.integer' => 'La cantidad debe ser un número entero (sin decimales).',
        ];
    }
}
