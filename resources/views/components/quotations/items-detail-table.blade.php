@props([
    'items' => [],
    'amount' => 0,
])

{{-- En pantallas chicas (< 768px) cada fila se ve como tarjeta, con la etiqueta de cada dato a la
     izquierda, en vez de forzar scroll horizontal (mismo patrón que las tablas de venta). --}}
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

<div class="overflow-x-auto border border-gray-100 dark:border-gray-800 rounded-lg">
    <table class="rtable w-full text-xs">
        <thead class="bg-gray-50 dark:bg-slate-800/60 text-gray-500 dark:text-gray-400 uppercase">
            <tr>
                <th class="px-3 py-2.5 text-left">N°</th>
                <th class="px-3 py-2.5 text-left">Producto</th>
                <th class="px-3 py-2.5 text-left">Unidad</th>
                <th class="px-3 py-2.5 text-center">Cantidad</th>
                <th class="px-3 py-2.5 text-right">Precio Unitario</th>
                <th class="px-3 py-2.5 text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
            @foreach ($items as $index => $item)
                <tr>
                    <td class="px-3 py-2.5 text-gray-400" data-label="N°">{{ $index + 1 }}</td>
                    <td class="px-3 py-2.5 text-gray-800 dark:text-gray-200 font-medium" data-label="Producto">
                        {{ $item->product->name ?? '—' }}
                        <div class="text-[10px] text-gray-400 font-mono">SKU: {{ $item->product->sku ?? '—' }}</div>
                    </td>
                    <td class="px-3 py-2.5 text-gray-700 dark:text-gray-300" data-label="Unidad">
                        {{ $item->productUnit->name ?? 'Unidad' }}
                    </td>
                    <td class="px-3 py-2.5 text-center font-semibold text-gray-900 dark:text-white" data-label="Cantidad">
                        {{ number_format((float) $item->quantity, 0) }}
                    </td>
                    <td class="px-3 py-2.5 text-right text-gray-700 dark:text-gray-300 font-mono" data-label="Precio Unitario">
                        Bs. {{ number_format((float) $item->unit_price, 2) }}
                    </td>
                    <td class="px-3 py-2.5 text-right font-mono font-bold text-gray-900 dark:text-white" data-label="Subtotal">
                        Bs. {{ number_format((float) $item->subtotal, 2) }}
                    </td>
                </tr>
            @endforeach
        </tbody>
        <tfoot class="bg-gray-50 dark:bg-slate-800/40 border-t border-gray-200 dark:border-gray-700">
            <tr class="rtable-summary">
                <td colspan="5" class="px-3 py-3 text-right font-bold text-gray-700 dark:text-gray-300 text-xs uppercase">
                    Total:
                </td>
                <td class="px-3 py-3 text-right font-mono font-extrabold text-sm text-emerald-600 dark:text-emerald-400">
                    Bs. {{ number_format((float) $amount, 2) }}
                </td>
            </tr>
        </tfoot>
    </table>
</div>
