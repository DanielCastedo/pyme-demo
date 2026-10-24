<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SettleConsignmentRequest extends FormRequest
{
    /**
     * Se autoriza antes de validar: quien no puede liquidar recibe 403, no los errores del formulario.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('settle', $this->route('consignment')) ?? false;
    }

    public function rules(): array
    {
        return [
            'lines' => 'required|array|min:1',
            'lines.*.consignment_item_id' => 'required|integer|exists:consignment_items,id',
            'lines.*.sold' => 'nullable|integer|min:0',
            'lines.*.returned' => 'nullable|integer|min:0',
            'note' => 'nullable|string|max:1000',
            // Cómo se cobra lo vendido (solo aplica si hay algo vendido)
            'payment_type' => 'nullable|in:cash,credit',
            'due_date' => 'required_if:payment_type,credit|nullable|date|after:today',
            'initial_payment' => 'nullable|numeric|min:0',
            'payment_id' => 'nullable|exists:payments,id',
            'cashbox_id' => 'nullable|exists:cashboxes,id',
        ];
    }

    public function messages(): array
    {
        return [
            'lines.required' => 'Indica cuánto vendió o devuelve el cliente.',
            'lines.*.sold.integer' => 'La cantidad vendida debe ser un número entero.',
            'lines.*.sold.min' => 'Lo vendido no puede ser negativo.',
            'lines.*.returned.integer' => 'La cantidad devuelta debe ser un número entero.',
            'lines.*.returned.min' => 'Lo devuelto no puede ser negativo.',
            'lines.*.sold.integer' => 'Lo vendido debe ser un número entero.',
            'lines.*.returned.integer' => 'Lo devuelto debe ser un número entero.',
            'due_date.required_if' => 'La fecha de vencimiento es obligatoria si lo vendido queda a crédito.',
            'due_date.after' => 'La fecha de vencimiento debe ser posterior a hoy.',
        ];
    }
}
