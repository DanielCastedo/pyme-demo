<x-ui.edit-page title="Editar Cliente" category="Ventas" :backRoute="route('customers.index')"
    :action="route('customers.update', $customer)" method="PUT" submitText="Guardar Cambios" :columns="2" :fields="[
        [
            'type' => 'text',
            'name' => 'name',
            'label' => 'Nombre / Razón social',
            'value' => $customer->name,
            'placeholder' => 'Ej. Constructora Andina S.R.L.',
            'required' => true,
            'fullWidth' => true,
        ],
        [
            'type' => 'select',
            'name' => 'document_type',
            'label' => 'Tipo de documento',
            'value' => $customer->document_type,
            'options' => \App\Models\Customer::DOCUMENT_TYPES,
            'required' => true,
        ],
        [
            'type' => 'text',
            'name' => 'document',
            'label' => 'N° de documento',
            'value' => $customer->document,
            'placeholder' => 'Opcional',
        ],
        [
            'type' => 'text',
            'name' => 'phone',
            'label' => 'Teléfono',
            'value' => $customer->phone,
            'placeholder' => 'Ej. 7XXXXXXX',
        ],
        [
            'type' => 'email',
            'name' => 'email',
            'label' => 'Correo',
            'value' => $customer->email,
            'placeholder' => 'Opcional',
        ],
        [
            'type' => 'text',
            'name' => 'address',
            'label' => 'Dirección',
            'value' => $customer->address,
            'placeholder' => 'Opcional',
        ],
        [
            'type' => 'text',
            'name' => 'city',
            'label' => 'Ciudad',
            'value' => $customer->city,
            'placeholder' => 'Ej. Santa Cruz',
        ],
        [
            'type' => 'number',
            'name' => 'credit_limit',
            'label' => 'Límite de crédito',
            'value' => $customer->credit_limit,
            'placeholder' => 'Vacío = sin límite',
            'help' => 'Vacío = sin límite. Es lo máximo que este cliente puede deber a la vez.',
        ],
    ]">
    <label class="flex items-center gap-2 cursor-pointer">
        <input type="checkbox" name="is_active" value="1" @checked($customer->is_active)
            class="w-4 h-4 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 cursor-pointer">
        <span class="text-sm text-gray-700 dark:text-gray-300">Cliente activo</span>
    </label>
</x-ui.edit-page>
