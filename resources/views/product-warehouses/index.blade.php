<x-app-layout>
    <div class="max-w-10xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        @if (session('success'))
            <x-ui.alert type="success" :message="session('success')" />
        @endif

        @if (session('error'))
            <x-ui.alert type="error" :message="session('error')" />
        @endif

        @if ($errors->any())
            <x-ui.alert type="error">
                <ul class="list-disc pl-4 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-ui.alert>
        @endif

        @php
            $buttons = [
                [
                    'id' => 'modal-toggle-button',
                    'can' => 'crear product-warehouses',
                    'label' => 'Registrar Stock',
                    'variant' => 'primary',
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />',
                ],
            ];
        @endphp

        <x-ui.page-header category="Inventario" title="Stock por Almacén"
            description="Stock por lotes (FIFO), stock mínimo y costo de cada producto en cada almacén." :buttons="$buttons" />

        @if (request('filter.bajoMinimo'))
            <div class="mb-4 px-4 py-2.5 rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 text-xs text-amber-800 dark:text-amber-300 flex items-center justify-between gap-2">
                <span>Mostrando solo los productos que llegaron a su <strong>stock mínimo</strong>.</span>
                <a href="{{ route('product-warehouses.index') }}" class="font-bold underline shrink-0">Ver todo el stock</a>
            </div>
        @endif

        {{-- Filtros por almacén y producto (se combinan con el buscador de arriba) --}}
        <x-ui.filter-bar :action="route('product-warehouses.index')" :resetRoute="route('product-warehouses.index')" :collapsible="true" :fields="[
            [
                'type' => 'select',
                'name' => 'filter[currentWarehouse]',
                'label' => 'Almacén',
                'value' => request('filter.currentWarehouse'),
                'options' => ['' => 'Todos los almacenes'] + $warehouses->pluck('name', 'id')->toArray(),
            ],
            [
                'type' => 'select',
                'name' => 'filter[currentProduct]',
                'label' => 'Producto',
                'value' => request('filter.currentProduct'),
                'options' => ['' => 'Todos los productos'] + $products->pluck('name', 'id')->toArray(),
            ],
        ]">
            @if (request('filter.bajoMinimo'))
                <input type="hidden" name="filter[bajoMinimo]" value="1">
            @endif
        </x-ui.filter-bar>

        @php
            $columns = [
                ['key' => 'product.name', 'label' => 'Producto'],
                ['key' => 'warehouse.name', 'label' => 'Almacén'],
                ['key' => 'stock', 'label' => 'Stock'],
                ['key' => 'min_stock', 'label' => 'Stock Mínimo'],
                ['key' => 'avg_cost', 'label' => 'Costo Prom. (ref.)'],
            ];

            $actions = [
                ['type' => 'view', 'route' => 'product-warehouses.show'],
                ['type' => 'edit', 'route' => 'product-warehouses.edit'],
                ['type' => 'delete', 'route' => 'product-warehouses.destroy', 'confirm' => '¿Eliminar este registro de stock?'],
            ];
        @endphp

        <x-ui.data-table :rows="$productWarehouses" :columns="$columns" :actions="$actions" :paginator="$productWarehouses"
            rowKey="composite_key" emptyText="No hay registros de stock." />

        <x-ui.create-modal id="crud-modal" title="Registrar Producto en Almacén" :action="route('product-warehouses.store')"
            submitText="Guardar" :columns="2" :fields="[
                [
                    'type' => 'select',
                    'name' => 'product_id',
                    'label' => 'Producto',
                    'options' => $products->pluck('name', 'id')->toArray(),
                    'required' => true,
                ],
                [
                    'type' => 'select',
                    'name' => 'warehouse_id',
                    'label' => 'Almacén',
                    'options' => $warehouses->pluck('name', 'id')->toArray(),
                    'required' => true,
                ],
                [
                    'type' => 'number',
                    'name' => 'stock',
                    'label' => 'Stock Inicial (lote de apertura)',
                    'value' => 0,
                    'placeholder' => 'Ej. 20',
                ],
                [
                    'type' => 'number',
                    'name' => 'min_stock',
                    'label' => 'Stock Mínimo',
                    'value' => 0,
                    'placeholder' => 'Ej. 5',
                ],
                [
                    'type' => 'number',
                    'name' => 'avg_cost',
                    'label' => 'Costo Unitario del Stock Inicial',
                    'value' => 0,
                    'placeholder' => 'Ej. 35.00',
                    'fullWidth' => true,
                ],
            ]" />
    </div>

    @push('scripts')
        <script>
            const crudModal = document.getElementById('crud-modal');
            document.getElementById('modal-toggle-button')?.addEventListener('click', () => crudModal.classList.remove('hidden'));
            document.querySelectorAll('[data-modal-close="crud-modal"]').forEach(el => {
                el.addEventListener('click', () => crudModal.classList.add('hidden'));
            });
        </script>
    @endpush
</x-app-layout>
