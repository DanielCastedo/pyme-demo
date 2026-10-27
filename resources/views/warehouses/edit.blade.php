<x-ui.edit-page title="Editar Almacén" category="Inventario" :backRoute="route('warehouses.index')"
    :action="route('warehouses.update', $warehouse)" method="PUT" submitText="Guardar Cambios" :columns="2" :fields="[
        [
            'type' => 'select',
            'name' => 'branche_id',
            'label' => 'Sucursal dueña',
            'value' => $warehouse->branche_id,
            'options' => $branches->pluck('name', 'id')->toArray(),
            'required' => true,
            'fullWidth' => true,
        ],
        [
            'type' => 'checkbox_group',
            'name' => 'branch_ids',
            'label' => 'Sucursales que usan este almacén',
            'value' => $warehouse->branchIds(),
            'options' => $branches->pluck('name', 'id')->toArray(),
            'help' => 'Marca varias si comparten el mismo stock (venden, compran y hacen pedidos de este almacén). La sucursal dueña siempre queda incluida.',
            'fullWidth' => true,
        ],
        [
            'type' => 'text',
            'name' => 'name',
            'label' => 'Nombre',
            'value' => $warehouse->name,
            'placeholder' => 'Ej. Almacén Central',
            'required' => true,
        ],
        [
            'type' => 'text',
            'name' => 'code',
            'label' => 'Código',
            'value' => $warehouse->code,
            'placeholder' => 'Ej. ALM-01',
            'required' => true,
        ],
    ]">
    <label class="flex items-center gap-2 cursor-pointer">
        <input type="checkbox" name="is_active" value="1" @checked($warehouse->is_active)
            class="w-4 h-4 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 cursor-pointer">
        <span class="text-sm text-gray-700 dark:text-gray-300">Almacén activo</span>
    </label>

    <label class="flex items-center gap-2 cursor-pointer mt-3">
        <input type="checkbox" name="default_warehouse" id="default-warehouse-checkbox" value="1"
            class="w-4 h-4 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 cursor-pointer">
        <span class="text-sm text-gray-700 dark:text-gray-300">Usar como almacén predeterminado para todas las operaciones</span>
    </label>
    <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-1">
        Se guarda solo en este navegador (no en el servidor): preselecciona este almacén al vender, hacer pedidos, consignar, comprar y en movimientos de stock.
    </p>

    <script>
        // El estado de "predeterminado" vive en localStorage (no en la BD): se marca la casilla acá
        // si este almacén ya es el guardado en este navegador.
        (function() {
            try {
                const loc = JSON.parse(localStorage.getItem('pos-default-location') || '{}');
                if (String(loc.warehouse) === String({{ $warehouse->id }})) {
                    document.getElementById('default-warehouse-checkbox').checked = true;
                }
            } catch (e) {}
        })();
    </script>
</x-ui.edit-page>
