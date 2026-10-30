@props([
    'action',
    'company' => null,
    'paymentOptions' => [
        'efectivo' => 'Efectivo',
        'qr' => 'QR',
        'tarjeta' => 'Tarjeta',
        'transferencia' => 'Transferencia',
        'credito' => 'Crédito',
        'otro' => 'Otro',
    ],
])

<div id="ticket-column" class="md:col-span-3 bg-white dark:bg-slate-900 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden flex flex-col md:h-full md:min-h-0"
    x-data="{ showSupplierFields: true }">

    <div class="px-4 py-2 border-b border-gray-100 dark:border-slate-800 shrink-0 flex items-center justify-between">
        <h3 class="text-xs font-bold text-gray-900 dark:text-white">Orden de Compra</h3>
        <button type="button" @click="showSupplierFields = !showSupplierFields"
            class="inline-flex items-center gap-1 text-[10px] font-medium text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition">
            <span x-text="showSupplierFields ? 'Ocultar' : 'Mostrar'"></span>
        </button>
    </div>

    <form id="purchase-form" action="{{ $action }}" method="POST" class="flex flex-col flex-1 md:overflow-hidden">
        @csrf

        <div class="border-b border-gray-100 dark:border-slate-800 shrink-0" x-show="showSupplierFields" x-cloak>
            <div class="px-4 py-2">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                    <div class="relative">
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Proveedor</label>
                        <input type="hidden" name="supplier_id" id="supplier-id-input" value="">
                        <input type="text" id="supplier-search" autocomplete="off" placeholder="Buscar o dejar en blanco..."
                            class="w-full px-3 h-10 text-sm rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">

                        <div id="supplier-selected" class="hidden mt-1.5 flex items-center justify-between gap-2 px-2 py-1 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-lg">
                            <span id="supplier-selected-name" class="text-xs font-medium text-emerald-700 dark:text-emerald-300 truncate"></span>
                            <button type="button" id="supplier-clear-btn" class="text-emerald-600 hover:text-emerald-800 shrink-0">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div id="supplier-search-results"
                            class="hidden absolute left-0 top-full mt-1 z-20 w-full max-h-64 overflow-y-auto rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 shadow-lg"></div>
                    </div>

                    <x-ui.form-field type="text" name="supplier_document" id="supplier-document-input" label="NIT" placeholder="Opcional" />
                    <x-ui.form-field type="select" name="payment_method" label="Pago" required :options="$paymentOptions" />
                </div>

                <div class="mt-2">
                    <x-ui.form-field type="text" name="invoice_number" label="N° Factura del Proveedor" placeholder="Opcional" />
                </div>
            </div>
        </div>

        <div class="px-4 py-2 border-b border-gray-100 dark:border-slate-800 space-y-1.5 shrink-0 bg-gray-50/50 dark:bg-slate-800/30">
            <div class="grid grid-cols-2 gap-2 items-end">
                <x-ui.form-field type="number" name="discount" id="discount-input" label="Descuento" value="0" placeholder="0.00" />

                <div class="pb-1.5">
                    <div class="flex justify-between text-xs text-gray-600 dark:text-gray-300">
                        <span>Subtotal</span>
                        <span id="summary-subtotal">0.00</span>
                    </div>
                    <div class="flex justify-between text-base font-bold text-gray-900 dark:text-white mt-0.5">
                        <span>Total</span>
                        <span id="summary-total">0.00</span>
                    </div>
                </div>
            </div>

            <div id="cart-items-inputs"></div>

            <x-ui.button type="submit" variant="primary" :fullWidth="true" id="submit-purchase-btn" disabled>
                <span id="cart-count-label">0 productos</span> — Registrar Compra
            </x-ui.button>
        </div>

        <div class="md:flex-1 md:overflow-y-auto px-4 py-2 md:min-h-0 flex flex-col">
            <div id="cart-empty" class="text-center py-10">
                <svg class="w-10 h-10 mx-auto text-gray-300 dark:text-gray-600 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                </svg>
                <p class="text-sm text-gray-500 dark:text-gray-400">Selecciona al menos un producto<br>para registrar la compra.</p>
            </div>

            <div id="cart-list" class="hidden divide-y divide-gray-100 dark:divide-gray-800"></div>
        </div>
    </form>

    {{-- Modal de creación rápida de proveedor --}}
    <div id="quick-supplier-modal" tabindex="-1" aria-hidden="true"
        class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm p-0 sm:p-4 flex items-end sm:items-center justify-center">
        <div class="w-full sm:max-w-md sm:my-8">
            <div class="bg-white dark:bg-slate-900 rounded-t-2xl sm:rounded-2xl border border-gray-200 dark:border-slate-700 shadow-2xl overflow-hidden max-h-[90vh] flex flex-col">
                <div class="px-6 py-5 border-b border-gray-100 dark:border-slate-800 shrink-0 flex items-start justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Nuevo Proveedor</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Se registrará y se seleccionará automáticamente.</p>
                    </div>
                    <button type="button" data-modal-close="quick-supplier-modal"
                        class="inline-flex items-center justify-center w-9 h-9 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-slate-800 transition shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div id="quick-supplier-error" class="hidden mx-6 mt-4 px-3 py-2 text-xs text-red-700 bg-red-50 border border-red-200 rounded-lg dark:bg-red-900/20 dark:text-red-300 dark:border-red-800"></div>

                <div class="px-6 py-5 overflow-y-auto space-y-3">
                    <x-ui.form-field type="text" name="quick_supplier_name" id="quick-supplier-name" label="Nombre" placeholder="Ej. Importadora Mayorista S.R.L." required />
                    <x-ui.form-field type="text" name="quick_supplier_tax_id" id="quick-supplier-tax-id" label="NIT" placeholder="Opcional" />
                    <x-ui.form-field type="text" name="quick_supplier_phone" id="quick-supplier-phone" label="Teléfono" placeholder="Opcional" />
                </div>

                <div class="px-6 py-4 border-t border-gray-100 dark:border-slate-800 flex justify-end gap-2 shrink-0">
                    <x-ui.button type="button" variant="outline" data-modal-close="quick-supplier-modal">Cancelar</x-ui.button>
                    <x-ui.button type="button" variant="primary" id="quick-supplier-save-btn">Crear y Seleccionar</x-ui.button>
                </div>
            </div>
        </div>
    </div>
</div>