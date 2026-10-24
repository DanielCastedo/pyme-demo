<x-ui.edit-page title="Editar Categoría" category="Inventario" :backRoute="route('categories.index')"
    :action="route('categories.update', $category)" method="PUT" submitText="Guardar Cambios" :columns="2" :fields="[
        [
            'type' => 'text',
            'name' => 'name',
            'label' => 'Nombre',
            'value' => $category->name,
            'placeholder' => 'Ej. Herramientas Manuales',
            'required' => true,
        ],
        [
            'type' => 'textarea',
            'name' => 'description',
            'label' => 'Descripción',
            'value' => $category->description,
            'placeholder' => 'Ej. Martillos, destornilladores y llaves.',
            'fullWidth' => true,
            'rows' => 3,
        ],
    ]" />
