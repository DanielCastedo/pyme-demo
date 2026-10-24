@php
    // Parcial compartido por Ventas (mode = sale) y Pedidos (mode = order). Lo que no se pase, toma estos valores.
    $mode = $mode ?? 'sale';
    $method = $method ?? 'POST';
    $category = $category ?? 'Ventas';
    $submitLabel = $submitLabel ?? 'Guardar';
    $initialItems = $initialItems ?? [];
    $initialCustomerId = $initialCustomerId ?? null;
    $initialWarehouseId = $initialWarehouseId ?? null;
    $deliveryDate = $deliveryDate ?? null;
    $note = $note ?? null;
    $canCredit = ($canCredit ?? false) && $mode === 'sale';
@endphp
    <div class="max-w-10xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

        @if (session('error'))
            <x-ui.alert type="error" :message="session('error')" />
        @endif

        @if ($errors->any())
            <x-ui.alert type="error">
                <ul class="list-disc pl-4 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-ui.alert>
        @endif

        <x-ui.page-header :category="$category" :title="$title" :description="$description" />

        <form id="sale-form" action="{{ $action }}" method="POST" enctype="multipart/form-data" class="grid grid-cols-1 lg:grid-cols-3 gap-5">
            @csrf
            @if ($method !== 'POST')
                @method($method)
            @endif
            <input type="file" name="receipt_photo" id="sale-receipt-file-input" class="hidden" accept="image/*">

            {{-- ── Izquierda: productos ─────────────────────────────── --}}
            <div class="lg:col-span-2 space-y-4">
                <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 p-4">
                    <div class="relative" id="product-search-wrapper">
                        <svg class="w-5 h-5 absolute left-3 top-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z" />
                        </svg>
                        <input type="text" id="product-search" autofocus autocomplete="off"
                            placeholder="Busca por nombre o código, o escanea con el lector (barras / QR)..."
                            class="w-full h-12 pl-10 pr-14 text-sm rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                        <button type="button" id="scan-camera-btn" title="Escanear código de barras o QR con la cámara"
                            class="absolute right-2 top-2 w-8 h-8 flex items-center justify-center rounded-lg border border-sky-300 dark:border-sky-700 text-sky-600 dark:text-sky-400 hover:bg-sky-50 dark:hover:bg-sky-900/20 transition">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V6a2 2 0 012-2h2M4 16v2a2 2 0 002 2h2m8-16h2a2 2 0 012 2v2m-4 12h2a2 2 0 002-2v-2M7 12h10" /></svg>
                        </button>
                        <div id="product-results"
                            class="hidden absolute left-0 top-full mt-1 z-30 w-full max-h-80 overflow-y-auto rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 shadow-xl"></div>
                    </div>
                </div>

                {{-- En pantallas chicas (< 768 px) cada producto se muestra como tarjeta en vez de fila de tabla --}}
                <style>
                    @media (max-width: 767px) {
                        #pos-lines-table, #pos-lines-table tbody { display: block; width: 100%; }
                        #pos-lines-table thead { display: none; }
                        #lines-body > tr {
                            display: grid;
                            grid-template-columns: 64px 1fr 1fr 28px;
                            gap: 10px 12px;
                            align-items: center;
                            padding: 14px 12px;
                        }
                        #lines-body > tr > td { display: block; padding: 0 !important; height: auto !important; width: auto !important; text-align: left !important; min-width: 0; }
                        /* Foto | Producto | Quitar */
                        #lines-body > tr > td:nth-child(1) { grid-column: 1; grid-row: 1; }
                        #lines-body > tr > td:nth-child(2) { grid-column: 2 / 4; grid-row: 1; }
                        #lines-body > tr > td:nth-child(7) { grid-column: 4; grid-row: 1; align-self: start; text-align: right !important; }
                        /* Cantidad | Precio */
                        #lines-body > tr > td:nth-child(3) { grid-column: 1 / 3; grid-row: 2; }
                        #lines-body > tr > td:nth-child(4) { grid-column: 3 / 5; grid-row: 2; }
                        /* Descuento | Subtotal */
                        #lines-body > tr > td:nth-child(5) { grid-column: 1 / 3; grid-row: 3; }
                        #lines-body > tr > td:nth-child(6) { grid-column: 3 / 5; grid-row: 3; text-align: right !important; font-size: 1.05rem; }
                        /* Nombre del campo sobre cada control */
                        #lines-body > tr > td:nth-child(n+3):nth-child(-n+6)::before {
                            display: block; margin-bottom: 4px; font-size: 10px; font-weight: 600; letter-spacing: .05em; text-transform: uppercase; color: #6b7280;
                        }
                        #lines-body > tr > td:nth-child(3)::before { content: 'Cantidad'; }
                        #lines-body > tr > td:nth-child(4)::before { content: 'Precio'; }
                        #lines-body > tr > td:nth-child(5)::before { content: 'Descuento'; }
                        #lines-body > tr > td:nth-child(6)::before { content: 'Subtotal'; text-align: right; }
                        .dark #lines-body > tr > td::before { color: #9ca3af; }
                        /* Controles a lo ancho de su columna */
                        #lines-body .price-wrap { width: 100% !important; margin-left: 0 !important; }
                        #lines-body .price-input { width: 100% !important; }
                        #lines-body .price-hint { position: static !important; margin-top: 6px; flex-wrap: wrap; justify-content: flex-start; }
                        #lines-body td:nth-child(3) > div, #lines-body td:nth-child(5) > div { justify-content: flex-start !important; }
                    }
                </style>

                <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table id="pos-lines-table" class="w-full text-sm">
                            <thead class="bg-gray-50 dark:bg-slate-800 text-[11px] uppercase text-gray-500 dark:text-gray-400">
                                <tr>
                                    <th class="px-3 py-2 text-center w-20">Foto</th>
                                    <th class="px-3 py-2 text-left">Producto</th>
                                    <th class="px-3 py-2 text-center w-36">Cantidad</th>
                                    <th class="px-3 py-2 text-right w-28">Precio</th>
                                    <th class="px-3 py-2 text-center w-32">Descuento</th>
                                    <th class="px-3 py-2 text-right w-28">Subtotal</th>
                                    <th class="px-3 py-2 w-8"></th>
                                </tr>
                            </thead>
                            <tbody id="lines-body" class="divide-y divide-gray-100 dark:divide-gray-800"></tbody>
                        </table>
                    </div>
                    <div id="lines-empty" class="px-4 py-12 text-center text-sm text-gray-400 dark:text-gray-500">
                        Aún no hay productos. Escribe arriba o escanea un código.
                    </div>
                </div>

                @if (! $canEditPrice || ! $canDiscount)
                    <p class="text-[11px] text-gray-400">
                        @if (! $canEditPrice) No tienes permiso para cambiar precios. @endif
                        @if (! $canDiscount) No tienes permiso para dar descuentos. @endif
                    </p>
                @endif
            </div>

            {{-- ── Derecha: cobro ───────────────────────────────────── --}}
            <div class="lg:col-span-1">
                <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 p-4 space-y-4 lg:sticky lg:top-4">

                    {{-- Cliente --}}
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300">Cliente</label>
                            @if ($canCreateCustomer)
                                <button type="button" id="new-customer-btn" class="text-xs font-semibold text-emerald-600 hover:text-emerald-700 hover:underline">+ Nuevo cliente</button>
                            @endif
                        </div>
                        <input type="hidden" name="customer_id" id="customer-id" value="{{ old('customer_id', $initialCustomerId) }}">
                        <div id="customer-chip" class="hidden items-center justify-between gap-2 px-3 h-10 rounded-lg bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 text-sm">
                            <span id="customer-chip-text" class="truncate text-emerald-800 dark:text-emerald-300"></span>
                            <button type="button" id="customer-clear" class="text-emerald-700 hover:text-red-600 text-lg leading-none" title="Quitar cliente">&times;</button>
                        </div>
                        <div id="customer-search-box" class="relative">
                            <input type="text" id="customer-search" autocomplete="off" placeholder="{{ $mode === 'consignment' ? 'Obligatorio: busca al cliente por nombre o documento' : 'Cliente general (busca por nombre o documento)' }}"
                                class="w-full h-10 px-3 text-sm rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white placeholder-gray-400">
                            <div id="customer-results"
                                class="hidden absolute left-0 top-full mt-1 z-30 w-full max-h-56 overflow-y-auto rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 shadow-xl"></div>
                        </div>
                    </div>

                    @if ($canCredit)
                        {{-- Contado o crédito --}}
                        <div>
                            <input type="hidden" name="payment_type" id="payment-type" value="{{ old('payment_type', 'cash') }}">
                            <div class="grid grid-cols-2 gap-1 p-1 rounded-lg bg-gray-100 dark:bg-slate-800">
                                <button type="button" data-type="cash" class="type-btn h-9 text-xs font-bold rounded-md">Contado</button>
                                <button type="button" data-type="credit" class="type-btn h-9 text-xs font-bold rounded-md">Crédito</button>
                            </div>
                            <div id="credit-fields" class="hidden mt-3 space-y-3">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Vence el <span class="text-red-500">*</span></label>
                                    <input type="date" name="due_date" id="due-date" value="{{ old('due_date', now()->addDays(30)->toDateString()) }}"
                                        class="w-full h-10 px-3 text-sm rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Anticipo (opcional)</label>
                                    <input type="number" step="0.01" min="0" name="initial_payment" id="initial-payment" value="{{ old('initial_payment') }}" placeholder="0.00"
                                        class="w-full h-10 px-3 text-sm rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white">
                                </div>
                                <p id="credit-info" class="text-[11px] text-gray-500 dark:text-gray-400"></p>
                                <p id="credit-debt" class="text-sm font-semibold text-amber-600"></p>
                            </div>
                        </div>
                    @endif

                    @if ($mode === 'order')
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Fecha de entrega <span class="text-red-500">*</span></label>
                            <input type="date" name="delivery_date" required value="{{ old('delivery_date', $deliveryDate) }}"
                                class="w-full h-10 px-3 text-sm rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white">
                        </div>
                    @elseif ($mode === 'consignment')
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Fecha límite para liquidar (opcional)</label>
                            <input type="date" name="due_date" value="{{ old('due_date', $deliveryDate) }}"
                                class="w-full h-10 px-3 text-sm rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white">
                            <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">La mercadería sale del almacén ahora; no se cobra nada hasta que el cliente liquide.</p>
                        </div>
                    @else
                    {{-- Cómo paga --}}
                    <div id="payment-block">
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Método de pago</label>
                        <select name="payment_id" id="payment-id"
                            class="w-full h-10 px-3 text-sm rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white">
                            @foreach ($paymentMethods as $pm)
                                <option value="{{ $pm->id }}" data-cash="{{ $pm->is_cash ? 1 : 0 }}" @selected(old('payment_id') == $pm->id)>{{ $pm->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    {{-- Sucursal / almacén / caja (preseleccionados) --}}
                    <details class="group" id="location-details">
                        <summary class="flex items-center justify-between cursor-pointer list-none text-xs">
                            <span class="font-semibold text-gray-700 dark:text-gray-300">{{ in_array($mode, ['order', 'consignment']) ? 'Sucursal y almacén' : 'Dónde se vende' }}</span>
                            <span id="location-summary" class="text-gray-500 dark:text-gray-400 truncate ml-2"></span>
                        </summary>
                        <div class="mt-3 space-y-3">
                            <div>
                                <label class="block text-[11px] text-gray-500 mb-1">Sucursal</label>
                                <select id="branch-id" class="w-full h-9 px-2 text-sm rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white">
                                    @foreach ($branches as $b)
                                        <option value="{{ $b->id }}">{{ $b->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-[11px] text-gray-500 mb-1">Almacén (de donde sale la mercadería)</label>
                                <select name="warehouse_id" id="warehouse-id" class="w-full h-9 px-2 text-sm rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white"></select>
                            </div>
                            @if ($mode === 'sale')
                            <div>
                                <label class="block text-[11px] text-gray-500 mb-1">Caja (donde se cobra)</label>
                                <select name="cashbox_id" id="cashbox-id" class="w-full h-9 px-2 text-sm rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white"></select>
                            </div>
                            @endif
                        </div>
                    </details>

                    <div>
                        <input type="text" name="note" id="note-input" maxlength="1000" value="{{ old('note', $note) }}" placeholder="Nota (opcional)"
                            class="w-full h-9 px-3 text-sm rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white placeholder-gray-400">
                    </div>

                    {{-- Totales --}}
                    <div class="pt-3 border-t border-gray-100 dark:border-gray-800 space-y-1">
                        <div id="gross-row" class="hidden flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                            <span>Subtotal</span><span id="gross">0.00</span>
                        </div>
                        <div id="discount-row" class="hidden flex items-center justify-between text-xs text-red-600 dark:text-red-400">
                            <span>Descuentos</span><span id="discount-total">0.00</span>
                        </div>
                        <div class="flex items-baseline justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Total</span>
                            <span id="total" class="text-3xl font-extrabold text-gray-900 dark:text-white">0.00</span>
                        </div>
                        <p class="text-[11px] text-gray-400 text-right"><span id="items-count">0</span> producto(s)</p>
                    </div>

                    @if ($mode === 'sale')
                    {{-- Efectivo recibido y vuelto (solo informativo) --}}
                    <div id="cash-helper" class="hidden">
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Efectivo recibido</label>
                        <input type="number" step="0.01" min="0" id="received" placeholder="0.00"
                            class="w-full h-10 px-3 text-sm rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white">
                        <p id="change" class="mt-1 text-sm font-semibold"></p>
                    </div>
                    @endif

                    <p id="stock-warning" class="hidden text-xs font-medium text-red-600 dark:text-red-400"></p>

                    <button type="submit" id="submit-btn" disabled
                        class="w-full h-12 rounded-lg bg-emerald-600 hover:bg-emerald-700 disabled:bg-gray-300 dark:disabled:bg-slate-700 disabled:cursor-not-allowed text-white font-bold text-sm transition-colors">
                        {{ $mode === 'sale' ? 'Cobrar' : $submitLabel }}
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- ── Lector por cámara (código de barras / QR) y aviso de lo escaneado ──────── --}}
    <div id="scan-modal" class="hidden fixed inset-0 z-[60] bg-slate-900/70 backdrop-blur-sm p-4 flex items-center justify-center">
        <div class="w-full max-w-sm bg-white dark:bg-slate-900 rounded-2xl border border-gray-200 dark:border-slate-700 shadow-2xl overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 dark:border-slate-800 flex items-center justify-between">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white">Escanear productos</h3>
                <button type="button" id="scan-modal-close" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
            </div>
            <div class="p-4">
                <div id="scan-reader" class="w-full rounded-lg overflow-hidden bg-slate-100 dark:bg-slate-800 min-h-[120px]"></div>
                <div id="scan-feedback" class="mt-3 p-2.5 rounded-lg text-xs font-medium text-center bg-gray-50 dark:bg-slate-800 text-gray-600 dark:text-gray-300">Apunta la cámara al código.</div>
                <p class="text-[11px] text-gray-500 dark:text-gray-400 text-center mt-3">
                    Cada código que muestres se agrega a la venta. Para sumar otra unidad del mismo producto, retira el código y vuelve a mostrarlo.
                </p>
                <button type="button" id="scan-modal-done" class="mt-3 w-full h-10 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold">Listo</button>
            </div>
        </div>
    </div>
    <div id="scan-toast" class="hidden fixed bottom-4 right-4 z-[70] max-w-xs px-4 py-2.5 rounded-lg shadow-xl text-sm font-semibold text-white"></div>

    {{-- ── Modal: Pago con QR y Carga de Comprobante ───────────────── --}}
    <div id="qr-payment-modal" class="hidden fixed inset-0 z-50 bg-slate-900/70 backdrop-blur-sm p-4 flex items-center justify-center">
        <div class="w-full max-w-md bg-white dark:bg-slate-900 rounded-2xl border border-gray-200 dark:border-slate-700 shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
            <div class="px-5 py-4 border-b border-gray-100 dark:border-slate-800 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                        </svg>
                        Cobro con Código QR
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Escanea el QR y sube el comprobante</p>
                </div>
                <button type="button" id="qr-modal-close" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 text-xl leading-none">&times;</button>
            </div>

            <div class="p-5 overflow-y-auto space-y-4">
                {{-- Monto a cobrar --}}
                <div class="p-3 rounded-xl bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800/50 text-center">
                    <span class="text-xs font-semibold text-emerald-800 dark:text-emerald-300 uppercase tracking-wider block">Monto a Transferir</span>
                    <span id="qr-modal-amount" class="text-3xl font-extrabold text-emerald-700 dark:text-emerald-400">Bs. 0.00</span>
                </div>

                {{-- QR de la sucursal seleccionada --}}
                <div class="text-center">
                    <div id="qr-modal-image-wrapper" class="hidden inline-block p-2 bg-white rounded-2xl border-2 border-dashed border-gray-300 dark:border-slate-600 shadow-sm">
                        <img id="qr-modal-image" src="" alt="Código QR de la Sucursal"
                            class="w-52 h-52 object-contain rounded-xl mx-auto">
                    </div>
                    <p id="qr-modal-caption" class="hidden text-xs text-gray-500 dark:text-gray-400 mt-2">
                        Pide al cliente que escanee el código QR desde su app bancaria para pagar.
                    </p>
                    <div id="qr-modal-empty" class="hidden p-4 rounded-xl border border-amber-200 dark:border-amber-800/40 bg-amber-50 dark:bg-amber-900/20 text-amber-800 dark:text-amber-300 text-xs text-center">
                        ⚠ La sucursal seleccionada no tiene configurada su imagen de QR de cobro en <strong>Sucursales</strong>.
                    </div>
                </div>

                {{-- Sección de Comprobante --}}
                <div class="pt-3 border-t border-gray-100 dark:border-slate-800">
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        Comprobante de Pago <span class="text-red-500">*</span>
                    </label>

                    {{-- Botones para capturar/subir --}}
                    <div class="grid grid-cols-2 gap-2 mb-2">
                        <button type="button" id="btn-camera-receipt"
                            class="flex items-center justify-center gap-2 h-10 px-3 rounded-lg border border-sky-300 dark:border-sky-700 bg-sky-50 dark:bg-sky-900/20 text-sky-700 dark:text-sky-300 text-xs font-semibold hover:bg-sky-100 transition">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            Tomar Foto
                        </button>
                        <button type="button" id="btn-upload-receipt"
                            class="flex items-center justify-center gap-2 h-10 px-3 rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-700 dark:text-gray-300 text-xs font-semibold hover:bg-gray-50 transition">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                            </svg>
                            Importar Imagen
                        </button>
                    </div>

                    {{-- Inputs file ocultos para foto directa o archivo --}}
                    <input type="file" id="input-receipt-camera" capture="environment" accept="image/*" class="hidden">
                    <input type="file" id="input-receipt-file" accept="image/jpeg,image/png,image/webp" class="hidden">

                    {{-- Vista previa de comprobante --}}
                    <div id="receipt-preview-wrapper" class="hidden rounded-xl border border-emerald-300 dark:border-emerald-700/50 bg-emerald-50/50 dark:bg-emerald-950/20 p-3 flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3 overflow-hidden">
                            <img id="receipt-preview-img" src="" alt="Comprobante" class="w-12 h-12 object-cover rounded-lg border border-emerald-300">
                            <div class="truncate">
                                <span class="text-xs font-bold text-gray-900 dark:text-white block truncate" id="receipt-preview-name">comprobante.jpg</span>
                                <span class="text-[10px] text-emerald-600 dark:text-emerald-400 font-semibold block">✓ Comprobante cargado</span>
                            </div>
                        </div>
                        <button type="button" id="btn-receipt-remove" class="text-red-500 hover:text-red-700 text-xs font-semibold shrink-0">
                            Quitar
                        </button>
                    </div>

                    <p id="qr-modal-error" class="hidden text-xs text-red-500 mt-1.5 font-medium"></p>
                </div>
            </div>

            <div class="px-5 py-4 border-t border-gray-100 dark:border-slate-800 flex justify-end gap-2 shrink-0">
                <button type="button" id="qr-modal-cancel"
                    class="px-4 py-2 rounded-lg border border-gray-300 dark:border-slate-600 text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-800 transition">
                    Cancelar
                </button>
                <button type="button" id="qr-modal-confirm"
                    class="px-5 py-2.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold transition flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Guardar Comprobante y Cobrar
                </button>
            </div>
        </div>
    </div>

    {{-- ── Modal: nuevo cliente ─────────────────────────────────── --}}
    @if ($canCreateCustomer)
        <div id="customer-modal" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm p-4 flex items-center justify-center">
            <div class="w-full max-w-md bg-white dark:bg-slate-900 rounded-2xl border border-gray-200 dark:border-slate-700 shadow-2xl overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 dark:border-slate-800 flex items-center justify-between">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white">Nuevo cliente</h3>
                    <button type="button" id="customer-modal-close" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
                </div>
                <form id="customer-form" class="p-5 space-y-3">
                    <div id="customer-form-errors" class="hidden p-2.5 rounded-lg bg-red-50 dark:bg-red-900/20 text-xs text-red-700 dark:text-red-300"></div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Nombre / Razón social <span class="text-red-500">*</span></label>
                        <input type="text" name="name" required maxlength="255" placeholder="Ej. Juan Pérez"
                            class="w-full h-10 px-3 text-sm rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white">
                    </div>
                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Documento</label>
                            <select name="document_type" class="w-full h-10 px-2 text-sm rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white">
                                @foreach (\App\Models\Customer::DOCUMENT_TYPES as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-span-2">
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">N° de documento</label>
                            <input type="text" name="document" maxlength="50" placeholder="Opcional"
                                class="w-full h-10 px-3 text-sm rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Teléfono</label>
                            <input type="text" name="phone" maxlength="30" placeholder="Ej. 7XXXXXXX"
                                class="w-full h-10 px-3 text-sm rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Correo</label>
                            <input type="email" name="email" maxlength="255" placeholder="Opcional"
                                class="w-full h-10 px-3 text-sm rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white">
                        </div>
                    </div>

                    <div class="pt-2 flex justify-end gap-2">
                        <button type="button" id="customer-modal-cancel" class="px-4 h-10 text-xs font-semibold rounded-lg border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-800">Cancelar</button>
                        <button type="submit" id="customer-save" class="px-4 h-10 text-xs font-semibold rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white">Guardar y seleccionar</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <x-ui.image-preview-modal />

    @push('scripts')
        <script>
            (async function() {
                // El catálogo NO se manda entero al navegador (con miles/millones de productos sería
                // lentísimo o directamente colgaría la pantalla): el buscador pide productos al servidor
                // a medida que se escribe, y cada respuesta trae como mucho 15 (ver PosSearchController).
                // Cada producto que devuelve el servidor ya viene con su stock/costo por almacén y sus
                // presentaciones hermanas (unit.stock_by_warehouse, unit.cost_by_warehouse, unit.siblings).
                const searchUrl = @json(route('pos.products.search'));
                async function fetchUnits(params) {
                    const url = new URL(searchUrl, window.location.origin);
                    Object.entries(params).forEach(([k, v]) => { if (v !== undefined && v !== null && v !== '') url.searchParams.set(k, v); });
                    try {
                        const res = await fetch(url, { headers: { Accept: 'application/json' } });
                        if (!res.ok) return [];
                        const data = await res.json().catch(() => ({units: []}));
                        return data.units || [];
                    } catch (e) {
                        return []; // sin red: la búsqueda simplemente no trae resultados, no rompe la pantalla
                    }
                }
                // Misma idea para la cartera de clientes: antes se mandaba entera, ahora se pide de a poco.
                const customersSearchUrl = @json(route('pos.customers.search'));
                async function fetchCustomers(params) {
                    const url = new URL(customersSearchUrl, window.location.origin);
                    Object.entries(params).forEach(([k, v]) => { if (v !== undefined && v !== null && v !== '') url.searchParams.set(k, v); });
                    try {
                        const res = await fetch(url, { headers: { Accept: 'application/json' } });
                        if (!res.ok) return [];
                        const data = await res.json().catch(() => ({customers: []}));
                        return data.customers || [];
                    } catch (e) {
                        return [];
                    }
                }
                const warehouses = @json($warehouses);
                const cashboxes = @json($cashboxes);
                // sucursal → URL de su QR de cobro (para el modal de pago con QR)
                const branchQr = @json($branches->pluck('payment_qr_image_url', 'id'));
                const canEditPrice = @json($canEditPrice);
                const canDiscount = @json($canDiscount);
                const customersUrl = @json(route('customers.store'));
                // sale: cobra y descuenta stock ya (no deja pasar del stock). order: pedido en espera (el stock es solo informativo).
                const mode = @json($mode);
                const enforceStock = mode === 'sale' || mode === 'consignment';
                const submitLabel = @json($submitLabel);
                const canCredit = @json($canCredit);
                let saleType = 'cash';
                let ready = false; // true cuando ya se dibujaron las líneas (antes no se puede refrescar)
                const today = new Date(Date.now() - new Date().getTimezoneOffset() * 60000).toISOString().slice(0, 10);
                const docLabels = {ci: 'CI', nit: 'NIT', otro: ''};
                const old = {
                    items: @json(old('items', $initialItems)),
                    warehouse: @json(old('warehouse_id', $initialWarehouseId)),
                    cashbox: @json(old('cashbox_id')),
                    branch: @json($defaultBranchId),
                    customer: @json(old('customer_id', $initialCustomerId)),
                };

                const $ = (id) => document.getElementById(id);
                const search = $('product-search'), results = $('product-results');
                // Después de elegir un producto, el foco vuelve al buscador para poder escanear/tipear el
                // siguiente al toque — pero ESE foco puntual no debe reabrir el desplegable (si no, la lista
                // "de entrada" reaparece justo después de agregar algo, como si no se hubiera cerrado nunca).
                let suppressFocusSearch = false;
                function focusSearchQuietly() {
                    suppressFocusSearch = true;
                    search.focus();
                }
                const body = $('lines-body'), empty = $('lines-empty');
                const branchSel = $('branch-id'), whSel = $('warehouse-id'), cbSel = $('cashbox-id'), paySel = $('payment-id');
                const submitBtn = $('submit-btn'), totalEl = $('total'), warning = $('stock-warning');

                const round2 = (n) => Math.round((n + Number.EPSILON) * 100) / 100;
                const money = (n) => Number(n).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                const cellInput = 'h-9 px-2 text-sm text-right rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white focus:ring-1 focus:ring-emerald-500';
                const stepInput = 'w-14 h-9 text-center text-sm border-y border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white focus:outline-none';
                const btnCls = 'w-8 h-9 border border-gray-300 dark:border-slate-600 bg-gray-50 dark:bg-slate-700 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-slate-600 font-bold';

                // {unit, qty, price, discType: 'percent'|'amount', discValue}
                const lines = [];

                // ── Sucursal/almacén/caja recordados en ESTE navegador (localStorage) ────
                // Sirve para no tener que elegir "Dónde se vende" cada vez: se guarda solo en el
                // dispositivo (no en el servidor), así cada caja/vendedor puede tener su propio
                // almacén por defecto sin pisar el de los demás.
                const LOCATION_STORAGE_KEY = 'pos-default-location';

                function loadSavedLocation() {
                    try {
                        return JSON.parse(localStorage.getItem(LOCATION_STORAGE_KEY) || 'null');
                    } catch (e) {
                        return null; // almacenamiento bloqueado o navegación privada: se sigue sin recordar
                    }
                }

                function saveLocation() {
                    try {
                        localStorage.setItem(LOCATION_STORAGE_KEY, JSON.stringify({
                            branch: branchSel.value,
                            warehouse: whSel.value,
                            cashbox: cbSel ? cbSel.value : null,
                        }));
                    } catch (e) {
                        // idem: si no se puede guardar, no es grave, solo no queda recordado.
                    }
                }

                // ── Sucursal → almacén y caja ────────────────────────────
                function fillSelect(select, items, preferred) {
                    select.innerHTML = '';
                    items.forEach((i) => {
                        const o = document.createElement('option');
                        o.value = i.id;
                        o.textContent = i.name;
                        select.appendChild(o);
                    });
                    if (preferred && items.some((i) => String(i.id) === String(preferred))) select.value = preferred;
                }

                function applyBranch(preferWh, preferCb) {
                    const b = branchSel.value;
                    fillSelect(whSel, warehouses.filter((w) => String(w.branche_id) === String(b)), preferWh);
                    if (cbSel) fillSelect(cbSel, cashboxes.filter((c) => String(c.branche_id) === String(b)), preferCb);
                    updateSummary();
                    updateQrPreview();
                }

                // Muestra en el modal de pago con QR el código de la sucursal seleccionada
                function updateQrPreview() {
                    const img = $('qr-modal-image'), wrapper = $('qr-modal-image-wrapper');
                    const caption = $('qr-modal-caption'), empty = $('qr-modal-empty');
                    if (!img || !wrapper || !caption || !empty) return;

                    const url = branchQr[branchSel.value];
                    if (url) {
                        img.src = url;
                        wrapper.classList.remove('hidden');
                        caption.classList.remove('hidden');
                        empty.classList.add('hidden');
                    } else {
                        img.src = '';
                        wrapper.classList.add('hidden');
                        caption.classList.add('hidden');
                        empty.classList.remove('hidden');
                    }
                }

                function updateSummary() {
                    const t = (sel) => sel.options[sel.selectedIndex]?.textContent || '—';
                    $('location-summary').textContent = [t(branchSel), t(whSel), cbSel ? t(cbSel) : null].filter(Boolean).join(' · ');
                }

                // ── Stock ────────────────────────────────────────────────
                // Cada línea del carrito puede salir de un almacén distinto (no necesariamente el de "Dónde se
                // vende"), así que todas estas funciones reciben el almacén a consultar en vez de usar siempre
                // whSel.value. El stock/costo por almacén ya viene incluido en el propio objeto "unit" (lo
                // trae el servidor en la búsqueda), no en un mapa global como antes.
                const baseStock = (unit, warehouseId) => Number(unit.stock_by_warehouse?.[warehouseId] ?? 0);

                // Costo de una unidad de esta presentación (costo por unidad base × lo que trae) en el almacén elegido.
                // Solo lo ve y lo respeta quien puede cambiar precios; sin costo conocido no hay piso.
                const costFor = (unit, warehouseId) => canEditPrice ? round2(Number(unit.cost_by_warehouse?.[warehouseId] ?? 0) * Number(unit.factor)) : 0;

                // Desglose del stock de un producto por presentaciones, de la más grande a la más chica:
                // 100 unidades base con "Caja x24" -> "4 Caja x24 + 4 Unidad". Todo sale del mismo stock base.
                function breakdown(unit, warehouseId) {
                    const total = baseStock(unit, warehouseId);
                    if (total <= 0) return 'Sin stock';
                    const parts = [];
                    let rest = total;
                    (unit.siblings && unit.siblings.length ? unit.siblings : [unit]).slice()
                        .sort((a, b) => b.factor - a.factor)
                        .forEach((u) => {
                            const n = Math.floor(rest / u.factor + 1e-9);
                            if (n > 0) { parts.push(`${n} ${u.unit}`); rest = Math.round((rest - n * u.factor) * 100) / 100; }
                        });
                    if (rest > 0) parts.push(`${rest} suelta(s)`);
                    return parts.join(' + ') + (parts.length > 1 ? ` (${total} en total)` : '');
                }

                function availableUnits(unit, warehouseId) {
                    return Math.floor(baseStock(unit, warehouseId) / unit.factor);
                }

                // Suma el stock de un producto en TODOS los almacenes (para no bloquear agregarlo solo porque
                // el almacén por defecto esté en 0: cada línea elige su propio almacén después de agregada).
                function totalStockAllWarehouses(unit) {
                    const perWarehouse = unit.stock_by_warehouse || {};
                    return Object.values(perWarehouse).reduce((sum, v) => sum + (Number(v) || 0), 0);
                }

                // Solo compiten por el mismo stock las líneas del mismo producto que además comparten almacén.
                function committedBase(productId, warehouseId, exceptLine = null) {
                    return lines.filter((l) => l !== exceptLine && l.unit.product_id === productId && String(l.warehouseId) === String(warehouseId))
                        .reduce((s, l) => s + (Number(l.qty) || 0) * l.unit.factor, 0);
                }

                function maxFor(line) {
                    const free = baseStock(line.unit, line.warehouseId) - committedBase(line.unit.product_id, line.warehouseId, line);
                    return Math.floor(free / line.unit.factor);
                }

                // ── Cálculo de una línea ─────────────────────────────────
                function calc(line) {
                    const qty = Number(line.qty) || 0;
                    const price = Number(line.price);
                    const gross = round2(qty * (isNaN(price) ? 0 : price));
                    const v = Number(line.discValue) || 0;
                    let discount = 0;
                    if (v > 0) discount = line.discType === 'percent' ? round2(gross * Math.min(v, 100) / 100) : round2(v);
                    const errors = [];
                    if (qty <= 0) errors.push('cantidad');
                    else if (!Number.isInteger(qty)) errors.push('La cantidad debe ser un número entero');
                    if (enforceStock && qty > maxFor(line) + 0.0001) errors.push(`Stock insuficiente (máx. ${Math.max(maxFor(line), 0)})`);
                    if (line.price === '' || isNaN(price) || price < 0) errors.push('Precio no válido');
                    else {
                        // El precio que el usuario cambie no puede quedar por debajo del costo (el del catálogo, sin tocar, se respeta)
                        const cost = costFor(line.unit, line.warehouseId);
                        if (cost > 0 && price < cost - 0.004 && Math.abs(price - line.unit.price) > 0.004) errors.push(`El precio no puede ser menor al costo (${money(cost)})`);
                    }
                    if (line.discType === 'percent' && v > 100) errors.push('Descuento mayor al 100%');
                    else if (discount > gross + 0.001) errors.push('Descuento mayor al subtotal');
                    const short = !enforceStock && qty > maxFor(line) + 0.0001;
                    return {gross, discount, net: round2(gross - Math.min(discount, gross)), errors, warn: short ? `Hoy hay ${Math.max(maxFor(line), 0)} disponible(s) en este almacén` : ''};
                }

                // ── Búsqueda de productos ────────────────────────────────
                // Se pide al servidor (como mucho 15 a la vez, ver PosSearchController). "searchSeq" evita
                // que una respuesta que tardó más pise en pantalla a otra búsqueda más nueva ya resuelta.
                let searchSeq = 0;
                let lastResults = []; // lo último que se mostró: lo usa Enter para "elegir el primero" sin pedir de nuevo
                function matches(q) {
                    return fetchUnits({q});
                }

                // Marca / Color / Medida, si el producto los tiene, para mostrarlos junto al SKU.
                function variantLabel(u) {
                    return [u.marca, u.color, u.medida].filter(Boolean).join(' · ');
                }

                function paintResults(list) {
                    lastResults = list;
                    results.innerHTML = '';
                    if (!list.length) {
                        results.innerHTML = '<div class="px-3 py-3 text-sm text-gray-500">Sin resultados</div>';
                    }
                    list.forEach((u) => {
                        const avail = availableUnits(u, whSel.value);
                        const b = document.createElement('button');
                        b.type = 'button';
                        b.className = 'w-full text-left px-3 py-2 flex items-center justify-between gap-3 hover:bg-emerald-50 dark:hover:bg-slate-700 disabled:opacity-50';
                        // No se bloquea solo por el almacén por defecto: si hay stock en OTRO almacén, se puede
                        // agregar igual y después elegir ese almacén en la línea.
                        b.disabled = enforceStock && totalStockAllWarehouses(u) <= 0;

                        const imgHtml = u.photo_url
                            ? `<div class="relative group/searchimg shrink-0 cursor-zoom-in" onclick="event.stopPropagation(); openImagePreview('${u.photo_url}', '${u.name.replace(/'/g, "\\'")}')" title="Clic para ampliar imagen">
                                <img src="${u.photo_url}" class="w-14 h-14 object-cover rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-slate-800 shadow-sm group-hover/searchimg:ring-2 group-hover/searchimg:ring-emerald-500 group-hover/searchimg:scale-105 transition-all" alt="">
                                <div class="absolute inset-0 bg-black/25 opacity-0 group-hover/searchimg:opacity-100 rounded-xl flex items-center justify-center transition-opacity">
                                    <svg class="w-4 h-4 text-white drop-shadow" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m3-3H7"/></svg>
                                </div>
                               </div>`
                            : `<div class="w-14 h-14 rounded-xl border border-dashed border-gray-300 dark:border-slate-700 bg-gray-50 dark:bg-slate-800 flex items-center justify-center text-gray-400 shrink-0"><svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg></div>`;

                        b.innerHTML = `
                            <div class="flex items-center gap-3.5 min-w-0 flex-1">
                                ${imgHtml}
                                <div class="min-w-0 flex-1">
                                    <span class="block text-sm font-semibold text-gray-900 dark:text-white truncate res-title"></span>
                                    <span class="block text-xs text-gray-500 res-sku mt-0.5 truncate"></span>
                                    <span class="block text-xs text-emerald-700 dark:text-emerald-400 res-avail mt-0.5"></span>
                                </div>
                            </div>
                            <span class="text-right shrink-0 ml-4"><span class="block text-base font-bold text-gray-900 dark:text-white">${money(u.price)}</span><span class="block text-[11px] ${avail > 0 ? 'text-emerald-600' : 'text-red-500'}">${avail > 0 ? 'Stock: ' + avail : 'Sin stock'}</span></span>`;
                        b.querySelector('.res-title').textContent = `${u.name} — ${u.unit}`;
                        b.querySelector('.res-sku').textContent = u.sku + (u.barcode && u.barcode !== u.sku ? ' · ' + u.barcode : '') + (variantLabel(u) ? ' · ' + variantLabel(u) : '');
                        b.querySelector('.res-avail').textContent = 'Hay: ' + breakdown(u, whSel.value);
                        b.addEventListener('click', () => addUnit(u));
                        results.appendChild(b);
                    });
                    results.classList.remove('hidden');
                }

                let searchDebounce = null;
                function renderResults() {
                    const q = search.value;
                    const seq = ++searchSeq;
                    clearTimeout(searchDebounce);
                    results.classList.remove('hidden');
                    // El buscador vacío (al enfocar) trae la lista de entrada sin esperar; escribiendo, se
                    // espera un poquito a que la persona termine de tipear antes de golpear al servidor.
                    searchDebounce = setTimeout(async () => {
                        const list = await matches(q);
                        if (seq !== searchSeq) return; // llegó una búsqueda más nueva mientras esperábamos esta
                        paintResults(list);
                    }, q.trim() ? 200 : 0);
                }

                // Agrega el producto como renglón; si ya estaba, suma la cantidad. Devuelve qué pasó:
                // 'added' | 'incremented' | 'max' (tope de stock) | 'nostock', con la cantidad resultante.
                function addUnit(unit, qty = 1, focus = true) {
                    // Solo se bloquea si NINGÚN almacén tiene stock; si hay en otro, se agrega igual (con el
                    // almacén por defecto) y el usuario cambia el almacén de esa línea si hace falta.
                    if (enforceStock && totalStockAllWarehouses(unit) <= 0) return {status: 'nostock', qty: 0};
                    // Dos líneas del mismo producto solo se consideran "la misma" si además comparten almacén.
                    const existing = lines.find((l) => l.unit.id === unit.id && String(l.warehouseId) === String(whSel.value));
                    let result;
                    if (existing) {
                        const next = Number(existing.qty) + qty;
                        if (!enforceStock || next <= maxFor(existing) + 0.0001) {
                            existing.qty = next;
                            result = {status: 'incremented', qty: next};
                        } else {
                            result = {status: 'max', qty: Number(existing.qty)};
                        }
                    } else {
                        lines.push({unit, qty, price: unit.price, discType: 'percent', discValue: '', warehouseId: whSel.value});
                        result = {status: 'added', qty};
                    }
                    search.value = '';
                    results.classList.add('hidden');
                    renderLines();
                    if (focus) focusSearchQuietly();
                    return result;
                }

                // ── Lector de códigos (barras / QR): con pistola lectora o con la cámara ───────
                let toastTimer = null;
                function scanToast(message, ok) {
                    const el = $('scan-toast');
                    el.textContent = message;
                    el.className = 'fixed bottom-4 right-4 z-[70] max-w-xs px-4 py-2.5 rounded-lg shadow-xl text-sm font-semibold text-white ' + (ok ? 'bg-emerald-600' : 'bg-red-600');
                    clearTimeout(toastTimer);
                    toastTimer = setTimeout(() => el.classList.add('hidden'), 2500);
                }

                function beep(ok) {
                    try {
                        const ctx = new (window.AudioContext || window.webkitAudioContext)();
                        const osc = ctx.createOscillator(), gain = ctx.createGain();
                        osc.frequency.value = ok ? 880 : 220;
                        gain.gain.value = 0.05;
                        osc.connect(gain); gain.connect(ctx.destination);
                        osc.start(); osc.stop(ctx.currentTime + (ok ? 0.08 : 0.25));
                        setTimeout(() => ctx.close(), 400);
                    } catch (e) { /* sin audio: no pasa nada */ }
                }

                // Busca el producto por código de barras o SKU exactos (consulta al servidor); un QR puede
                // traer más texto (URL, etc.), así que también se prueba cada "palabra" del texto leído.
                async function findByCode(raw) {
                    const code = String(raw).trim();
                    if (!code) return null;
                    const direct = (await fetchUnits({code}))[0];
                    if (direct) return direct;
                    for (const token of code.split(/[^A-Za-z0-9._-]+/).filter((t) => t.length >= 3)) {
                        const hit = (await fetchUnits({code: token}))[0];
                        if (hit) return hit;
                    }
                    return null;
                }

                // Avisa (toast + beep) el resultado de agregar una unidad; lo usan tanto el escaneo como
                // Enter en el buscador cuando lo tecleado es un código exacto.
                function announceAddResult(unit, r) {
                    const name = `${unit.name} — ${unit.unit}`;
                    const ok = r.status === 'added' || r.status === 'incremented';
                    const message = {
                        added: `✓ ${name} agregado`,
                        incremented: `✓ ${name} · cantidad ${r.qty}`,
                        max: `${name}: stock máximo (${r.qty})`,
                        nostock: `${name}: sin stock`,
                    }[r.status];
                    beep(ok);
                    scanToast(message, ok);
                    return {ok, message};
                }

                // Un escaneo agrega el producto como renglón y cada nuevo escaneo del mismo suma 1 a la cantidad.
                async function handleScan(raw, fromCamera = false) {
                    const code = String(raw).trim();
                    if (!code) return null;
                    const unit = await findByCode(code);
                    if (!unit) {
                        beep(false);
                        scanToast('Código no encontrado: ' + code, false);
                        return {ok: false, message: 'Código no encontrado: ' + code};
                    }
                    return announceAddResult(unit, addUnit(unit, 1, !fromCamera));
                }

                // Pistola lectora (USB/Bluetooth): teclea el código muy rápido y termina con Enter. Se detecta esa
                // ráfaga en cualquier parte de la pantalla, así el escaneo funciona aunque el cursor no esté en el buscador.
                (function handheldScanner() {
                    let burst = {chars: '', last: 0, target: null, saved: null};
                    document.addEventListener('keydown', (e) => {
                        if (e.ctrlKey || e.metaKey || e.altKey) return;
                        const t = e.target;
                        if (t === search || t.closest('#customer-modal, #scan-modal') || ['SELECT', 'TEXTAREA'].includes(t.tagName)) return; // el buscador ya lo maneja
                        const now = performance.now();

                        if (e.key === 'Enter') {
                            const isScan = burst.chars.length >= 4 && now - burst.last < 120;
                            const code = burst.chars;
                            const {target, saved} = burst;
                            burst = {chars: '', last: 0, target: null, saved: null};
                            if (!isScan) return;
                            e.preventDefault();
                            // Los dígitos del código cayeron en el campo enfocado: se devuelve lo que tenía.
                            if (target && saved !== null && target.tagName === 'INPUT' && target.value !== saved) {
                                target.value = saved;
                                target.dispatchEvent(new Event('input', {bubbles: true}));
                            }
                            handleScan(code);
                            return;
                        }

                        if (e.key.length !== 1) return;
                        if (burst.chars === '' || now - burst.last > 60) {
                            burst = {chars: '', last: now, target: t, saved: t.tagName === 'INPUT' ? t.value : null};
                        }
                        burst.chars += e.key;
                        burst.last = now;
                    }, true);
                })();

                // Cámara: se mantiene abierta para escanear varios productos seguidos.
                (function cameraScanner() {
                    const modal = $('scan-modal'), feedback = $('scan-feedback');
                    let camera = null, lastCode = '', lastSeen = 0, starting = false;

                    const setFeedback = (text, ok) => {
                        feedback.textContent = text;
                        feedback.className = 'mt-3 p-2.5 rounded-lg text-xs font-medium text-center ' + (ok === null ? 'bg-gray-50 dark:bg-slate-800 text-gray-600 dark:text-gray-300' : ok ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700');
                    };

                    function loadLib() {
                        if (window.Html5Qrcode) return Promise.resolve();
                        return new Promise((resolve, reject) => {
                            const script = document.createElement('script');
                            script.src = 'https://unpkg.com/html5-qrcode';
                            script.onload = resolve;
                            script.onerror = () => reject(new Error('No se pudo cargar el lector (¿sin internet?).'));
                            document.head.appendChild(script);
                        });
                    }

                    async function open() {
                        if (starting || camera) return;
                        starting = true;
                        modal.classList.remove('hidden');
                        setFeedback('Iniciando cámara...', null);
                        try {
                            await loadLib();
                            const cameras = await Html5Qrcode.getCameras();
                            if (!cameras || !cameras.length) throw new Error('No se detectó ninguna cámara.');
                            const back = cameras.find((c) => /back|rear|trasera|environment/i.test(c.label || ''));
                            camera = new Html5Qrcode('scan-reader');
                            const wanted = ['QR_CODE', 'EAN_13', 'EAN_8', 'UPC_A', 'UPC_E', 'CODE_128', 'CODE_39', 'CODABAR', 'ITF'];
                            const formats = wanted.map((n) => Html5QrcodeSupportedFormats[n]).filter((v) => v !== undefined);
                            await camera.start((back || cameras[0]).id, {
                                fps: 12,
                                qrbox: (w, h) => ({width: Math.floor(w * 0.85), height: Math.max(160, Math.floor(h * 0.6))}),
                                ...(formats.length ? {formatsToSupport: formats} : {}),
                            }, onDecode, () => {
                                // Sin código en el cuadro: si pasó un momento, el siguiente código igual cuenta como uno nuevo.
                                if (Date.now() - lastSeen > 800) lastCode = '';
                            });
                            setFeedback('Apunta la cámara al código.', null);
                        } catch (err) {
                            camera = null;
                            setFeedback((err && err.message) || 'No se pudo abrir la cámara (necesita permiso y conexión segura).', false);
                        } finally {
                            starting = false;
                        }
                    }

                    async function onDecode(text) {
                        lastSeen = Date.now();
                        if (text === lastCode) return; // el mismo código sigue a la vista: no se cuenta de nuevo
                        lastCode = text;
                        const res = await handleScan(text, true);
                        if (res) setFeedback(res.message, res.ok);
                    }

                    async function close() {
                        modal.classList.add('hidden');
                        if (camera) {
                            try { await camera.stop(); camera.clear(); } catch (e) { /* ya estaba detenida */ }
                            camera = null;
                        }
                        lastCode = '';
                        focusSearchQuietly();
                    }

                    $('scan-camera-btn').addEventListener('click', open);
                    $('scan-modal-close').addEventListener('click', close);
                    $('scan-modal-done').addEventListener('click', close);
                })();

                search.addEventListener('input', renderResults);
                search.addEventListener('focus', () => {
                    if (suppressFocusSearch) { suppressFocusSearch = false; return; }
                    renderResults();
                });
                // Un código exacto (lector o tecleado) se trata como escaneo: agrega o suma 1 a la cantidad.
                // Si no es un código, se usa el primero de lo que ya está mostrado en el desplegable (o se
                // pide de nuevo si todavía no se mostró nada, p. ej. si se tipeó y hay Enter muy rápido).
                async function handleEnterSearch(q) {
                    const exact = await findByCode(q);
                    if (exact) { announceAddResult(exact, addUnit(exact, 1, true)); return; }
                    const list = lastResults.length ? lastResults : await matches(q);
                    const pick = list[0];
                    if (pick) addUnit(pick);
                    else { beep(false); scanToast('Sin resultados para: ' + q, false); }
                }

                search.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter') {
                        e.preventDefault(); // Enter en el buscador nunca envía el formulario
                        const q = search.value.trim();
                        if (!q) return;
                        handleEnterSearch(q);
                    } else if (e.key === 'Escape') {
                        results.classList.add('hidden');
                    }
                });
                document.addEventListener('click', (e) => {
                    if (!$('product-search-wrapper').contains(e.target)) results.classList.add('hidden');
                    if (!$('customer-search-box').contains(e.target)) $('customer-results').classList.add('hidden');
                });

                // ── Líneas ───────────────────────────────────────────────
                function renderLines() {
                    body.innerHTML = '';
                    empty.classList.toggle('hidden', lines.length > 0);

                    lines.forEach((line, index) => {
                        const tr = document.createElement('tr');
                        const imgHtml = line.unit.photo_url
                            ? `<div class="relative group/lineimg shrink-0 cursor-zoom-in" onclick="openImagePreview('${line.unit.photo_url}', '${line.unit.name.replace(/'/g, "\\'")}')" title="Clic para ampliar imagen">
                                <img src="${line.unit.photo_url}" class="w-14 h-14 object-cover rounded-xl shrink-0 border border-gray-200 dark:border-gray-700 bg-white dark:bg-slate-800 shadow-sm group-hover/lineimg:ring-2 group-hover/lineimg:ring-emerald-500 group-hover/lineimg:scale-105 transition-all" alt="">
                                <div class="absolute inset-0 bg-black/25 opacity-0 group-hover/lineimg:opacity-100 rounded-xl flex items-center justify-center transition-opacity">
                                    <svg class="w-4 h-4 text-white drop-shadow" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m3-3H7"/></svg>
                                </div>
                               </div>`
                            : `<div class="w-14 h-14 rounded-xl border border-dashed border-gray-300 dark:border-slate-700 bg-gray-50 dark:bg-slate-800 flex items-center justify-center text-gray-400 shrink-0"><svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg></div>`;

                        tr.innerHTML = `
                            <td class="px-3 py-2 text-center w-20 align-middle">
                                <div class="flex items-center justify-center">
                                    ${imgHtml}
                                </div>
                            </td>
                            <td class="px-3 py-2 align-middle">
                                <div class="min-w-0">
                                    <span class="block text-gray-900 dark:text-white font-medium name text-sm leading-snug"></span>
                                    <span class="block text-xs text-gray-500 meta mt-0.5"></span>
                                    <select class="wh-select mt-1 h-7 px-1.5 text-[11px] rounded-md border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-700 dark:text-gray-200" title="Almacén de donde sale esta línea"></select>
                                    <span class="block text-[11px] text-red-600 font-medium err mt-0.5"></span>
                                </div>
                            </td>
                            <td class="px-3 py-2 align-middle"><div class="flex items-center justify-center">
                                <button type="button" class="${btnCls} rounded-l-lg minus">−</button>
                                <input type="number" step="1" min="1" inputmode="numeric" placeholder="1" onkeydown="if (['.', ',', 'e', 'E', '-', '+'].includes(event.key)) event.preventDefault()" class="${stepInput} qty">
                                <button type="button" class="${btnCls} rounded-r-lg plus">+</button></div></td>
                            <td class="px-3 py-2 text-right price-cell align-middle"></td>
                            <td class="px-3 py-2 align-middle"><div class="flex items-center justify-center gap-1 disc-cell"></div></td>
                            <td class="px-3 py-2 text-right font-semibold text-gray-900 dark:text-white subtotal align-middle"></td>
                            <td class="px-3 py-2 text-right align-middle"><button type="button" class="text-red-500 hover:text-red-700 text-xl leading-none remove" title="Quitar">&times;</button></td>`;
                        tr.querySelector('.name').textContent = line.unit.name;
                        tr.querySelector('.meta').textContent = line.unit.unit + ' · ' + line.unit.sku + (variantLabel(line.unit) ? ' · ' + variantLabel(line.unit) : '');

                        // Almacén de esta línea: por defecto el de "Dónde se vende", pero se puede cambiar a
                        // cualquier otro (no solo los de la sucursal elegida) — así se puede vender cruzando almacenes.
                        const whLineSel = tr.querySelector('.wh-select');
                        if (!line.warehouseId) line.warehouseId = whSel.value;
                        warehouses.forEach((w) => {
                            const o = document.createElement('option');
                            o.value = w.id;
                            o.textContent = `${w.name} (${availableUnits(line.unit, w.id)})`;
                            whLineSel.appendChild(o);
                        });
                        whLineSel.value = line.warehouseId;
                        whLineSel.addEventListener('change', () => { line.warehouseId = whLineSel.value; refresh(); });

                        const qty = tr.querySelector('.qty');
                        qty.value = line.qty;
                        qty.addEventListener('input', () => { line.qty = qty.value; refresh(); });
                        // Al salir del campo la cantidad se redondea a un entero de al menos 1.
                        qty.addEventListener('change', () => { line.qty = Math.max(1, Math.round(Number(qty.value)) || 1); qty.value = line.qty; refresh(); });
                        tr.querySelector('.minus').addEventListener('click', () => { line.qty = Math.max(1, (Math.round(Number(line.qty)) || 0) - 1); qty.value = line.qty; refresh(); });
                        tr.querySelector('.plus').addEventListener('click', () => { line.qty = (Math.round(Number(line.qty)) || 0) + 1; qty.value = line.qty; refresh(); });
                        tr.querySelector('.remove').addEventListener('click', () => { lines.splice(index, 1); renderLines(); focusSearchQuietly(); });

                        // Precio: editable solo con permiso
                        const priceCell = tr.querySelector('.price-cell');
                        if (canEditPrice) {
                            const p = document.createElement('input');
                            p.type = 'number'; p.step = '0.01'; p.min = '0'; p.value = line.price;
                            p.className = cellInput + ' w-24 price-input';
                            p.title = `Precio de catálogo: ${money(line.unit.price)}`;
                            p.addEventListener('input', () => { line.price = p.value; refresh(); });
                            // Al salir del campo, un precio por debajo del costo sube al costo (no se puede bajar de ahí)
                            p.addEventListener('change', () => {
                                const cost = costFor(line.unit, line.warehouseId);
                                const v = Number(p.value);
                                if (cost > 0 && v < cost - 0.004 && Math.abs(v - line.unit.price) > 0.004) {
                                    p.value = cost.toFixed(2);
                                    line.price = p.value;
                                }
                                refresh();
                            });
                            const minCost = costFor(line.unit, line.warehouseId);
                            if (minCost > 0) p.min = String(minCost);
                            // El input queda solo en el flujo de la celda (centrado como cantidad y descuento); las etiquetas van debajo, sin empujarlo.
                            const priceWrap = document.createElement('div');
                            priceWrap.className = 'price-wrap relative ml-auto w-24';
                            priceWrap.appendChild(p);
                            priceCell.appendChild(priceWrap);
                            priceCell.style.height = '84px'; // espacio fijo para que todas las filas queden parejas
                            // Referencias del precio: catálogo (un clic vuelve a ese precio) y costo (piso del precio)
                            const hint = document.createElement('div');
                            hint.className = 'price-hint absolute right-0 top-full mt-1 flex gap-1 whitespace-nowrap';
                            const catalogChip = document.createElement('button');
                            catalogChip.type = 'button';
                            catalogChip.className = 'catalog-chip inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md border border-slate-200 dark:border-slate-600 bg-slate-100 dark:bg-slate-700 text-[10px] font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors';
                            catalogChip.title = 'Volver al precio de catálogo';
                            catalogChip.innerHTML = '<span class="uppercase tracking-wide text-slate-400 dark:text-slate-400">Catálogo</span><span class="font-semibold tabular-nums"></span>';
                            catalogChip.addEventListener('click', () => { line.price = line.unit.price; p.value = line.unit.price; refresh(); });
                            const costChip = document.createElement('span');
                            costChip.className = 'cost-chip inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md border border-amber-200 dark:border-amber-800/60 bg-amber-50 dark:bg-amber-900/20 text-[10px] font-medium text-amber-700 dark:text-amber-300';
                            costChip.title = 'Costo del producto: el precio no puede quedar por debajo';
                            costChip.innerHTML = '<span class="uppercase tracking-wide text-amber-500 dark:text-amber-400">Costo</span><span class="font-semibold tabular-nums"></span>';
                            hint.append(catalogChip, costChip);
                            priceWrap.appendChild(hint);
                        } else {
                            priceCell.textContent = money(line.unit.price);
                            priceCell.className += ' text-gray-700 dark:text-gray-300';
                        }

                        // Descuento por línea: % o monto, solo con permiso
                        const discCell = tr.querySelector('.disc-cell');
                        if (canDiscount) {
                            const d = document.createElement('input');
                            d.type = 'number'; d.step = '0.01'; d.min = '0'; d.placeholder = '0';
                            d.value = line.discValue;
                            d.className = cellInput + ' w-16 disc-input';
                            d.addEventListener('input', () => { line.discValue = d.value; refresh(); });
                            const t = document.createElement('button');
                            t.type = 'button';
                            t.className = 'h-9 w-9 text-xs font-bold rounded-lg border border-gray-300 dark:border-slate-600 bg-gray-50 dark:bg-slate-700 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-slate-600';
                            t.title = 'Cambiar entre porcentaje y monto';
                            const paint = () => { t.textContent = line.discType === 'percent' ? '%' : 'Bs'; };
                            paint();
                            t.addEventListener('click', () => { line.discType = line.discType === 'percent' ? 'amount' : 'percent'; paint(); refresh(); });
                            discCell.append(d, t);
                        } else {
                            discCell.textContent = '—';
                            discCell.className += ' text-gray-400';
                        }

                        line.row = tr;
                        body.appendChild(tr);
                    });

                    refresh();
                }

                function refresh() {
                    let gross = 0, discount = 0, net = 0, valid = lines.length > 0;
                    const problems = [];

                    lines.forEach((line) => {
                        const c = calc(line);
                        gross += c.gross; discount += Math.min(c.discount, c.gross); net += c.net;
                        line.row.querySelector('.subtotal').textContent = money(c.net);
                        const errText = c.errors.filter((e) => e !== 'cantidad').join(' · ');
                        const errEl = line.row.querySelector('.err');
                        errEl.textContent = errText || c.warn;
                        errEl.classList.toggle('text-red-600', !!errText);
                        errEl.classList.toggle('text-amber-600', !errText && !!c.warn);
                        line.row.querySelector('.qty').classList.toggle('text-red-600', c.errors.length > 0);
                        const hint = line.row.querySelector('.price-hint');
                        if (hint) {
                            const cost = costFor(line.unit, line.warehouseId);
                            hint.querySelector('.catalog-chip span:last-child').textContent = money(line.unit.price);
                            const costChip = hint.querySelector('.cost-chip');
                            costChip.classList.toggle('hidden', !(cost > 0));
                            costChip.querySelector('span:last-child').textContent = money(cost);
                            // El precio cambiado a mano resalta la etiqueta de catálogo para poder volver de un clic
                            const changed = Math.abs((Number(line.price) || 0) - Number(line.unit.price)) > 0.004;
                            hint.querySelector('.catalog-chip').classList.toggle('ring-1', changed);
                            hint.querySelector('.catalog-chip').classList.toggle('ring-sky-400', changed);
                        }
                        if (c.errors.length) {
                            valid = false;
                            if (errText) problems.push(`${line.unit.name}: ${errText}`);
                        }
                    });

                    net = round2(net);
                    if (net <= 0) valid = false;

                    if (mode === 'consignment' && !$('customer-id').value) {
                        valid = false;
                        problems.push('Elige el cliente que recibe la consignación');
                    }

                    let debt = net;
                    if (mode === 'sale' && saleType === 'credit') {
                        const cr = creditState(net);
                        debt = cr.debt;
                        if (cr.msg) { valid = false; problems.push(cr.msg); }
                    }

                    totalEl.textContent = money(net);
                    $('gross').textContent = money(gross);
                    $('discount-total').textContent = '− ' + money(discount);
                    $('gross-row').classList.toggle('hidden', discount <= 0);
                    $('discount-row').classList.toggle('hidden', discount <= 0);
                    $('items-count').textContent = lines.length;
                    warning.textContent = problems.join(' · ');
                    warning.classList.toggle('hidden', problems.length === 0);
                    submitBtn.disabled = !valid || !whSel.value || (cbSel && !cbSel.value);
                    const credit = mode === 'sale' && saleType === 'credit';
                    const baseLabel = mode === 'sale' ? (credit ? 'Registrar a crédito' : 'Cobrar') : submitLabel;
                    submitBtn.textContent = lines.length && valid ? (credit ? `${baseLabel} · saldo ${money(debt)}` : (mode === 'sale' ? `${baseLabel} ${money(net)}` : `${baseLabel} · ${money(net)}`)) : baseLabel;
                    if (credit) $('credit-debt').textContent = net > 0 ? `Saldo a crédito: ${money(debt)}` : '';
                    updateChange(net);
                }

                // ── Venta a crédito ──────────────────────────────────────
                function creditState(net) {
                    const c = selectedCustomer;
                    const initial = Number($('initial-payment')?.value) || 0;
                    const debt = round2(net - initial);
                    const due = $('due-date')?.value;
                    let msg = '';

                    if (!c) msg = 'La venta a crédito necesita un cliente';
                    else if (!due || due <= today) msg = 'Indica un vencimiento posterior a hoy';
                    else if (initial < 0 || (net > 0 && initial >= net)) msg = 'El anticipo debe ser menor al total (si paga todo, es contado)';
                    else if (c.credit_limit !== null && c.owed + debt > c.credit_limit + 0.004) msg = `Se pasa del límite de crédito (disponible ${money(Math.max(0, c.credit_limit - c.owed))})`;

                    $('credit-info').textContent = c
                        ? `${c.name}: debe ${money(c.owed)} · límite ${c.credit_limit === null ? 'sin límite' : money(c.credit_limit)}`
                        : 'Elige un cliente para vender a crédito.';

                    return {debt, msg};
                }

                function setType(type) {
                    saleType = type;
                    $('payment-type').value = type;
                    document.querySelectorAll('.type-btn').forEach((b) => {
                        const on = b.dataset.type === type;
                        b.className = 'type-btn h-9 text-xs font-bold rounded-md ' + (on ? 'bg-emerald-600 text-white shadow' : 'text-gray-600 dark:text-gray-300 hover:bg-white dark:hover:bg-slate-700');
                    });
                    $('credit-fields').classList.toggle('hidden', type !== 'credit');
                    ['due-date', 'initial-payment'].forEach((id) => { $(id).disabled = type !== 'credit'; });
                    syncPaymentBlock();
                    if (ready) refresh();
                }

                // El método de pago se pide al cobrar: siempre en contado, y en crédito solo si hay anticipo.
                function syncPaymentBlock() {
                    const needsPayment = saleType === 'cash' || (Number($('initial-payment')?.value) || 0) > 0;
                    $('payment-block')?.classList.toggle('hidden', !needsPayment);
                }

                // ── Efectivo recibido / vuelto ───────────────────────────
                function isCash() { return paySel?.options[paySel.selectedIndex]?.dataset.cash === '1'; }

                function currentTotal() {
                    return round2(lines.reduce((s, l) => s + calc(l).net, 0));
                }

                function updateChange(total = null) {
                    $('cash-helper')?.classList.toggle('hidden', !isCash() || saleType === 'credit');
                    const t = total ?? currentTotal();
                    const received = parseFloat($('received')?.value);
                    const el = $('change');
                    if (!el) return; // solo existe en ventas
                    if (!isCash() || isNaN(received)) { el.textContent = ''; return; }
                    const diff = round2(received - t);
                    el.textContent = diff >= 0 ? `Vuelto: ${money(diff)}` : `Falta: ${money(-diff)}`;
                    el.className = 'mt-1 text-sm font-semibold ' + (diff >= 0 ? 'text-emerald-600' : 'text-red-600');
                }

                // Enter en estos campos no debe cobrar la venta por accidente.
                ['received', 'note-input'].forEach((id) => $(id)?.addEventListener('keydown', (e) => { if (e.key === 'Enter') e.preventDefault(); }));

                paySel?.addEventListener('change', () => updateChange());
                $('received')?.addEventListener('input', () => updateChange());

                // ── Cliente (opcional) ───────────────────────────────────
                // El cliente elegido se guarda entero acá (no solo el id): así creditState() no necesita
                // volver a buscarlo en ningún listado completo, que ya no se manda al navegador.
                let selectedCustomer = null;

                function setCustomer(c) {
                    selectedCustomer = c || null;
                    $('customer-id').value = c ? c.id : '';
                    $('customer-chip').classList.toggle('hidden', !c);
                    $('customer-chip').classList.toggle('flex', !!c);
                    $('customer-search-box').classList.toggle('hidden', !!c);
                    if (c) $('customer-chip-text').textContent = c.name + (c.document ? ` (${docLabels[c.document_type] || ''} ${c.document})`.replace('( ', '(') : '');
                    $('customer-results').classList.add('hidden');
                    $('customer-search').value = '';
                    if (ready) refresh();
                }

                let lastCustomerResults = [];

                function paintCustomers(list) {
                    lastCustomerResults = list;
                    const box = $('customer-results');
                    box.innerHTML = '';
                    list.forEach((c) => {
                        const b = document.createElement('button');
                        b.type = 'button';
                        b.className = 'w-full text-left px-3 py-2 text-sm text-gray-800 dark:text-gray-100 hover:bg-emerald-50 dark:hover:bg-slate-700';
                        b.textContent = c.name + (c.document ? ' · ' + c.document : '');
                        b.addEventListener('click', () => setCustomer(c));
                        box.appendChild(b);
                    });
                    if (!list.length) box.innerHTML = '<div class="px-3 py-2 text-sm text-gray-500">Sin resultados (se venderá como cliente general)</div>';
                    box.classList.remove('hidden');
                }

                let customerSearchSeq = 0;
                let customerSearchDebounce = null;

                function renderCustomers() {
                    const q = $('customer-search').value;
                    const seq = ++customerSearchSeq;
                    clearTimeout(customerSearchDebounce);
                    customerSearchDebounce = setTimeout(async () => {
                        const list = await fetchCustomers({ q });
                        if (seq !== customerSearchSeq) return; // llegó una búsqueda más nueva mientras esperábamos esta
                        paintCustomers(list);
                    }, q.trim() ? 200 : 0);
                }

                $('customer-search').addEventListener('input', renderCustomers);
                $('customer-search').addEventListener('focus', renderCustomers);
                $('customer-search').addEventListener('keydown', (e) => { if (e.key === 'Enter') e.preventDefault(); });
                $('customer-clear').addEventListener('click', () => setCustomer(null));

                // ── Modal: crear cliente sin salir de la venta ───────────
                const modal = $('customer-modal');
                if (modal) {
                    const form = $('customer-form'), errBox = $('customer-form-errors'), saveBtn = $('customer-save');
                    const openModal = () => {
                        form.reset();
                        errBox.classList.add('hidden');
                        // Lo que ya se escribió en el buscador de clientes se usa como nombre inicial.
                        form.elements.name.value = $('customer-search').value.trim();
                        modal.classList.remove('hidden');
                        form.elements.name.focus();
                    };
                    const closeModal = () => { modal.classList.add('hidden'); focusSearchQuietly(); };

                    $('new-customer-btn').addEventListener('click', openModal);
                    $('customer-modal-close').addEventListener('click', closeModal);
                    $('customer-modal-cancel').addEventListener('click', closeModal);

                    form.addEventListener('submit', async (e) => {
                        e.preventDefault();
                        saveBtn.disabled = true;
                        saveBtn.textContent = 'Guardando...';
                        errBox.classList.add('hidden');

                        try {
                            const res = await fetch(customersUrl, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'X-CSRF-TOKEN': document.querySelector('#sale-form input[name=_token]').value,
                                },
                                body: JSON.stringify(Object.fromEntries(new FormData(form))),
                            });
                            const data = await res.json().catch(() => ({}));

                            if (res.status === 422) {
                                errBox.innerHTML = Object.values(data.errors || {}).flat().map((m) => `<div>${m.replace(/</g, '&lt;')}</div>`).join('');
                                errBox.classList.remove('hidden');
                                return;
                            }
                            if (!res.ok) throw new Error(data.message || 'No se pudo guardar el cliente.');

                            setCustomer(data);
                            closeModal();
                        } catch (err) {
                            errBox.textContent = err.message;
                            errBox.classList.remove('hidden');
                        } finally {
                            saveBtn.disabled = false;
                            saveBtn.textContent = 'Guardar y seleccionar';
                        }
                    });
                }

                // ── Cambios de sucursal / almacén ────────────────────────
                branchSel.addEventListener('change', () => { applyBranch(); renderLines(); saveLocation(); });
                whSel.addEventListener('change', () => { updateSummary(); renderLines(); saveLocation(); });
                cbSel?.addEventListener('change', () => { updateSummary(); refresh(); saveLocation(); });

                // ── Flujo de Cobro con QR y Comprobante ───────────────────
                function isQr() {
                    const text = (paySel?.options[paySel.selectedIndex]?.textContent || '').toLowerCase();
                    return text.includes('qr');
                }

                let receiptFileObj = null;
                const qrModal = $('qr-payment-modal');
                const btnCamera = $('btn-camera-receipt');
                const btnUpload = $('btn-upload-receipt');
                const inputCamera = $('input-receipt-camera');
                const inputFile = $('input-receipt-file');
                const previewWrapper = $('receipt-preview-wrapper');
                const previewImg = $('receipt-preview-img');
                const previewName = $('receipt-preview-name');
                const btnRemove = $('btn-receipt-remove');
                const qrError = $('qr-modal-error');
                const qrConfirmBtn = $('qr-modal-confirm');
                const qrCancelBtn = $('qr-modal-cancel');
                const qrCloseBtn = $('qr-modal-close');
                const saleFileInput = $('sale-receipt-file-input');

                function openQrModal() {
                    if (!qrModal) return;
                    const amountText = (mode === 'sale' && saleType === 'credit')
                        ? (Number($('initial-payment')?.value) || 0)
                        : currentTotal();
                    $('qr-modal-amount').textContent = 'Bs. ' + money(amountText);
                    qrError?.classList.add('hidden');
                    qrModal.classList.remove('hidden');
                }

                function closeQrModal() {
                    qrModal?.classList.add('hidden');
                }

                function handleReceiptSelected(file) {
                    if (!file) return;
                    if (!file.type.startsWith('image/')) {
                        if (qrError) {
                            qrError.textContent = 'El comprobante debe ser una imagen válida (JPG, PNG, WEBP).';
                            qrError.classList.remove('hidden');
                        }
                        return;
                    }
                    receiptFileObj = file;
                    if (qrError) qrError.classList.add('hidden');

                    const reader = new FileReader();
                    reader.onload = (e) => {
                        if (previewImg) previewImg.src = e.target.result;
                        if (previewName) previewName.textContent = file.name || 'comprobante.jpg';
                        previewWrapper?.classList.remove('hidden');
                    };
                    reader.readAsDataURL(file);

                    // Sincronizar con el input del formulario principal
                    try {
                        const dt = new DataTransfer();
                        dt.items.add(file);
                        if (saleFileInput) saleFileInput.files = dt.files;
                    } catch (e) {
                        // Fallback si DataTransfer no está disponible
                    }
                }

                btnCamera?.addEventListener('click', () => inputCamera?.click());
                btnUpload?.addEventListener('click', () => inputFile?.click());
                inputCamera?.addEventListener('change', (e) => {
                    if (e.target.files?.[0]) handleReceiptSelected(e.target.files[0]);
                });
                inputFile?.addEventListener('change', (e) => {
                    if (e.target.files?.[0]) handleReceiptSelected(e.target.files[0]);
                });

                btnRemove?.addEventListener('click', () => {
                    receiptFileObj = null;
                    if (saleFileInput) saleFileInput.value = '';
                    if (inputCamera) inputCamera.value = '';
                    if (inputFile) inputFile.value = '';
                    previewWrapper?.classList.add('hidden');
                });

                qrCancelBtn?.addEventListener('click', closeQrModal);
                qrCloseBtn?.addEventListener('click', closeQrModal);

                qrConfirmBtn?.addEventListener('click', () => {
                    if (!receiptFileObj && (!saleFileInput || !saleFileInput.files || !saleFileInput.files.length)) {
                        if (qrError) {
                            qrError.textContent = 'Debe tomar o subir una foto del comprobante de pago antes de continuar.';
                            qrError.classList.remove('hidden');
                        }
                        return;
                    }
                    closeQrModal();
                    // Envío del formulario
                    $('sale-form').requestSubmit ? $('sale-form').requestSubmit() : $('sale-form').submit();
                });

                // ── Envío ────────────────────────────────────────────────
                $('sale-form').addEventListener('submit', (e) => {
                    if (submitBtn.disabled) { e.preventDefault(); return; }

                    // Si el método de pago seleccionado es QR en una venta que requiere pago (contado o crédito con anticipo)
                    const needsPayment = mode === 'sale' && (saleType === 'cash' || (Number($('initial-payment')?.value) || 0) > 0);
                    if (needsPayment && isQr()) {
                        const hasReceipt = receiptFileObj || (saleFileInput && saleFileInput.files && saleFileInput.files.length > 0);
                        if (!hasReceipt) {
                            e.preventDefault();
                            openQrModal();
                            return;
                        }
                    }

                    document.querySelectorAll('.item-input').forEach((n) => n.remove());
                    lines.forEach((line, i) => {
                        const fields = [['product_unit_id', line.unit.id], ['warehouse_id', line.warehouseId], ['quantity', line.qty], ['unit_price', line.price]];
                        if ((Number(line.discValue) || 0) > 0) fields.push(['discount_type', line.discType], ['discount_value', line.discValue]);
                        fields.forEach(([k, v]) => {
                            const h = document.createElement('input');
                            h.type = 'hidden'; h.className = 'item-input';
                            h.name = `items[${i}][${k}]`; h.value = v;
                            $('sale-form').appendChild(h);
                        });
                    });
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'Registrando...';
                });

                if (canCredit) {
                    document.querySelectorAll('.type-btn').forEach((b) => b.addEventListener('click', () => setType(b.dataset.type)));
                    ['due-date', 'initial-payment'].forEach((id) => $(id).addEventListener('input', () => { syncPaymentBlock(); refresh(); }));
                }

                // ── Inicio ───────────────────────────────────────────────
                // El almacén guardado en este navegador solo se usa en un formulario nuevo (no si viene
                // de un error de validación, ni al editar un pedido que ya tiene su propio almacén).
                const savedLocation = old.warehouse ? null : loadSavedLocation();
                branchSel.value = savedLocation?.branch ?? old.branch ?? branchSel.value;
                applyBranch(old.warehouse || savedLocation?.warehouse, old.cashbox || savedLocation?.cashbox);
                // Las líneas ya elegidas (venir de un error de validación, o editar un pedido) se piden
                // puntualmente por id: no hace falta ni tiene sentido pedir el catálogo entero para esto.
                if ((old.items || []).length) {
                    const foundUnits = await fetchUnits({ids: old.items.map((it) => it.product_unit_id).join(',')});
                    const byId = new Map(foundUnits.map((u) => [String(u.id), u]));
                    old.items.forEach((it) => {
                        const u = byId.get(String(it.product_unit_id));
                        if (u) lines.push({
                            unit: u, qty: it.quantity,
                            price: it.unit_price ?? u.price,
                            discType: it.discount_type || 'percent',
                            discValue: it.discount_value || '',
                            warehouseId: it.warehouse_id || old.warehouse,
                        });
                    });
                }
                if (old.customer) setCustomer((await fetchCustomers({ids: old.customer}))[0] || null);
                if (!whSel.value || (cbSel && !cbSel.value)) $('location-details').open = true;
                renderLines();
                ready = true;
                if (canCredit) setType($('payment-type').value === 'credit' ? 'credit' : 'cash');
                refresh();
            })();
        </script>
    @endpush
