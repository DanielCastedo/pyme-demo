<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWarehouseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $warehouseId = $this->route('warehouse')->id;

        return [
            'branche_id' => 'required|exists:branches,id',
            'name' => 'required|string|max:255',
            'code' => ['required', 'string', 'max:100', Rule::unique('warehouses', 'code')->ignore($warehouseId)],
            'is_active' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'branche_id.required' => 'La sucursal es obligatoria.',
            'branche_id.exists' => 'La sucursal seleccionada no existe.',
            'name.required' => 'El nombre del almacén es obligatorio.',
            'code.required' => 'El código del almacén es obligatorio.',
            'code.unique' => 'Ya existe otro almacén con ese código.',
        ];
    }
}
