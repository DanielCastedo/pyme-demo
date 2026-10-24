<x-ui.edit-page title="Editar Promoción" category="Inventario" :backRoute="route('promotions.index')"
    :action="route('promotions.update', $promotion)" method="PUT" submitText="Guardar Cambios" :columns="2" :fields="[
        [
            'type' => 'text',
            'name' => 'name',
            'label' => 'Nombre',
            'value' => $promotion->name,
            'placeholder' => 'Ej. Lanzamiento Herramientas Eléctricas',
            'required' => true,
            'fullWidth' => true,
        ],
        [
            'type' => 'number',
            'name' => 'value',
            'label' => 'Valor del Descuento',
            'value' => $promotion->value,
            'placeholder' => 'Ej. 10',
            'required' => true,
        ],
        [
            'type' => 'number',
            'name' => 'min_quantity',
            'label' => 'Cantidad Mínima',
            'value' => $promotion->min_quantity,
            'placeholder' => 'Ej. 2',
        ],
        [
            'type' => 'date',
            'name' => 'start_date',
            'label' => 'Fecha de Inicio',
            'value' => $promotion->start_date->format('Y-m-d'),
            'required' => true,
        ],
        [
            'type' => 'date',
            'name' => 'end_date',
            'label' => 'Fecha de Fin',
            'value' => $promotion->end_date->format('Y-m-d'),
            'required' => true,
        ],
        [
            'type' => 'checkbox_group',
            'name' => 'product_ids',
            'label' => 'Productos incluidos',
            'value' => $selectedProductIds,
            'options' => $products->pluck('name', 'id')->toArray(),
            'fullWidth' => true,
        ],
    ]">
    <label class="flex items-center gap-2 cursor-pointer">
        <input type="checkbox" name="is_active" value="1" @checked($promotion->is_active)
            class="w-4 h-4 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 cursor-pointer">
        <span class="text-sm text-gray-700 dark:text-gray-300">Promoción activa</span>
    </label>
</x-ui.edit-page>
