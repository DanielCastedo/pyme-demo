<x-ui.edit-page title="Editar Stock por Almacén" category="Inventario" :backRoute="route('product-warehouses.index')"
    :action="route('product-warehouses.update', $productWarehouse->composite_key)" method="PUT" submitText="Guardar Cambios"
    :columns="2" :fields="[
        [
            'type' => 'number',
            'name' => 'min_stock',
            'label' => 'Stock Mínimo',
            'value' => $productWarehouse->min_stock,
            'placeholder' => 'Ej. 5',
            'required' => true,
            'fullWidth' => true,
            'help' => 'El stock y el costo salen de los lotes: se modifican con movimientos de entrada/salida, no a mano.',
        ],
    ]">
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-3 bg-gray-50 dark:bg-slate-800/40 rounded-lg border border-gray-100 dark:border-gray-800">
        <div>
            <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-0.5">Producto</span>
            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $productWarehouse->product->name }}</span>
        </div>
        <div>
            <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-0.5">Almacén</span>
            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $productWarehouse->warehouse->name }}</span>
        </div>
    </div>
</x-ui.edit-page>
