<?php

namespace App\Http\Requests;

use App\Models\Product;
use App\Models\ProductComponent;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Agrega componentes a un kit. Dos formas de enviarlo:
 *  - varios a la vez (modal): product_id + components[i][component_product_id|quantity];
 *  - uno solo: product_id + component_product_id + quantity.
 */
class StoreProductComponentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** ¿Viene la lista de componentes (formulario del modal)? */
    public function isBulk(): bool
    {
        return $this->has('components');
    }

    public function rules(): array
    {
        if ($this->isBulk()) {
            return [
                'product_id' => 'required|exists:products,id',
                'components' => 'required|array|min:1',
                'components.*.component_product_id' => 'required|exists:products,id|distinct|different:product_id',
                'components.*.quantity' => 'required|integer|min:1',
            ];
        }

        return [
            'product_id' => 'required|exists:products,id',
            'component_product_id' => [
                'required',
                'exists:products,id',
                'different:product_id',
                function ($attribute, $value, $fail) {
                    $yaExiste = ProductComponent::where('product_id', $this->product_id)
                        ->where('component_product_id', $value)
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

    /**
     * Reglas del negocio para varios componentes: no repetir uno que el kit ya tiene, y sin kits dentro de kits
     * (un kit no tiene stock propio, así que no puede ser parte de otro).
     */
    public function withValidator(Validator $validator): void
    {
        if (! $this->isBulk()) {
            return;
        }

        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $kitId = (int) $this->input('product_id');
            $kit = Product::find($kitId);

            if (ProductComponent::where('component_product_id', $kitId)->exists()) {
                $validator->errors()->add('product_id', "«{$kit->name}» ya es parte de otro kit, y un kit no puede ser parte de otro. Elige otro producto como kit.");

                return;
            }

            $ya = ProductComponent::where('product_id', $kitId)->pluck('component_product_id')->all();
            $kits = ProductComponent::distinct()->pluck('product_id')->all();

            foreach ((array) $this->input('components') as $i => $row) {
                $id = (int) ($row['component_product_id'] ?? 0);
                $name = Product::find($id)?->name ?? '';

                if (in_array($id, $ya, true)) {
                    $validator->errors()->add("components.$i.component_product_id", "«{$name}» ya está en este kit.");
                } elseif (in_array($id, $kits, true)) {
                    $validator->errors()->add("components.$i.component_product_id", "«{$name}» es un kit y no puede ser parte de otro kit.");
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'product_id.required' => 'El producto (kit) es obligatorio.',
            'component_product_id.required' => 'El componente es obligatorio.',
            'component_product_id.different' => 'Un producto no puede ser componente de sí mismo.',
            'quantity.required' => 'La cantidad es obligatoria.',
            'quantity.min' => 'La cantidad debe ser al menos 1.',
            'components.required' => 'Agrega al menos un componente.',
            'components.min' => 'Agrega al menos un componente.',
            'components.*.component_product_id.required' => 'Elige el producto de cada componente.',
            'components.*.component_product_id.exists' => 'Uno de los componentes elegidos no existe.',
            'components.*.component_product_id.distinct' => 'Hay un componente repetido: usa una sola fila por producto y cambia su cantidad.',
            'components.*.component_product_id.different' => 'Un producto no puede ser componente de sí mismo.',
            'components.*.quantity.required' => 'Indica la cantidad de cada componente.',
            'components.*.quantity.integer' => 'La cantidad de cada componente debe ser un número entero.',
            'components.*.quantity.min' => 'La cantidad de cada componente debe ser al menos 1.',
        ];
    }
}
