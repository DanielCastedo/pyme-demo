@props([
    'placeholder' => 'Buscar productos por nombre o código...',
])

<div
    class="sticky top-[64px] z-30 md:static md:z-auto bg-white dark:bg-slate-900 border border-gray-200 dark:border-gray-700 rounded-xl p-3 mb-4 relative shrink-0 shadow-sm md:shadow-none">
    <div class="flex items-center gap-3">
        <svg class="w-5 h-5 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <input type="text" id="product-search" autocomplete="off" placeholder="{{ $placeholder }}"
            class="w-full bg-transparent border-0 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:ring-0 focus:outline-none">

        <button type="button" id="pos-scan-btn" title="Escanear código con la cámara"
            class="shrink-0 w-9 h-9 flex items-center justify-center rounded-lg border border-sky-300 dark:border-sky-700 text-sky-600 dark:text-sky-400 hover:bg-sky-50 dark:hover:bg-sky-900/20 transition">
            <svg class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 13a3 3 0 100 6 3 3 0 000-6z" />
            </svg>
        </button>

        <button type="button" id="toggle-catalog-btn"
            class="hidden md:inline-flex shrink-0 px-3 py-1.5 text-xs font-medium rounded-lg border border-gray-300 dark:border-slate-600 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-800">
            Ver Catálogo
        </button>
    </div>

    {{-- Dropdown de resultados: solo se usa en móvil --}}
    <div id="mobile-search-results"
        class="md:hidden hidden absolute left-0 right-0 top-full mt-1 z-30 mx-3 max-h-80 overflow-y-auto rounded-lg border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-lg">
    </div>
</div>

{{-- Modal del lector de código para el POS --}}
<div id="pos-scanner-modal" tabindex="-1" aria-hidden="true"
    class="hidden fixed inset-0 z-[60] bg-slate-900/70 backdrop-blur-sm p-4 flex items-center justify-center">
    <div
        class="w-full max-w-sm bg-white dark:bg-slate-900 rounded-2xl border border-gray-200 dark:border-slate-700 shadow-2xl overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 dark:border-slate-800 flex items-center justify-between">
            <h3 class="text-sm font-bold text-gray-900 dark:text-white">Escanear Producto</h3>
            <button type="button" id="pos-scanner-close" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="p-4">
            <div id="pos-reader" class="w-full rounded-lg overflow-hidden"></div>
            <div id="pos-scan-feedback" class="hidden mt-3 p-2.5 rounded-lg text-xs font-medium text-center"></div>
            <p class="text-[11px] text-gray-500 dark:text-gray-400 text-center mt-3">
                Apunta la cámara al código de barras o QR del producto.
            </p>
        </div>
    </div>
</div>

<div id="grid-error"
    class="hidden text-center py-4 text-sm text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl px-4 mb-4 shrink-0">
</div>
