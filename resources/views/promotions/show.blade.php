@php
    $items = [
        ['label' => 'Nombre', 'value' => $promotion->name],
        ['label' => 'Valor del Descuento', 'value' => $promotion->value],
        ['label' => 'Cantidad Mínima', 'value' => $promotion->min_quantity],
        ['label' => 'Estado', 'value' => $promotion->is_active ? 'Activa' : 'Inactiva'],
        ['label' => 'Fecha de Inicio', 'value' => $promotion->start_date->format('d/m/Y')],
        ['label' => 'Fecha de Fin', 'value' => $promotion->end_date->format('d/m/Y')],
        ['label' => 'Vigente Actualmente', 'value' => $promotion->isCurrentlyActive() ? 'Sí' : 'No'],
    ];
@endphp

<x-ui.show-page title="Detalle de Promoción" category="Inventario" :backRoute="route('promotions.index')"
    :editRoute="auth()->user()->can('update', $promotion) ? route('promotions.edit', $promotion) : ''" :items="$items" :columns="2">
    <div>
        <span class="block text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">
            Productos Incluidos ({{ $promotion->products->count() }})
        </span>
        @if ($promotion->products->isNotEmpty())
            <div class="flex flex-wrap gap-2">
                @foreach ($promotion->products as $product)
                    <span
                        class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">
                        {{ $product->name }}
                    </span>
                @endforeach
            </div>
        @else
            <p class="text-sm text-gray-500 dark:text-gray-400 italic">Sin productos asignados.</p>
        @endif
    </div>
</x-ui.show-page>
