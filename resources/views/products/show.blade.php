@php
    $kitParts = $product->kitBreakdown();

    $items = [
        ['label' => 'SKU', 'value' => $product->sku, 'mono' => true],
        ['label' => 'Nombre', 'value' => $product->name],
        ['label' => 'Categoría', 'value' => $product->category->name ?? '—'],
        ['label' => 'Marca', 'value' => $product->marca ?? '—'],
        ['label' => 'Color', 'value' => $product->color ?? '—'],
        ['label' => 'Medida', 'value' => $product->medida ?? '—'],
        ['label' => 'Estado', 'value' => $product->is_active ? 'Activo' : 'Inactivo'],
        ['label' => $kitParts ? 'Kits disponibles' : 'Stock Total', 'value' => $product->total_stock],
        ['label' => 'Descripción', 'value' => $product->description ?? '—', 'fullWidth' => true],
    ];
@endphp

<x-ui.show-page title="Detalle de Producto" category="Inventario" :backRoute="route('products.index')"
    :editRoute="auth()->user()->can('update', $product) ? route('products.edit', $product) : ''" :items="$items" :columns="2">
    @if ($product->photo)
        <div class="mb-6">
            <span class="block text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">Foto</span>
            <div class="relative group inline-block cursor-pointer" onclick="openImagePreview('{{ $product->photo_url }}', '{{ addslashes($product->name) }}')">
                <img src="{{ $product->photo_url }}" alt="{{ $product->name }}"
                    class="w-44 h-44 object-cover rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-slate-800 p-1 shadow-sm group-hover:ring-2 group-hover:ring-emerald-500 transition-all">
                <div class="absolute bottom-2 right-2 px-2.5 py-1 rounded-lg bg-slate-900/80 text-white text-xs font-semibold backdrop-blur-sm flex items-center gap-1 shadow transition pointer-events-none">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m3-3H7" />
                    </svg>
                    Ver más grande
                </div>
            </div>
        </div>
    @endif

    @if ($kitParts)
        <div class="mb-6">
            <span class="block text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">
                Partes del kit
            </span>
            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-slate-700">
                <table class="w-full text-xs">
                    <thead class="bg-gray-50 dark:bg-slate-800 text-gray-500 dark:text-gray-400">
                        <tr>
                            <th class="text-left px-3 py-2 font-semibold">Parte</th>
                            <th class="text-right px-3 py-2 font-semibold">Lleva 1 kit</th>
                            <th class="text-right px-3 py-2 font-semibold">En stock</th>
                            <th class="text-right px-3 py-2 font-semibold">Alcanza para</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-slate-700/60">
                        @foreach ($kitParts as $part)
                            <tr class="{{ $part['limits'] ? 'bg-amber-50/60 dark:bg-amber-900/10' : '' }}">
                                <td class="px-3 py-2 text-gray-900 dark:text-white font-medium">
                                    {{ $part['name'] }}
                                    @if ($part['limits'])
                                        <span class="ml-1 text-[10px] font-semibold text-amber-700 dark:text-amber-400">limita</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-right text-gray-600 dark:text-gray-300">{{ \App\Support\Bc::trim($part['required']) }}</td>
                                <td class="px-3 py-2 text-right text-gray-600 dark:text-gray-300">{{ \App\Support\Bc::trim($part['stock']) }}</td>
                                <td class="px-3 py-2 text-right font-semibold text-gray-900 dark:text-white">{{ \App\Support\Bc::trim($part['kits']) }} kits</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-1.5">El kit no tiene stock propio: al venderlo se descuenta de cada parte, y alcanza para la que menos tenga.</p>
        </div>
    @elseif ($product->warehouses->isNotEmpty())
        <div>
            <span class="block text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">
                Stock por Almacén
            </span>
            <div class="flex flex-wrap gap-2">
                @foreach ($product->warehouses as $warehouse)
                    <span
                        class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-sky-50 text-sky-700 dark:bg-sky-900/30 dark:text-sky-400">
                        {{ $warehouse->name }}: {{ $warehouse->pivot->stock }}
                    </span>
                @endforeach
            </div>
        </div>
    @endif
</x-ui.show-page>

<x-ui.image-preview-modal />
