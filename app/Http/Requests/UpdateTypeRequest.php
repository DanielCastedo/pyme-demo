<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $typeId = $this->route('type')->id;

        return [
            'name' => 'required|string|max:255',
            'code' => ['required', 'string', 'max:50', Rule::unique('types', 'code')->ignore($typeId)],
            'sign' => 'nullable|string|max:10',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del tipo es obligatorio.',
            'code.required' => 'El código es obligatorio.',
            'code.unique' => 'Ya existe otro tipo con ese código.',
        ];
    }
}
