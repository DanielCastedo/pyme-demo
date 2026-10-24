<x-ui.edit-page title="Editar Proveedor" category="Inventario" :backRoute="route('suppliers.index')"
    :action="route('suppliers.update', $supplier)" method="PUT" submitText="Guardar Cambios" :columns="2" :fields="[
        [
            'type' => 'text',
            'name' => 'name',
            'label' => 'Nombre / Razón social',
            'value' => $supplier->name,
            'placeholder' => 'Ej. Distribuidora Importadora S.R.L.',
            'required' => true,
            'fullWidth' => true,
        ],
        [
            'type' => 'select',
            'name' => 'document_type',
            'label' => 'Tipo de documento',
            'value' => $supplier->document_type,
            'options' => \App\Models\Supplier::DOCUMENT_TYPES,
            'required' => true,
        ],
        [
            'type' => 'text',
            'name' => 'document',
            'label' => 'N° de documento',
            'value' => $supplier->document,
            'placeholder' => 'Opcional',
        ],
        [
            'type' => 'text',
            'name' => 'contact_name',
            'label' => 'Persona de contacto',
            'value' => $supplier->contact_name,
            'placeholder' => 'Opcional',
            'fullWidth' => true,
        ],
        [
            'type' => 'text',
            'name' => 'phone',
            'label' => 'Teléfono',
            'value' => $supplier->phone,
            'placeholder' => 'Ej. 7XXXXXXX',
        ],
        [
            'type' => 'email',
            'name' => 'email',
            'label' => 'Correo',
            'value' => $supplier->email,
            'placeholder' => 'Opcional',
        ],
        [
            'type' => 'text',
            'name' => 'address',
            'label' => 'Dirección',
            'value' => $supplier->address,
            'placeholder' => 'Opcional',
        ],
        [
            'type' => 'text',
            'name' => 'city',
            'label' => 'Ciudad',
            'value' => $supplier->city,
            'placeholder' => 'Ej. Santa Cruz',
        ],
    ]">
    <label class="flex items-center gap-2 cursor-pointer">
        <input type="checkbox" name="is_active" value="1" @checked($supplier->is_active)
            class="w-4 h-4 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 cursor-pointer">
        <span class="text-sm text-gray-700 dark:text-gray-300">Proveedor activo</span>
    </label>
</x-ui.edit-page>
