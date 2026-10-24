<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCashboxRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('cashboxes')
                    ->where('branche_id', $this->input('branche_id'))
                    ->ignore($this->route('cashbox')),
            ],
            'branche_id' => 'required|exists:branches,id',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la caja es obligatorio.',
            'name.unique' => 'Ya existe una caja con ese nombre en la sucursal seleccionada.',
            'branche_id.required' => 'La sucursal es obligatoria.',
            'branche_id.exists' => 'La sucursal seleccionada no existe.',
        ];
    }
}
