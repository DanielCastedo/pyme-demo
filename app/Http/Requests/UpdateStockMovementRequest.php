<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Un movimiento ya afectó el stock/costo promedio al crearse, así que solo se
 * permite corregir la nota — no el tipo, almacén, fecha ni las líneas. Para
 * corregir cantidades reales, se registra un movimiento nuevo (o se elimina
 * este, ver StockMovementController::destroy()).
 */
class UpdateStockMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'note' => 'nullable|string',
        ];
    }
}
