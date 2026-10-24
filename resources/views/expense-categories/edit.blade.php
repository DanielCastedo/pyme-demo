<x-ui.edit-page title="Editar Categoría" category="Gastos" :backRoute="route('expense-categories.index')"
    :action="route('expense-categories.update', $category)" method="PUT" submitText="Guardar Cambios" :columns="1" :fields="[
        ['type' => 'text', 'name' => 'name', 'label' => 'Nombre', 'value' => $category->name, 'placeholder' => 'Ej. Servicios básicos', 'required' => true],
    ]">
    <label class="flex items-center gap-2 cursor-pointer">
        <input type="checkbox" name="is_active" value="1" @checked($category->is_active)
            class="w-4 h-4 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 cursor-pointer">
        <span class="text-sm text-gray-700 dark:text-gray-300">Categoría activa</span>
    </label>
</x-ui.edit-page>
