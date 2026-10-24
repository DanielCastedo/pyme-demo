<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductWarehouseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'min_stock' => 'required|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'min_stock.required' => 'El stock mínimo es obligatorio.',
        ];
    }
}
