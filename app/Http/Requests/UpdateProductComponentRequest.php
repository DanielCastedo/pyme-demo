<?php

namespace App\Http\Requests;

use App\Models\ProductComponent;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProductComponentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $productComponentId = $this->route('product_component')->id;

        return [
            'product_id' => 'required|exists:products,id',
            'component_product_id' => [
                'required',
                'exists:products,id',
                'different:product_id',
                function ($attribute, $value, $fail) use ($productComponentId) {
                    $yaExiste = ProductComponent::where('product_id', $this->product_id)
                        ->where('component_product_id', $value)
                        ->where('id', '!=', $productComponentId)
                        ->exists();

                    if ($yaExiste) {
                        $fail('Este componente ya está agregado a ese producto.');

                        return;
                    }

                    $esCicloInmediato = ProductComponent::where('product_id', $value)
                        ->where('component_product_id', $this->product_id)
                        ->exists();

                    if ($esCicloInmediato) {
                        $fail('No puedes usar este producto como componente: ya es un kit que usa el producto elegido como su propio componente.');
                    }
                },
            ],
            'quantity' => 'required|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => 'El producto (kit) es obligatorio.',
            'component_product_id.required' => 'El componente es obligatorio.',
            'component_product_id.different' => 'Un producto no puede ser componente de sí mismo.',
            'quantity.required' => 'La cantidad es obligatoria.',
            'quantity.min' => 'La cantidad debe ser al menos 1.',
        ];
    }
}
