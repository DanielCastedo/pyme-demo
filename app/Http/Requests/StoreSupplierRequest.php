<?php

namespace App\Http\Requests;

use App\Models\Supplier;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // la autorización la hace SupplierController (authorizeResource)
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'document_type' => ['nullable', Rule::in(array_keys(Supplier::DOCUMENT_TYPES))],
            'document' => ['nullable', 'string', 'max:50', Rule::unique('suppliers', 'document')->ignore($this->route('supplier'))],
            'contact_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'is_active' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre o razón social del proveedor es obligatorio.',
            'document_type.in' => 'El tipo de documento no es válido.',
            'document.unique' => 'Ya existe un proveedor registrado con ese documento.',
            'email.email' => 'Ingresa un correo electrónico válido.',
        ];
    }
}
