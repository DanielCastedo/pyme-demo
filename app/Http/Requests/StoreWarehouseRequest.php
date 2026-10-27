<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWarehouseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'branche_id' => 'required|exists:branches,id',
            'branch_ids' => 'nullable|array',
            'branch_ids.*' => 'integer|exists:branches,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:100|unique:warehouses,code',
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
            'code.unique' => 'Ya existe un almacén con ese código.',
        ];
    }
}
