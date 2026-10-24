<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // la autorización la hace PurchaseController (authorizeResource)
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'pay_now' => $this->boolean('pay_now'),
            'payment_type' => $this->input('payment_type') === 'credit' ? 'credit' : 'cash',
        ]);
    }

    public function rules(): array
    {
        return [
            'warehouse_id' => 'required|exists:warehouses,id',
            'date' => 'nullable|date|before_or_equal:today',
            'supplier_id' => 'nullable|required_if:payment_type,credit|exists:suppliers,id',
            'invoice_number' => 'nullable|string|max:100',
            'note' => 'nullable|string|max:1000',
            'payment_type' => 'required|in:cash,credit',
            'due_date' => 'nullable|required_if:payment_type,credit|date|after:today',
            'initial_payment' => 'nullable|numeric|min:0',
            'pay_now' => 'boolean',
            'payment_id' => 'required_if:pay_now,1|nullable|exists:payments,id',
            'cashbox_id' => 'required_if:pay_now,1|nullable|exists:cashboxes,id',
            'items' => 'required|array|min:1',
            'items.*.product_unit_id' => 'required|distinct|exists:product_units,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_cost' => 'required|numeric|min:0',
            // Reparto opcional del producto entre almacenes; si viene, tiene que sumar exactamente la cantidad comprada.
            'items.*.distribution' => 'nullable|array',
            'items.*.distribution.*.warehouse_id' => 'required|exists:warehouses,id',
            'items.*.distribution.*.quantity' => 'required|integer|min:1',
        ];
    }

    /** El reparto de cada producto debe usar almacenes distintos y sumar exactamente lo comprado. */
    public function after(): array
    {
        return [function (Validator $validator) {
            $items = (array) $this->input('items', []);
            $distributing = collect($items)->contains(fn ($item) => ! empty($item['distribution']));

            foreach ($items as $i => $item) {
                $distribution = array_values((array) ($item['distribution'] ?? []));

                if ($distribution === []) {
                    // Si se distribuye en almacenes, no puede haber productos sin destino
                    if ($distributing) {
                        $validator->errors()->add("items.$i.distribution", 'Estás distribuyendo la compra: indica en qué almacenes entra cada producto.');
                    }

                    continue;
                }

                $warehouses = array_column($distribution, 'warehouse_id');
                if (count($warehouses) !== count(array_unique($warehouses))) {
                    $validator->errors()->add("items.$i.distribution", 'Un almacén está repetido en el reparto de un producto.');

                    continue;
                }

                $assigned = array_sum(array_map(fn ($d) => (int) ($d['quantity'] ?? 0), $distribution));
                $bought = (int) ($item['quantity'] ?? 0);

                if ($assigned !== $bought) {
                    $validator->errors()->add(
                        "items.$i.distribution",
                        "El reparto de un producto suma $assigned pero compraste $bought: reparte exactamente la cantidad comprada."
                    );
                }
            }
        }];
    }

    public function messages(): array
    {
        return [
            'warehouse_id.required' => 'Elige el almacén donde entra la mercadería.',
            'items.required' => 'Agrega al menos un producto a la compra.',
            'items.min' => 'Agrega al menos un producto a la compra.',
            'items.*.product_unit_id.distinct' => 'Un producto está repetido en la compra.',
            'items.*.quantity.integer' => 'La cantidad debe ser un número entero (sin decimales).',
            'items.*.quantity.min' => 'La cantidad mínima es 1.',
            'items.*.unit_cost.required' => 'Indica el costo de cada producto.',
            'items.*.unit_cost.min' => 'El costo no puede ser negativo.',
            'items.*.distribution.*.warehouse_id.required' => 'Elige el almacén de cada parte del reparto.',
            'items.*.distribution.*.warehouse_id.exists' => 'Un almacén del reparto no existe.',
            'items.*.distribution.*.quantity.integer' => 'Las cantidades del reparto deben ser enteras.',
            'items.*.distribution.*.quantity.min' => 'Cada parte del reparto debe ser de al menos 1.',
            'payment_id.required_if' => 'Elige el método de pago.',
            'cashbox_id.required_if' => 'Elige la caja de donde sale el dinero.',
            'date.before_or_equal' => 'La fecha de la compra no puede ser futura.',
            'supplier_id.required_if' => 'Una compra a crédito necesita un proveedor registrado.',
            'due_date.required_if' => 'Indica la fecha de vencimiento del crédito.',
            'due_date.after' => 'La fecha de vencimiento del crédito debe ser posterior a hoy.',
        ];
    }
}
