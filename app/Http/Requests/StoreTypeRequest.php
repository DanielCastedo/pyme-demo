<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:types,code',
            'sign' => 'nullable|string|max:10',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del tipo es obligatorio.',
            'code.required' => 'El código es obligatorio.',
            'code.unique' => 'Ya existe un tipo con ese código.',
        ];
    }
}
