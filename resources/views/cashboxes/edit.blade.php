<x-ui.edit-page title="Editar Caja" category="Finanzas" :backRoute="route('cashboxes.index')"
    :action="route('cashboxes.update', $cashbox)" method="PUT" submitText="Guardar Cambios" :columns="2" :fields="[
        [
            'type' => 'text',
            'name' => 'name',
            'label' => 'Nombre',
            'value' => $cashbox->name,
            'placeholder' => 'Ej. Caja Principal',
            'required' => true,
        ],
        [
            'type' => 'select',
            'name' => 'branche_id',
            'label' => 'Sucursal',
            'value' => $cashbox->branche_id,
            'options' => $branches->pluck('name', 'id')->toArray(),
            'required' => true,
            'help' => 'No se puede cambiar si la caja ya tiene transacciones.',
        ],
    ]">
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-3 bg-gray-50 dark:bg-slate-800/40 rounded-lg border border-gray-100 dark:border-gray-800">
        <div>
            <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-0.5">Efectivo en caja</span>
            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $cashbox->cash_label }}</span>
        </div>
        <div>
            <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-0.5">Saldo total</span>
            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $cashbox->balance_label }}</span>
        </div>
        <p class="sm:col-span-2 text-[11px] text-gray-500 dark:text-gray-400">
            El efectivo y el saldo no se editan a mano: cambian solo con transacciones.
        </p>
    </div>
</x-ui.edit-page>
