<x-ui.edit-page title="Editar Unidad" category="Inventario" :backRoute="route('units.index')"
    :action="route('units.update', $unit)" method="PUT" submitText="Guardar Cambios" :columns="1" :fields="[
        ['type' => 'text', 'name' => 'name', 'label' => 'Nombre', 'value' => $unit->name, 'placeholder' => 'Ej. Caja', 'required' => true,
            'help' => 'Si la renombras, las presentaciones de productos que la usan toman el nuevo nombre.'],
        ['type' => 'text', 'name' => 'abbreviation', 'label' => 'Abreviatura', 'value' => $unit->abbreviation, 'placeholder' => 'Ej. cja (opcional)'],
    ]">
    <label class="flex items-center gap-2 cursor-pointer">
        <input type="checkbox" name="is_active" value="1" @checked($unit->is_active)
            class="w-4 h-4 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 cursor-pointer">
        <span class="text-sm text-gray-700 dark:text-gray-300">Unidad activa</span>
    </label>
</x-ui.edit-page>
