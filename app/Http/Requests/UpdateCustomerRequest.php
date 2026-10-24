<?php

namespace App\Http\Requests;

use App\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'document_type' => ['nullable', Rule::in(array_keys(Customer::DOCUMENT_TYPES))],
            'document' => ['nullable', 'string', 'max:50', Rule::unique('customers', 'document')->ignore($this->route('customer'))],
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'credit_limit' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre o razón social del cliente es obligatorio.',
            'document_type.in' => 'El tipo de documento no es válido.',
            'document.unique' => 'Ya existe un cliente registrado con ese documento.',
            'email.email' => 'Ingresa un correo electrónico válido.',
        ];
    }
}
