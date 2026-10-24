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

<div id="ticket-column"
    class="md:col-span-3 bg-white dark:bg-slate-900 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden flex flex-col md:h-full md:min-h-0"
    x-data="{ showClientFields: true }">

    <div class="px-4 py-2 border-b border-gray-100 dark:border-slate-800 shrink-0 flex items-center justify-between">
        <h3 class="text-xs font-bold text-gray-900 dark:text-white">Ticket de Venta</h3>
        <button type="button" @click="showClientFields = !showClientFields"
            class="inline-flex items-center gap-1 text-[10px] font-medium text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" x-show="showClientFields">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
            </svg>
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                x-show="!showClientFields">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S3.732 16.057 2.458 12z" />
            </svg>
            <span x-text="showClientFields ? 'Ocultar' : 'Mostrar'"></span>
        </button>
    </div>

    <form id="sale-form" action="{{ $action }}" method="POST" enctype="multipart/form-data"
        class="flex flex-col flex-1 md:overflow-hidden">
        @csrf

        <div class="border-b border-gray-100 dark:border-slate-800 shrink-0" x-show="showClientFields" x-cloak>
            <div class="px-4 py-2">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                    {{-- Buscador de cliente --}}
                    <div class="relative">
                        <label
                            class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Cliente</label>
                        <input type="hidden" name="customer_id" id="customer-id-input" value="">
                        <input type="text" id="customer-search" autocomplete="off"
                            placeholder="Buscar o dejar en blanco..."
                            class="w-full px-3 h-10 text-sm rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">

                        <div id="customer-selected"
                            class="hidden mt-1.5 flex items-center justify-between gap-2 px-2 py-1 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-lg">
                            <span id="customer-selected-name"
                                class="text-xs font-medium text-emerald-700 dark:text-emerald-300 truncate"></span>
                            <button type="button" id="customer-clear-btn"
                                class="text-emerald-600 hover:text-emerald-800 shrink-0">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div id="customer-search-results"
                            class="hidden absolute left-0 top-full mt-1 z-20 w-full max-h-64 overflow-y-auto rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 shadow-lg">
                        </div>
                    </div>

                    <x-ui.form-field type="text" name="customer_document" id="customer-document-input"
                        label="NIT / CI" placeholder="Opcional" />
                    <x-ui.form-field type="select" name="payment_method" id="payment-method-select" label="Pago"
                        required :options="$paymentOptions" />
                    <div id="credit-due-date-block" class="hidden mt-2">
                        <x-ui.form-field type="date" name="due_date" id="due-date-input"
                            label="Fecha de Vencimiento del Crédito" :value="now()->addDays(30)->format('Y-m-d')" />
                    </div>
                </div>
            </div>

            {{-- Bloque QR: se muestra solo cuando el método de pago es QR --}}
            <div id="qr-payment-block" class="hidden px-4 py-3 bg-sky-50/50 dark:bg-sky-900/10">
                <div class="flex gap-3 items-start">
                    <div class="shrink-0">
                        @if ($company?->payment_qr_image_url)
                            <button type="button" id="qr-zoom-btn"
                                class="w-24 h-24 rounded-lg border border-gray-200 dark:border-slate-700 bg-white p-1 overflow-hidden hover:opacity-80 transition cursor-zoom-in">
                                <img src="{{ $company->payment_qr_image_url }}" alt="QR de pago"
                                    class="w-full h-full object-contain">
                            </button>
                        @else
                            <div
                                class="w-24 h-24 flex items-center justify-center rounded-lg border border-dashed border-gray-300 dark:border-slate-600 text-[10px] text-gray-400 text-center p-2">
                                QR no configurado
                            </div>
                        @endif
                    </div>

                    <div class="flex-1 min-w-0">
                        <p class="text-xs text-gray-700 dark:text-gray-300 mb-2">Escanea este QR para pagar, luego sube
                            o toma una foto del comprobante.</p>

                        <label
                            class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-[11px] font-medium rounded-lg border border-sky-300 dark:border-sky-700 text-sky-700 dark:text-sky-300 bg-white dark:bg-slate-800 hover:bg-sky-50 dark:hover:bg-slate-700 cursor-pointer">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 13a3 3 0 100 6 3 3 0 000-6z" />
                            </svg>
                            Subir comprobante
                            <input type="file" name="payment_proof" id="payment-proof-input" accept="image/*"
                                capture="environment" class="hidden">
                        </label>

                        <div class="mt-2 flex items-center gap-1.5">
                            <div
                                class="flex items-center rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 overflow-hidden">
                                <span
                                    class="px-2 h-8 flex items-center text-[11px] font-medium text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-slate-700 border-r border-gray-300 dark:border-slate-600">
                                    +{{ $company?->phone_country_code ?? '591' }}
                                </span>
                                <input type="text" id="whatsapp-number-input" placeholder="70012345"
                                    class="w-28 px-2 h-8 text-[11px] border-0 bg-transparent text-gray-900 dark:text-white placeholder-gray-400 focus:ring-0">
                            </div>
                            <button type="button" id="send-qr-whatsapp-btn"
                                class="inline-flex items-center gap-1 px-2.5 py-1.5 text-[11px] font-medium rounded-lg border border-emerald-300 dark:border-emerald-700 text-emerald-700 dark:text-emerald-300 bg-white dark:bg-slate-800 hover:bg-emerald-50 dark:hover:bg-slate-700">
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24">
                                    <path
                                        d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.372-.025-.521-.075-.148-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z" />
                                </svg>
                                Enviar por WhatsApp
                            </button>
                        </div>

                        <div id="payment-proof-preview" class="hidden mt-2">
                            <img id="payment-proof-preview-img"
                                class="w-16 h-16 object-cover rounded-lg border border-gray-200 dark:border-slate-700">
                            <span id="payment-proof-filename"
                                class="text-[10px] text-gray-500 dark:text-gray-400 block mt-1 truncate"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Totales + botón Vender: arriba, siempre visible --}}
        <div
            class="px-4 py-2 border-b border-gray-100 dark:border-slate-800 space-y-1.5 shrink-0 bg-gray-50/50 dark:bg-slate-800/30">
            <div class="grid grid-cols-2 gap-2 items-end">
                <x-ui.form-field type="number" name="discount" id="discount-input" label="Descuento"
                    value="0" placeholder="0.00" />

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

            <x-ui.button type="submit" variant="primary" :fullWidth="true" id="submit-sale-btn" disabled>
                <span id="cart-count-label">0 productos</span> — Vender
            </x-ui.button>
        </div>

        {{-- Carrito --}}
        <div class="md:flex-1 md:overflow-y-auto px-4 py-2 md:min-h-0 flex flex-col">
            <div id="cart-view-toggle" class="hidden items-center gap-1.5 mb-2 shrink-0">
                <span class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mr-1">Vista:</span>
                <button type="button" id="cart-view-scroll-btn"
                    class="px-2.5 py-1 text-[11px] font-medium rounded-lg border transition">
                    Ver todo
                </button>
                <button type="button" id="cart-view-paginated-btn"
                    class="px-2.5 py-1 text-[11px] font-medium rounded-lg border transition">
                    Paginado
                </button>
            </div>

            <div id="cart-empty" class="text-center py-10">
                <svg class="w-10 h-10 mx-auto text-gray-300 dark:text-gray-600 mb-2" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                <p class="text-sm text-gray-500 dark:text-gray-400">Selecciona al menos un producto<br>para crear el
                    ticket.</p>
            </div>

            <div id="cart-list" class="hidden divide-y divide-gray-100 dark:divide-gray-800"></div>

            <div id="cart-pagination" class="hidden md:mt-auto pt-3 flex items-center justify-between shrink-0">
                <button type="button" id="cart-prev-btn"
                    class="inline-flex items-center gap-1 px-2.5 py-1 text-[11px] font-medium rounded-lg border border-gray-300 dark:border-slate-600 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-800 disabled:opacity-40 disabled:cursor-not-allowed">
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Anterior
                </button>
                <span id="cart-page-indicator" class="text-[11px] text-gray-500 dark:text-gray-400"></span>
                <button type="button" id="cart-next-btn"
                    class="inline-flex items-center gap-1 px-2.5 py-1 text-[11px] font-medium rounded-lg border border-gray-300 dark:border-slate-600 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-800 disabled:opacity-40 disabled:cursor-not-allowed">
                    Siguiente
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
            </div>
        </div>
    </form>

    {{-- Modal de detalle de producto en el carrito --}}
    <div id="item-detail-modal" tabindex="-1" aria-hidden="true"
        class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm p-0 sm:p-4 flex items-end sm:items-center justify-center">
        <div class="w-full sm:max-w-sm sm:my-8">
            <div
                class="bg-white dark:bg-slate-900 rounded-t-2xl sm:rounded-2xl border border-gray-200 dark:border-slate-700 shadow-2xl overflow-hidden">
                <div
                    class="px-6 py-5 border-b border-gray-100 dark:border-slate-800 flex items-start justify-between gap-4">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Detalle del Producto</h3>
                    <button type="button" data-modal-close="item-detail-modal"
                        class="inline-flex items-center justify-center w-9 h-9 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-slate-800 transition shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="px-6 py-5">
                    <div id="item-detail-thumb"
                        class="w-20 h-20 rounded-lg overflow-hidden mb-4 bg-gray-50 dark:bg-slate-800"></div>

                    <div class="space-y-3">
                        <div>
                            <span
                                class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-0.5">Nombre</span>
                            <span id="item-detail-name"
                                class="text-sm font-medium text-gray-900 dark:text-white"></span>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <span
                                    class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-0.5">SKU</span>
                                <span id="item-detail-sku"
                                    class="text-sm font-mono text-gray-700 dark:text-gray-300"></span>
                            </div>
                            <div>
                                <span
                                    class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-0.5">Unidad</span>
                                <span id="item-detail-unit" class="text-sm text-gray-700 dark:text-gray-300"></span>
                            </div>
                            <div>
                                <span
                                    class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-0.5">Stock
                                    Disponible</span>
                                <span id="item-detail-stock" class="text-sm text-gray-700 dark:text-gray-300"></span>
                            </div>
                            <div>
                                <span
                                    class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-0.5">Cantidad
                                    en Carrito</span>
                                <span id="item-detail-quantity"
                                    class="text-sm text-gray-700 dark:text-gray-300"></span>
                            </div>
                            <div>
                                <span
                                    class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-0.5">Precio
                                    Unitario</span>
                                <span id="item-detail-price" class="text-sm text-gray-700 dark:text-gray-300"></span>
                            </div>
                            <div>
                                <span
                                    class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-0.5">Subtotal</span>
                                <span id="item-detail-subtotal" class="text-sm font-bold text-emerald-600"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 border-t border-gray-100 dark:border-slate-800 flex justify-end">
                    <x-ui.button type="button" variant="outline"
                        data-modal-close="item-detail-modal">Cerrar</x-ui.button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal de creación rápida de cliente --}}
    <div id="quick-customer-modal" tabindex="-1" aria-hidden="true"
        class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm p-0 sm:p-4 flex items-end sm:items-center justify-center">
        <div class="w-full sm:max-w-md sm:my-8">
            <div
                class="bg-white dark:bg-slate-900 rounded-t-2xl sm:rounded-2xl border border-gray-200 dark:border-slate-700 shadow-2xl overflow-hidden max-h-[90vh] flex flex-col">
                <div
                    class="px-6 py-5 border-b border-gray-100 dark:border-slate-800 shrink-0 flex items-start justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Nuevo Cliente</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Se registrará y se seleccionará
                            automáticamente para esta venta.</p>
                    </div>
                    <button type="button" data-modal-close="quick-customer-modal"
                        class="inline-flex items-center justify-center w-9 h-9 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-slate-800 transition shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div id="quick-customer-error"
                    class="hidden mx-6 mt-4 px-3 py-2 text-xs text-red-700 bg-red-50 border border-red-200 rounded-lg dark:bg-red-900/20 dark:text-red-300 dark:border-red-800">
                </div>

                <div class="px-6 py-5 overflow-y-auto space-y-3">
                    <x-ui.form-field type="text" name="quick_customer_name" id="quick-customer-name"
                        label="Nombre Completo" placeholder="Ej. Juan Pérez Mamani" required />

                    <div class="grid grid-cols-2 gap-3">
                        <x-ui.form-field type="select" name="quick_customer_document_type"
                            id="quick-customer-document-type" label="Tipo Documento" value="ci"
                            :options="['ci' => 'CI', 'nit' => 'NIT', 'otro' => 'Otro']" />
                        <x-ui.form-field type="text" name="quick_customer_document" id="quick-customer-document"
                            label="N° Documento" placeholder="Opcional" />
                    </div>

                    <x-ui.form-field type="text" name="quick_customer_phone" id="quick-customer-phone"
                        label="Teléfono" placeholder="Opcional" />
                </div>

                <div class="px-6 py-4 border-t border-gray-100 dark:border-slate-800 flex justify-end gap-2 shrink-0">
                    <x-ui.button type="button" variant="outline"
                        data-modal-close="quick-customer-modal">Cancelar</x-ui.button>
                    <x-ui.button type="button" variant="primary" id="quick-customer-save-btn">Crear y
                        Seleccionar</x-ui.button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal de vista ampliada del QR --}}
    @if ($company?->payment_qr_image_url)
        <div id="qr-zoom-modal" tabindex="-1" aria-hidden="true"
            class="hidden fixed inset-0 z-[60] bg-slate-900/80 backdrop-blur-sm p-4 flex items-center justify-center">
            <div class="relative max-w-xs w-full max-h-[85vh] flex flex-col items-center">
                <button type="button" data-modal-close="qr-zoom-modal"
                    class="absolute -top-3 -right-3 z-10 w-9 h-9 flex items-center justify-center rounded-full bg-white text-slate-700 hover:bg-gray-100 shadow-lg transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
                <img src="{{ $company->payment_qr_image_url }}" alt="QR de pago ampliado"
                    class="max-w-full max-h-[85vh] w-auto h-auto object-contain rounded-xl border-4 border-white bg-white shadow-2xl">
            </div>
        </div>
    @endif
</div>
