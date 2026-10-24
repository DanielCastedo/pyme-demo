<?php

namespace App\Http\Requests;

class UpdatePedidoRequest extends StorePedidoRequest
{
    /**
     * Al editar, la fecha de entrega puede quedar como estaba aunque ya haya pasado (pedido atrasado).
     */
    public function rules(): array
    {
        return [...parent::rules(), 'delivery_date' => 'required|date'];
    }
}
