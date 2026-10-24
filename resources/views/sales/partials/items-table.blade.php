{{-- Productos de la venta. Recibe: $order --}}
@php
    // Si la venta cruzó más de un almacén, se muestra de cuál salió cada línea; si todas comparten
    // el mismo, se omite la columna para no ensuciar un recibo simple.
    $multiWarehouse = $order->items->pluck('warehouse_id')->unique()->count() > 1;
@endphp

{{-- Tablas de la venta (esta y la de cobros): en pantallas chicas (< 768px) cada fila se ve como
     tarjeta, con la etiqueta de cada dato a la izquierda, en vez de forzar scroll horizontal. --}}
<style>
    @media (max-width: 767px) {
        .rtable thead { display: none; }
        .rtable, .rtable tbody, .rtable tr, .rtable td { display: block; width: 100%; }
        .rtable tr { padding: 10px 12px; border-bottom: 1px solid rgb(243 244 246); }
        .dark .rtable tr { border-bottom-color: rgb(30 41 59); }
        .rtable td { display: flex !important; justify-content: space-between; align-items: center; gap: 12px; text-align: right !important; padding: 3px 0 !important; border: none !important; }
        .rtable td::before { content: attr(data-label); font-weight: 700; text-transform: uppercase; font-size: 9.5px; letter-spacing: .03em; color: #9ca3af; text-align: left; }
        .rtable td:empty::before { content: none; }
        .rtable tr.rtable-summary td { display: block !important; text-align: right !important; }
        .rtable tr.rtable-summary td::before { content: none; }
    }
</style>

<div class="mb-6">
    <span class="block text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">
        Productos ({{ $order->items->count() }})
    </span>
    <div class="overflow-x-auto border border-gray-100 dark:border-gray-800 rounded-lg">
        <table class="rtable w-full text-xs">
            <thead class="bg-gray-50 dark:bg-slate-800/60 text-gray-500 dark:text-gray-400 uppercase">
                <tr>
                    <th class="px-3 py-2 text-left w-16">Foto</th>
                    <th class="px-3 py-2 text-left">Producto</th>
                    <th class="px-3 py-2 text-left">Unidad</th>
                    @if ($multiWarehouse)
                        <th class="px-3 py-2 text-left">Almacén</th>
                    @endif
                    <th class="px-3 py-2 text-right">Cantidad</th>
                    <th class="px-3 py-2 text-right">Precio</th>
                    <th class="px-3 py-2 text-right">Descuento</th>
                    <th class="px-3 py-2 text-right">Subtotal</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @foreach ($order->items as $item)
                    <tr>
                        <td class="px-3 py-2" data-label="Foto">
                            @if ($item->product && $item->product->photo_url)
                                <div class="relative group/saleimg cursor-zoom-in inline-block" onclick="openImagePreview('{{ $item->product->photo_url }}', '{{ addslashes($item->product->name) }}')" title="Clic para ampliar imagen">
                                    <img src="{{ $item->product->photo_url }}" alt="{{ $item->product->name }}"
                                        class="w-14 h-14 object-cover rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-slate-800 shadow-sm group-hover/saleimg:ring-2 group-hover/saleimg:ring-emerald-500 group-hover/saleimg:scale-105 transition-all shrink-0">
                                    <div class="absolute inset-0 bg-black/25 opacity-0 group-hover/saleimg:opacity-100 rounded-xl flex items-center justify-center transition-opacity">
                                        <svg class="w-4 h-4 text-white drop-shadow" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m3-3H7" />
                                        </svg>
                                    </div>
                                </div>
                            @else
                                <div class="w-14 h-14 rounded-xl border border-dashed border-gray-300 dark:border-slate-700 bg-gray-50 dark:bg-slate-800 flex items-center justify-center text-gray-400 shrink-0">
                                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                            @endif
                        </td>
                        <td class="px-3 py-2 text-gray-800 dark:text-gray-200 font-medium" data-label="Producto">{{ $item->product->name }}</td>
                        <td class="px-3 py-2 text-gray-800 dark:text-gray-200" data-label="Unidad">{{ $item->productUnit->name }}</td>
                        @if ($multiWarehouse)
                            <td class="px-3 py-2 text-gray-800 dark:text-gray-200" data-label="Almacén">{{ $item->warehouse->name ?? '—' }}</td>
                        @endif
                        <td class="px-3 py-2 text-right text-gray-800 dark:text-gray-200" data-label="Cantidad">{{ (float) $item->quantity }}</td>
                        <td class="px-3 py-2 text-right text-gray-800 dark:text-gray-200" data-label="Precio">{{ number_format((float) $item->unit_price, 2) }}</td>
                        <td class="px-3 py-2 text-right text-gray-800 dark:text-gray-200" data-label="Descuento">{{ (float) $item->discount > 0 ? '− '.number_format((float) $item->discount, 2) : '—' }}</td>
                        <td class="px-3 py-2 text-right font-medium text-gray-900 dark:text-white" data-label="Subtotal">{{ number_format((float) $item->subtotal, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-gray-50 dark:bg-slate-800/60">
                <tr class="rtable-summary">
                    <td colspan="{{ $multiWarehouse ? 7 : 6 }}" class="px-3 py-2 text-right font-bold uppercase text-gray-600 dark:text-gray-300">Total</td>
                    <td class="px-3 py-2 text-right font-bold text-gray-900 dark:text-white">{{ $order->amount_label }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<x-ui.image-preview-modal />
