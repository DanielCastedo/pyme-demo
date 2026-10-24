<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $supplierId = $this->route('supplier')->id;

        return [
            'name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'tax_id' => ['nullable', 'string', 'max:50', Rule::unique('suppliers', 'tax_id')->ignore($supplierId)],
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'active' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del proveedor es obligatorio.',
            'tax_id.unique' => 'Ya existe otro proveedor registrado con ese NIT.',
        ];
    }
}
