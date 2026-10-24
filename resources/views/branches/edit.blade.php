<x-ui.edit-page title="Editar Sucursal" category="Inventario" :backRoute="route('branches.index')"
    :action="route('branches.update', $branch)" method="PUT" submitText="Guardar Cambios" :hasFile="true" :columns="2" :fields="[
        [
            'type' => 'text',
            'name' => 'name',
            'label' => 'Nombre',
            'value' => $branch->name,
            'placeholder' => 'Ej. Sucursal Central - Santa Cruz',
            'required' => true,
            'fullWidth' => true,
        ],
        [
            'type' => 'text',
            'name' => 'address',
            'label' => 'Dirección',
            'value' => $branch->address,
            'placeholder' => 'Ej. Av. Cristo Redentor, 3er anillo interno',
        ],
        [
            'type' => 'text',
            'name' => 'phone',
            'label' => 'Teléfono',
            'value' => $branch->phone,
            'placeholder' => 'Ej. 3-3456789',
        ],
        [
            'type' => 'file',
            'name' => 'logo',
            'label' => 'Logo',
            'help' => 'Se imprime en el encabezado de los recibos de esta sucursal. Deja vacío para conservar el actual.',
            'preview' => $branch->logo_url,
            'fullWidth' => true,
        ],
        [
            'type' => 'file',
            'name' => 'payment_qr_image',
            'label' => 'QR de Cobro',
            'help' => 'QR que verán los clientes de esta sucursal al pagar con QR. Deja vacío para conservar el actual.',
            'preview' => $branch->payment_qr_image_url,
            'fullWidth' => true,
        ],
    ]">
    <label class="flex items-center gap-2 cursor-pointer">
        <input type="checkbox" name="is_active" value="1" @checked($branch->is_active)
            class="w-4 h-4 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 cursor-pointer">
        <span class="text-sm text-gray-700 dark:text-gray-300">Sucursal activa</span>
    </label>
</x-ui.edit-page>
