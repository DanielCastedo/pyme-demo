@if ($visible)
    <div class="inline-flex sm:relative" x-data="{ open: false }">
        <button type="button"
            class="relative flex items-center justify-center w-8 h-8 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600/80 rounded-full"
            :class="{ 'bg-slate-200 dark:bg-slate-600': open }"
            aria-haspopup="true" :aria-expanded="open" @click.prevent="open = !open"
            title="{{ $total > 0 ? $total.' producto(s) en stock mínimo' : 'Sin alertas de stock' }}">
            <span class="sr-only">Notificaciones de stock mínimo</span>
            <svg class="w-4 h-4 text-slate-500 dark:text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
            @if ($total > 0)
                <span class="absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 flex items-center justify-center rounded-full bg-red-500 text-white text-[10px] font-bold leading-none ring-2 ring-white dark:ring-slate-900">
                    {{ $total > 99 ? '99+' : $total }}
                </span>
            @endif
        </button>

        {{-- Celular: el panel se ancla al encabezado (no a la campana) y ocupa su ancho, justo debajo. Desde sm: desplegable junto a la campana. --}}
        <div class="origin-top-right z-20 absolute left-3 right-3 top-full mt-1 sm:left-auto sm:right-0 sm:mt-2 sm:w-80 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg shadow-lg overflow-hidden"
            @click.outside="open = false" @keydown.escape.window="open = false" x-show="open"
            x-transition:enter="transition ease-out duration-200 transform" x-transition:enter-start="opacity-0 -translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-out duration-150"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" x-cloak>

            <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between gap-2">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Stock mínimo</span>
                <span class="flex items-center gap-2">
                    @if ($total > 0)
                        <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400">{{ $total }} alerta(s)</span>
                    @endif
                    {{-- Cerrar la notificación --}}
                    <button type="button" @click="open = false" title="Cerrar" aria-label="Cerrar notificaciones"
                        class="w-7 h-7 -mr-1 inline-flex items-center justify-center rounded-md text-slate-400 hover:text-slate-600 hover:bg-slate-100 dark:hover:text-slate-200 dark:hover:bg-slate-700 transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </span>
            </div>

            <div class="max-h-[65vh] sm:max-h-96 overflow-y-auto overscroll-contain">
            @forelse ($items as $item)
                @php
                    $empty = (int) $item->stock <= 0;
                @endphp
                <a href="{{ route('product-warehouses.show', $item->product_id.'-'.$item->warehouse_id) }}"
                    class="flex items-start gap-3 px-4 py-3 sm:py-2.5 border-b border-slate-100 dark:border-slate-700/60 hover:bg-slate-50 dark:hover:bg-slate-700/40">
                    <span class="mt-1 w-2 h-2 shrink-0 rounded-full {{ $empty ? 'bg-red-500' : 'bg-amber-500' }}"></span>
                    <span class="min-w-0 flex-1">
                        <span class="block text-sm font-semibold text-slate-800 dark:text-slate-100 truncate">{{ $item->product }}</span>
                        <span class="block text-[11px] text-slate-500 dark:text-slate-400 truncate">{{ $item->warehouse }}</span>
                    </span>
                    <span class="text-right shrink-0">
                        <span class="block text-xs font-bold {{ $empty ? 'text-red-600 dark:text-red-400' : 'text-amber-600 dark:text-amber-400' }}">
                            {{ $empty ? 'Sin stock' : 'Stock '.(int) $item->stock }}
                        </span>
                        <span class="block text-[10px] text-slate-400">mínimo {{ (int) $item->min_stock }}</span>
                    </span>
                </a>
            @empty
                <div class="px-4 py-6 text-center text-xs text-slate-500 dark:text-slate-400">
                    Todo en orden: ningún producto llegó a su stock mínimo.
                </div>
            @endforelse
            </div>

            @if ($total > 0)
                <a href="{{ route('product-warehouses.index', ['filter[bajoMinimo]' => 1]) }}"
                    class="block px-4 py-3 sm:py-2.5 text-center text-xs font-semibold text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 hover:bg-slate-50 dark:hover:bg-slate-700/40">
                    Ver {{ $total > $items->count() ? 'las '.$total : 'todas' }} en Stock por Almacén
                </a>
            @endif
        </div>
    </div>
@endif
