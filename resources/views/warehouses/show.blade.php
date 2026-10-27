@php
    $items = [
        ['label' => 'Nombre', 'value' => $warehouse->name],
        ['label' => 'Código', 'value' => $warehouse->code, 'mono' => true],
        ['label' => 'Sucursal dueña', 'value' => $warehouse->branch->name ?? '—'],
        ['label' => 'Sucursales que lo usan', 'value' => $warehouse->branch_names],
        ['label' => 'Estado', 'value' => $warehouse->is_active ? 'Activo' : 'Inactivo'],
    ];
@endphp

<x-ui.show-page title="Detalle de Almacén" category="Inventario" :backRoute="route('warehouses.index')"
    :editRoute="auth()->user()->can('update', $warehouse) ? route('warehouses.edit', $warehouse) : ''" :items="$items" :columns="2">
    @if ($products->isNotEmpty())
        <div>
            <span class="block text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">
                Productos en este Almacén ({{ $products->total() }})
            </span>
            <div class="flex flex-wrap gap-2">
                @foreach ($products as $product)
                    <span
                        class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-sky-50 text-sky-700 dark:bg-sky-900/30 dark:text-sky-400">
                        {{ $product->name }}: {{ $product->pivot->stock }}
                    </span>
                @endforeach
            </div>

            @if ($products->hasPages())
                <div class="flex items-center justify-between mt-4">
                    @if ($products->onFirstPage())
                        <span class="px-3 py-1.5 text-xs text-gray-400 border border-gray-200 rounded-lg">Anterior</span>
                    @else
                        <a href="{{ $products->previousPageUrl() }}" class="px-3 py-1.5 text-xs font-medium text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 dark:border-slate-600 dark:text-gray-300 dark:hover:bg-slate-800">Anterior</a>
                    @endif
                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ $products->currentPage() }} / {{ $products->lastPage() }}</span>
                    @if ($products->hasMorePages())
                        <a href="{{ $products->nextPageUrl() }}" class="px-3 py-1.5 text-xs font-medium text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 dark:border-slate-600 dark:text-gray-300 dark:hover:bg-slate-800">Siguiente</a>
                    @else
                        <span class="px-3 py-1.5 text-xs text-gray-400 border border-gray-200 rounded-lg">Siguiente</span>
                    @endif
                </div>
            @endif
        </div>
    @endif
</x-ui.show-page>
