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
        // En un kit: cuántos se pueden armar y, entre paréntesis, el stock de cada parte (en el listado solo va "N kits").
        ['label' => $kitParts ? 'Kits disponibles' : 'Stock Total', 'value' => $kitParts
            ? $product->total_stock.' kits ('.$kitParts->map(fn ($p) => $p['name'].' '.\App\Support\Bc::int($p['stock']))->implode(' · ').')'
            : $product->total_stock],
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

    @php
        $th = 'px-3 py-2 font-semibold';
        $baseUnit = $product->units->firstWhere('is_base', true);
        $baseName = $baseUnit->name ?? 'unidad';
        // El costo de compra solo lo ve quien puede editar el producto (igual que en el punto de venta).
        $canSeeCost = auth()->user()->can('update', $product);
        $canSeeMovements = auth()->user()->can('ver stock-movements');
        $qty = fn ($n) => \App\Support\Bc::trim($n);
        $bs = fn ($n) => number_format((float) \App\Support\Bc::round($n, 2), 2);
    @endphp

    {{-- Presentaciones: en qué unidades se vende y a cuánto equivale cada una --}}
    <div class="mt-6">
        <span class="block text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">
            Presentaciones ({{ $product->units->count() }})
        </span>
        @if ($product->units->isEmpty())
            <p class="text-xs text-gray-500 dark:text-gray-400">Este producto todavía no tiene presentaciones registradas.</p>
        @else
            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-slate-700">
                <table class="w-full text-xs">
                    <thead class="bg-gray-50 dark:bg-slate-800 text-gray-500 dark:text-gray-400">
                        <tr>
                            <th class="text-left {{ $th }}">Presentación</th>
                            <th class="text-left {{ $th }}">Unidad de medida</th>
                            <th class="text-right {{ $th }}">Equivale a</th>
                            <th class="text-right {{ $th }}">Precio</th>
                            <th class="text-left {{ $th }}">Código de barras</th>
                            @unless ($kitParts)
                                <th class="text-right {{ $th }}">Stock en esta presentación</th>
                            @endunless
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-slate-700/60">
                        @foreach ($product->units as $unit)
                            <tr>
                                <td class="px-3 py-2 text-gray-900 dark:text-white font-medium">
                                    {{ $unit->name }}
                                    @if ($unit->is_base)
                                        <span class="ml-1 px-1.5 py-0.5 rounded text-[10px] font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">base</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-gray-600 dark:text-gray-300">{{ $unit->unit->name ?? '—' }}</td>
                                <td class="px-3 py-2 text-right text-gray-600 dark:text-gray-300">{{ $unit->factor }} {{ $baseName }}</td>
                                <td class="px-3 py-2 text-right font-semibold text-gray-900 dark:text-white">Bs {{ $bs($unit->price) }}</td>
                                <td class="px-3 py-2 font-mono text-gray-600 dark:text-gray-300">{{ $unit->barcode ?? '—' }}</td>
                                @unless ($kitParts)
                                    <td class="px-3 py-2 text-right text-gray-600 dark:text-gray-300">{{ \App\Support\Bc::int(\App\Support\Bc::floor(\App\Support\Bc::div($product->total_stock, max(1, $unit->factor)))) }}</td>
                                @endunless
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @unless ($kitParts)
                <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-1.5">El stock se guarda en la presentación base ({{ $baseName }}); las demás muestran cuántas completas alcanzan.</p>
            @endunless
        @endif
    </div>

    {{-- Lotes: cada entrada de stock (compra, ajuste, devolución) con su costo; las ventas gastan primero el más antiguo --}}
    @unless ($kitParts)
        <div class="mt-6">
            <span class="block text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">
                Lotes con stock ({{ $activeBatches->count() }})
            </span>
            @if ($activeBatches->isEmpty() && $exhaustedBatches->isEmpty())
                <p class="text-xs text-gray-500 dark:text-gray-400">Este producto todavía no tiene lotes: se crean al registrar una compra o una entrada de stock.</p>
            @else
                <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-slate-700">
                    <table class="w-full text-xs">
                        <thead class="bg-gray-50 dark:bg-slate-800 text-gray-500 dark:text-gray-400">
                            <tr>
                                <th class="text-left {{ $th }}">#</th>
                                <th class="text-left {{ $th }}">Lote</th>
                                <th class="text-left {{ $th }}">Vence</th>
                                <th class="text-left {{ $th }}">Ingresó</th>
                                <th class="text-left {{ $th }}">Almacén</th>
                                <th class="text-left {{ $th }}">Origen</th>
                                <th class="text-right {{ $th }}">Entró</th>
                                <th class="text-right {{ $th }}">Queda</th>
                                @if ($canSeeCost)
                                    <th class="text-right {{ $th }}">Costo unit.</th>
                                    <th class="text-right {{ $th }}">Valor restante</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-slate-700/60">
                            @foreach ($activeBatches->concat($exhaustedBatches) as $batch)
                                @php
                                    $movement = $batch->stockMovementItem?->stockMovement;
                                    $exhausted = (float) $batch->qty_remaining <= 0;
                                @endphp
                                <tr class="{{ $exhausted ? 'text-gray-400 dark:text-gray-500' : ($loop->first ? 'bg-emerald-50/50 dark:bg-emerald-900/10' : '') }}">
                                    <td class="px-3 py-2 font-mono">{{ $batch->id }}</td>
                                    <td class="px-3 py-2 font-mono">{{ $batch->lot_number ?? '—' }}</td>
                                    <td class="px-3 py-2 whitespace-nowrap">
                                        @if ($batch->expires_at)
                                            {{ $batch->expires_at->format('d/m/Y') }}
                                            @if (! $exhausted && $batch->isExpired())
                                                <span class="ml-1 px-1.5 py-0.5 rounded text-[10px] font-semibold bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-400">vencido</span>
                                            @elseif (! $exhausted && $batch->expiresSoon())
                                                <span class="ml-1 px-1.5 py-0.5 rounded text-[10px] font-semibold bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">vence en {{ (int) today()->diffInDays($batch->expires_at) }} días</span>
                                            @endif
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 whitespace-nowrap">{{ $batch->received_at->format('d/m/Y H:i') }}</td>
                                    <td class="px-3 py-2">{{ $batch->warehouse->name ?? '—' }}</td>
                                    <td class="px-3 py-2">
                                        @if ($movement && $canSeeMovements)
                                            <a href="{{ route('stock-movements.show', $movement) }}" class="text-emerald-700 dark:text-emerald-400 hover:underline">
                                                {{ $movement->type->name ?? 'Movimiento' }} #{{ $movement->id }}
                                            </a>
                                        @else
                                            {{ $movement ? ($movement->type->name ?? 'Movimiento').' #'.$movement->id : '—' }}
                                        @endif
                                        @if ($movement?->note)
                                            <span class="block text-[10px] text-gray-500 dark:text-gray-400 truncate max-w-[16rem]">{{ $movement->note }}</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-right">{{ $qty($batch->qty_initial) }}</td>
                                    <td class="px-3 py-2 text-right font-semibold {{ $exhausted ? '' : 'text-gray-900 dark:text-white' }}">
                                        {{ $exhausted ? 'Agotado' : $qty($batch->qty_remaining) }}
                                    </td>
                                    @if ($canSeeCost)
                                        <td class="px-3 py-2 text-right">Bs {{ $bs($batch->unit_cost) }}</td>
                                        <td class="px-3 py-2 text-right">Bs {{ $bs(\App\Support\Bc::mul($batch->qty_remaining, $batch->unit_cost)) }}</td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-1.5">
                    Cantidades en {{ $baseName }}. Las ventas descuentan primero del lote que vence antes en cada almacén (los que no tienen vencimiento, por antigüedad); el primero está resaltado.
                    @if ($exhaustedBatches->isNotEmpty())
                        En gris, los últimos {{ $exhaustedBatches->count() }} lotes ya agotados.
                    @endif
                </p>
            @endif
        </div>
    @endunless
</x-ui.show-page>

<x-ui.image-preview-modal />
