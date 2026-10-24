<x-ui.edit-page title="Editar Método de Pago" category="Ventas" :backRoute="route('payments.index')"
    :action="route('payments.update', $paymentMethod)" method="PUT" submitText="Guardar Cambios" :columns="2"
    :hasFile="true" :fields="[
        [
            'type' => 'text',
            'name' => 'name',
            'label' => 'Nombre',
            'value' => $paymentMethod->name,
            'placeholder' => 'Ej. Transferencia Bancaria',
            'required' => true,
        ],
        [
            'type' => 'text',
            'name' => 'number',
            'label' => 'N° de Cuenta / Celular',
            'value' => $paymentMethod->number,
            'placeholder' => 'Opcional',
        ],
        [
            'type' => 'textarea',
            'name' => 'note',
            'label' => 'Nota / Instrucciones',
            'value' => $paymentMethod->note,
            'placeholder' => 'Ej. Banco Mercantil Santa Cruz, Cuenta Corriente.',
            'fullWidth' => true,
            'rows' => 3,
        ],
        [
            'type' => 'file',
            'name' => 'photo',
            'label' => 'Imagen QR / Comprobante',
            'fullWidth' => true,
            'preview' => $paymentMethod->photo_url,
            'help' => 'Formatos admitidos: JPG, PNG o WEBP. Máx 10MB (se optimiza automáticamente). Deja vacío para conservar la actual.',
        ],
    ]">
    <label class="flex items-center gap-2 cursor-pointer">
        <input type="checkbox" name="is_cash" value="1" @checked($paymentMethod->is_cash)
            class="w-4 h-4 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 cursor-pointer">
        <span class="text-sm text-gray-700 dark:text-gray-300">Es efectivo (suma al efectivo físico de la caja)</span>
    </label>
</x-ui.edit-page>
