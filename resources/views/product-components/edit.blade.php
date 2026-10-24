<x-ui.edit-page title="Editar Componente" category="Inventario" :backRoute="route('product-components.index')"
    :action="route('product-components.update', $productComponent)" method="PUT" submitText="Guardar Cambios" :columns="2" :fields="[
        [
            'type' => 'select',
            'name' => 'product_id',
            'label' => 'Producto (Kit)',
            'value' => $productComponent->product_id,
            'options' => $products->pluck('name', 'id')->toArray(),
            'required' => true,
            'help' => 'El producto que se arma con los componentes.',
        ],
        [
            'type' => 'select',
            'name' => 'component_product_id',
            'label' => 'Componente',
            'value' => $productComponent->component_product_id,
            'options' => $products->pluck('name', 'id')->toArray(),
            'required' => true,
            'help' => 'El producto que se usa como parte del kit.',
        ],
        [
            'type' => 'number',
            'name' => 'quantity',
            'label' => 'Cantidad',
            'value' => $productComponent->quantity,
            'placeholder' => 'Ej. 4',
            'required' => true,
            'fullWidth' => true,
        ],
    ]" />
