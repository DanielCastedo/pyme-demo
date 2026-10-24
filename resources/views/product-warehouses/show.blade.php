@php
    $items = [
        ['label' => 'Producto', 'value' => $productWarehouse->product->name],
        ['label' => 'Almacén', 'value' => $productWarehouse->warehouse->name],
        ['label' => 'Stock', 'value' => $productWarehouse->stock],
        ['label' => 'Stock Mínimo', 'value' => $productWarehouse->min_stock],
        ['label' => 'Costo Promedio (referencia)', 'value' => number_format((float) $productWarehouse->avg_cost, 2)],
        ['label' => 'Estado', 'value' => $productWarehouse->hasLowStock() ? '⚠ Bajo stock' : 'Stock normal'],
    ];
@endphp

<x-ui.show-page title="Detalle de Stock por Almacén" category="Inventario" :backRoute="route('product-warehouses.index')"
    :editRoute="auth()->user()->can('update', $productWarehouse) ? route('product-warehouses.edit', $productWarehouse->composite_key) : ''" :items="$items" :columns="2">
    <div>
        <span class="block text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">
            Lotes disponibles ({{ $batches->count() }}) — salen en orden FIFO (el más antiguo primero)
        </span>
        <div class="overflow-x-auto border border-gray-100 dark:border-gray-800 rounded-lg">
            <table class="w-full text-xs">
                <thead class="bg-gray-50 dark:bg-slate-800/60 text-gray-500 dark:text-gray-400 uppercase">
                    <tr>
                        <th class="px-3 py-2 text-left">#</th>
                        <th class="px-3 py-2 text-left">Ingreso</th>
                        <th class="px-3 py-2 text-left">Costo Unit.</th>
                        <th class="px-3 py-2 text-left">Cant. Inicial</th>
                        <th class="px-3 py-2 text-left">Disponible</th>
                        <th class="px-3 py-2 text-left">Valor</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($batches as $batch)
                        <tr>
                            <td class="px-3 py-2 text-gray-800 dark:text-gray-200">{{ $loop->iteration }}</td>
                            <td class="px-3 py-2 text-gray-800 dark:text-gray-200">
                                @php
                                    $batchDate = $batch->received_at;
                                    if ($batchDate && $batchDate->format('H:i') === '00:00' && $batch->created_at && $batch->created_at->format('H:i') !== '00:00') {
                                        $batchDate = $batchDate->copy()->setTimeFrom($batch->created_at);
                                    }
                                @endphp
                                {{ $batchDate ? $batchDate->format('d/m/Y H:i') : '—' }}
                            </td>
                            <td class="px-3 py-2 text-gray-800 dark:text-gray-200">{{ number_format((float) $batch->unit_cost, 2) }}</td>
                            <td class="px-3 py-2 text-gray-800 dark:text-gray-200">{{ (float) $batch->qty_initial }}</td>
                            <td class="px-3 py-2 text-gray-800 dark:text-gray-200">{{ (float) $batch->qty_remaining }}</td>
                            <td class="px-3 py-2 text-gray-800 dark:text-gray-200">{{ number_format((float) $batch->qty_remaining * (float) $batch->unit_cost, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-3 py-4 text-center text-gray-400">Sin lotes con stock.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-ui.show-page>
