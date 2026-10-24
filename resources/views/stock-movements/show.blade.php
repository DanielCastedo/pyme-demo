@php
    $items = [
        ['label' => 'Tipo', 'value' => $stockMovement->type->name],
        ['label' => 'Almacén', 'value' => $stockMovement->warehouse->name],
        ['label' => 'Fecha del Movimiento', 'value' => $stockMovement->date->format('d/m/Y')],
        ['label' => 'Registrado', 'value' => $stockMovement->date_label],
        ['label' => 'Usuario', 'value' => $stockMovement->user->name],
        ['label' => 'Nota', 'value' => $stockMovement->note ?? '—', 'fullWidth' => true],
    ];
@endphp

<x-ui.show-page title="Detalle de Movimiento de Stock" category="Inventario" :backRoute="route('stock-movements.index')"
    :editRoute="auth()->user()->can('update', $stockMovement) ? route('stock-movements.edit', $stockMovement) : ''" :items="$items" :columns="2">
    <div>
        <span class="block text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">
            Productos ({{ $stockMovement->items->count() }})
        </span>
        <div class="overflow-x-auto border border-gray-100 dark:border-gray-800 rounded-lg">
            <table class="w-full text-xs">
                <thead class="bg-gray-50 dark:bg-slate-800/60 text-gray-500 dark:text-gray-400 uppercase">
                    <tr>
                        <th class="px-3 py-2 text-left">Producto</th>
                        <th class="px-3 py-2 text-left">Unidad</th>
                        <th class="px-3 py-2 text-left">Cantidad</th>
                        <th class="px-3 py-2 text-left">Cant. Base</th>
                        <th class="px-3 py-2 text-left">Costo Unit. (salidas: FIFO)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach ($stockMovement->items as $item)
                        <tr>
                            <td class="px-3 py-2 text-gray-800 dark:text-gray-200">{{ $item->product->name }}</td>
                            <td class="px-3 py-2 text-gray-800 dark:text-gray-200">{{ $item->productUnit->name }}</td>
                            <td class="px-3 py-2 text-gray-800 dark:text-gray-200">{{ $item->quantity }}</td>
                            <td class="px-3 py-2 text-gray-800 dark:text-gray-200">{{ $item->base_quantity }}</td>
                            <td class="px-3 py-2 text-gray-800 dark:text-gray-200">
                                {{ $item->unit_cost !== null ? number_format((float) $item->unit_cost, 2) : '—' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-ui.show-page>
