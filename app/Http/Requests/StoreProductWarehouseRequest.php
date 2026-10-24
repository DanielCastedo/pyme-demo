<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class StoreProductWarehouseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => 'required|exists:products,id',
            'warehouse_id' => [
                'required',
                'exists:warehouses,id',
                function ($attribute, $value, $fail) {
                    $existe = DB::table('product_warehouses')
                        ->where('product_id', $this->product_id)
                        ->where('warehouse_id', $value)
                        ->exists();

                    if ($existe) {
                        $fail('Este producto ya está registrado en ese almacén. Edita el registro existente en vez de crear uno nuevo.');
                    }
                },
            ],
            'stock' => 'nullable|integer|min:0',
            'min_stock' => 'nullable|integer|min:0',
            'avg_cost' => 'nullable|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => 'El producto es obligatorio.',
            'product_id.exists' => 'El producto seleccionado no existe.',
            'warehouse_id.required' => 'El almacén es obligatorio.',
            'warehouse_id.exists' => 'El almacén seleccionado no existe.',
        ];
    }
}
