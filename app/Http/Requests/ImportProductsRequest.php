<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportProductsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => 'required|file|mimes:xlsx|max:20480',
            'warehouse_id' => 'required|exists:warehouses,id',
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Debes seleccionar un archivo Excel.',
            'file.mimes' => 'El archivo debe ser .xlsx (para poder leer la foto pegada en la celda).',
            'file.max' => 'El archivo no debe superar los 20MB.',
            'warehouse_id.required' => 'Elige el almacén al que entra el stock (columna Cantidad) de este archivo.',
            'warehouse_id.exists' => 'El almacén elegido no existe.',
        ];
    }
}
