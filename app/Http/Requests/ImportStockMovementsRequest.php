<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportStockMovementsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // el permiso "kardex.importar" lo exige la ruta
    }

    public function rules(): array
    {
        return [
            'warehouse_id' => 'required|exists:warehouses,id',
            'note' => 'nullable|string|max:255',
            'file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ];
    }

    public function messages(): array
    {
        return [
            'warehouse_id.required' => 'Elige el almacén al que entra el inventario.',
            'warehouse_id.exists' => 'El almacén elegido no existe.',
            'file.required' => 'Debes seleccionar un archivo Excel.',
            'file.mimes' => 'El archivo debe ser .xlsx, .xls o .csv.',
            'file.max' => 'El archivo no debe superar los 5MB.',
        ];
    }
}
