<x-app-layout>
    <div class="max-w-10xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <x-ui.page-header category="Inventario" title="Stock por Sucursal"
            description="Todo tu inventario junto: dónde está cada producto, cuánto hay y cuánto vale a costo real, por sucursal y almacén." />

        <x-ui.filter-bar :action="route('stock-overview.index')" :resetRoute="route('stock-overview.index')" :collapsible="true" :fields="[
            ['type' => 'select', 'name' => 'branch', 'label' => 'Sucursal', 'value' => $f['branch'], 'options' => ['' => 'Todas las sucursales'] + $branches->toArray()],
            ['type' => 'select', 'name' => 'warehouse', 'label' => 'Almacén', 'value' => $f['warehouse'], 'options' => ['' => 'Todos los almacenes'] + $warehouses->toArray()],
            ['type' => 'select', 'name' => 'product', 'label' => 'Producto', 'value' => $f['product'], 'options' => ['' => 'Todos los productos'] + $products->toArray()],
            ['type' => 'select', 'name' => 'category', 'label' => 'Categoría', 'value' => $f['category'], 'options' => ['' => 'Todas las categorías'] + $categories->toArray()],
            ['type' => 'select', 'name' => 'status', 'label' => 'Estado del stock', 'value' => $f['status'], 'options' => ['' => 'Todos'] + $statuses],
        ]" />

        {{-- Totales de lo filtrado --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
            <x-ui.stat-card label="Productos distintos" :value="number_format((int) $totals->products)" color="sky" />
            <x-ui.stat-card label="Unidades en stock" :value="number_format((int) $totals->units)" color="violet" />
            <x-ui.stat-card label="Valor del inventario (costo)" :value="number_format((float) $totals->value, 2)" color="emerald" />
            <x-ui.stat-card label="En stock mínimo o menos" :value="number_format($alerts)" :color="$alerts > 0 ? 'red' : 'gray'" />
        </div>

        {{-- Resumen por sucursal --}}
        <h2 class="text-sm font-bold text-gray-900 dark:text-white mb-2">Por sucursal</h2>
        <div class="mb-8">
            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-slate-900">
                <table class="w-full text-xs text-left">
                    <thead class="uppercase bg-gray-200 dark:bg-slate-800/60 text-gray-500 dark:text-gray-400">
                        <tr>
                            <th class="px-3 py-2 font-semibold">Sucursal</th>
                            <th class="px-3 py-2 font-semibold text-right">Almacenes</th>
                            <th class="px-3 py-2 font-semibold text-right">Productos</th>
                            <th class="px-3 py-2 font-semibold text-right">Unidades</th>
                            <th class="px-3 py-2 font-semibold text-right">Valor a costo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800 text-gray-800 dark:text-gray-200">
                        @forelse ($byBranch as $b)
                            <tr>
                                <td class="px-3 py-2 font-semibold">{{ $b->branch }}</td>
                                <td class="px-3 py-2 text-right">{{ $b->warehouses }}</td>
                                <td class="px-3 py-2 text-right">{{ $b->products }}</td>
                                <td class="px-3 py-2 text-right">{{ number_format((int) $b->units) }}</td>
                                <td class="px-3 py-2 text-right font-semibold">{{ number_format((float) $b->value, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-3 py-6 text-center text-gray-400">No hay stock con estos filtros.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Detalle por almacén y producto --}}
        <h2 class="text-sm font-bold text-gray-900 dark:text-white mb-2">Detalle por almacén ({{ $rows->total() }})</h2>
        <x-ui.data-table :rows="$rows" :paginator="$rows" rowKey="composite_key"
            :columns="[
                ['key' => 'branch', 'label' => 'Sucursal'],
                ['key' => 'warehouse', 'label' => 'Almacén'],
                ['key' => 'product', 'label' => 'Producto'],
                ['key' => 'sku', 'label' => 'SKU'],
                ['key' => 'category', 'label' => 'Categoría'],
                ['key' => 'stock', 'label' => 'Stock'],
                ['key' => 'min_stock', 'label' => 'Mínimo'],
                ['key' => 'avg_cost_label', 'label' => 'Costo prom.'],
                ['key' => 'value_label', 'label' => 'Valor'],
                ['key' => 'status_label', 'label' => 'Estado'],
            ]"
            :actions="[['type' => 'view', 'route' => 'product-warehouses.show']]"
            emptyText="No hay stock con estos filtros." />
    </div>
</x-app-layout>
