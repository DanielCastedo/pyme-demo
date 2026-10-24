<?php

namespace App\Http\Requests;

use Illuminate\Support\Arr;

/**
 * Agregar productos a una compra ya registrada. Lo que se compró antes no se modifica, así que solo llegan los
 * productos nuevos (con su reparto opcional entre almacenes) y, si se paga, el método y la caja.
 * Las reglas de reparto y los mensajes son los mismos que al registrar la compra.
 */
class UpdatePurchaseRequest extends StorePurchaseRequest
{
    public function rules(): array
    {
        return Arr::except(parent::rules(), ['date', 'supplier_id', 'invoice_number', 'note', 'payment_type', 'due_date', 'initial_payment']);
    }

    public function messages(): array
    {
        return parent::messages() + [
            'items.required' => 'Agrega al menos un producto nuevo a la compra.',
            'items.min' => 'Agrega al menos un producto nuevo a la compra.',
        ];
    }
}
