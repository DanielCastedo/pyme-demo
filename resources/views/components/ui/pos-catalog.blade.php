@props([
    'gridCols' => 'grid-cols-3 sm:grid-cols-4 xl:grid-cols-5',
])

<div id="catalog-column" class="hidden md:col-span-4 md:h-full md:overflow-y-auto space-y-4">
    <div id="product-grid" class="grid {{ $gridCols }} gap-3">
        {{-- se llena por JS --}}
    </div>

    <div id="grid-empty" class="hidden text-center py-16 text-sm text-gray-500 dark:text-gray-400">
        No se encontraron productos con esa búsqueda.
    </div>

    <div id="grid-pagination" class="hidden flex items-center justify-between pt-2">
        <button type="button" id="prev-page-btn"
            class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium rounded-lg border border-gray-300 dark:border-slate-600 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-800 disabled:opacity-40 disabled:cursor-not-allowed">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            Anterior
        </button>
        <span id="page-indicator" class="text-xs text-gray-500 dark:text-gray-400"></span>
        <button type="button" id="next-page-btn"
            class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium rounded-lg border border-gray-300 dark:border-slate-600 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-800 disabled:opacity-40 disabled:cursor-not-allowed">
            Siguiente
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
        </button>
    </div>
</div>