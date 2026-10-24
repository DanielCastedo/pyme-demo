@props([
    'action',
    'method' => 'POST',
    'quotation' => null,
    'warehouse' => null,
    'initialItems' => [],
])

@php
    $isEdit = !empty($quotation) && strtoupper($method) === 'PUT';
    // Igual que en una venta: solo con el permiso "ventas.editar-precio" se puede cotizar a otro precio del catálogo
    $canEditPrice = (bool) auth()->user()?->can('ventas.editar-precio');
@endphp

{{-- En pantallas chicas (< 768 px) cada producto se muestra como tarjeta en vez de fila de tabla,
     igual que en el formulario de venta. --}}
<style>
    @media (max-width: 767px) {
        #items-table, #items-table tbody { display: block; width: 100%; }
        #items-table thead { display: none; }
        #items-body > tr.item-row {
            display: grid;
            grid-template-columns: 64px 1fr 1fr 28px;
            gap: 10px 12px;
            align-items: center;
            padding: 14px 12px;
        }
        #items-body > tr.item-row > td { display: block; padding: 0 !important; height: auto !important; width: auto !important; text-align: left !important; min-width: 0; }
        /* Foto | Producto | Quitar */
        #items-body > tr.item-row > td:nth-child(1) { grid-column: 1; grid-row: 1; }
        #items-body > tr.item-row > td:nth-child(2) { grid-column: 2 / 4; grid-row: 1; }
        #items-body > tr.item-row > td:nth-child(6) { grid-column: 4; grid-row: 1; align-self: start; text-align: right !important; }
        /* Cantidad | Precio */
        #items-body > tr.item-row > td:nth-child(3) { grid-column: 1 / 3; grid-row: 2; }
        #items-body > tr.item-row > td:nth-child(4) { grid-column: 3 / 5; grid-row: 2; }
        /* Subtotal */
        #items-body > tr.item-row > td:nth-child(5) { grid-column: 1 / 5; grid-row: 3; text-align: right !important; font-size: 1.05rem; }
        #items-body > tr.item-row > td:nth-child(n+3):nth-child(-n+5)::before {
            display: block; margin-bottom: 4px; font-size: 10px; font-weight: 600; letter-spacing: .05em; text-transform: uppercase; color: #6b7280;
        }
        #items-body > tr.item-row > td:nth-child(3)::before { content: 'Cantidad'; }
        #items-body > tr.item-row > td:nth-child(4)::before { content: 'Precio'; }
        #items-body > tr.item-row > td:nth-child(5)::before { content: 'Subtotal'; text-align: right; }
        .dark #items-body > tr.item-row > td::before { color: #9ca3af; }
        #items-body .price-wrap { width: 100% !important; margin-left: 0 !important; }
        #items-body .price-input { width: 100% !important; }
        #items-body .price-hint { position: static !important; margin-top: 6px; flex-wrap: wrap; justify-content: flex-start; }
        #items-body td:nth-child(3) > div { justify-content: flex-start !important; }
    }
</style>

<form action="{{ $action }}" method="POST" id="quotation-form" class="grid grid-cols-1 lg:grid-cols-3 gap-5">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    {{-- ── Izquierda: productos ─────────────────────────────── --}}
    <div class="lg:col-span-2 space-y-4">
        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 p-4">
            <div class="relative" id="product-search-wrapper">
                <svg class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z" />
                </svg>
                <input type="text" id="product-search-input" autocomplete="off"
                    placeholder="Busca por nombre o código del producto..."
                    class="w-full h-12 pl-10 pr-4 text-sm rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                <div id="product-search-list"
                    class="hidden absolute left-0 top-full mt-1 z-30 w-full max-h-80 overflow-y-auto rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 shadow-xl">
                </div>
            </div>
            <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-2">
                @if ($canEditPrice)
                    Selecciona un producto de la lista para agregarlo. Las cantidades respetan el stock disponible; puedes cambiar el precio, pero no puede quedar por debajo del costo.
                @else
                    Selecciona un producto de la lista para agregarlo. Las cantidades respetan el stock disponible y el precio oficial de catálogo.
                @endif
            </p>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 overflow-hidden">
            <div class="overflow-x-auto">
                <table id="items-table" class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-slate-800 text-[11px] uppercase text-gray-500 dark:text-gray-400">
                        <tr>
                            <th class="px-3 py-2 text-center w-20">Foto</th>
                            <th class="px-3 py-2 text-left">Producto</th>
                            <th class="px-3 py-2 text-center w-36">Cantidad</th>
                            <th class="px-3 py-2 text-right w-28">Precio</th>
                            <th class="px-3 py-2 text-right w-28">Subtotal</th>
                            <th class="px-3 py-2 w-8"></th>
                        </tr>
                    </thead>
                    <tbody id="items-body" class="divide-y divide-gray-100 dark:divide-gray-800"></tbody>
                </table>
            </div>
            <div id="items-empty" class="px-4 py-12 text-center text-sm text-gray-400 dark:text-gray-500">
                Aún no hay productos. Busca arriba para agregarlos a la proforma.
            </div>
        </div>
    </div>

    {{-- ── Derecha: cliente, fechas y total ─────────────────── --}}
    <div class="lg:col-span-1">
        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 p-4 space-y-4 lg:sticky lg:top-4">

            {{-- Cliente --}}
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300">Cliente</label>
                    <button type="button" id="btn-quick-new-customer" class="text-xs font-semibold text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 dark:hover:text-emerald-300 hover:underline">+ Nuevo cliente</button>
                </div>
                <input type="hidden" name="customer_id" id="input-customer-id" value="{{ old('customer_id', $quotation->customer_id ?? '') }}">

                <div id="customer-chip" class="hidden items-center justify-between gap-2 px-3 h-10 rounded-lg bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 text-sm mb-2">
                    <span id="customer-chip-text" class="truncate text-emerald-800 dark:text-emerald-300"></span>
                    <button type="button" id="customer-clear" class="text-emerald-700 hover:text-red-600 text-lg leading-none" title="Quitar selección">&times;</button>
                </div>

                <div id="customer-search-box" class="relative mb-2">
                    <input type="text" id="customer-search" autocomplete="off"
                        placeholder="Busca un cliente registrado por nombre o documento..."
                        class="w-full h-10 px-3 text-sm rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                    <div id="customer-results"
                        class="hidden absolute left-0 top-full mt-1 z-30 w-full max-h-56 overflow-y-auto rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 shadow-xl"></div>
                </div>

                <div class="space-y-2">
                    <input type="text" name="customer_name" id="input-customer-name" required
                        placeholder="Nombre / Razón social *"
                        value="{{ old('customer_name', $quotation->customer->name ?? '') }}"
                        class="w-full h-10 px-3 text-sm rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white placeholder-gray-400">
                    <div class="grid grid-cols-2 gap-2">
                        <input type="text" name="customer_document" id="input-customer-doc"
                            placeholder="NIT / CI"
                            value="{{ old('customer_document', $quotation->customer->document ?? '') }}"
                            class="w-full h-10 px-3 text-sm rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white placeholder-gray-400">
                        <input type="text" name="customer_phone" id="input-customer-phone"
                            placeholder="Teléfono"
                            value="{{ old('customer_phone', $quotation->customer->phone ?? '') }}"
                            class="w-full h-10 px-3 text-sm rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white placeholder-gray-400">
                    </div>
                    <input type="email" name="customer_email" id="input-customer-email"
                        placeholder="Correo electrónico"
                        value="{{ old('customer_email', $quotation->customer->email ?? '') }}"
                        class="w-full h-10 px-3 text-sm rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white placeholder-gray-400">
                    <input type="text" name="customer_address" id="input-customer-address"
                        placeholder="Dirección"
                        value="{{ old('customer_address', $quotation->customer->address ?? '') }}"
                        class="w-full h-10 px-3 text-sm rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white placeholder-gray-400">
                </div>
            </div>

            {{-- Fechas --}}
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">
                        Emisión <span class="text-red-500">*</span>
                    </label>
                    <input type="date" name="date" required
                        value="{{ old('date', $quotation?->date ? \Carbon\Carbon::parse($quotation->date)->toDateString() : date('Y-m-d')) }}"
                        class="w-full h-10 px-3 text-sm rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">
                        Vence
                    </label>
                    <input type="date" name="expires_at"
                        value="{{ old('expires_at', $quotation?->expires_at ? \Carbon\Carbon::parse($quotation->expires_at)->toDateString() : date('Y-m-d', strtotime('+15 days'))) }}"
                        class="w-full h-10 px-3 text-sm rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white">
                </div>
            </div>

            {{-- Almacén de referencia --}}
            <div>
                <label class="block text-[11px] text-gray-500 dark:text-gray-400 mb-1">Almacén de referencia</label>
                <div class="h-10 px-3 rounded-lg border border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800/60 flex items-center text-xs font-medium text-gray-700 dark:text-gray-300">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 mr-2"></span>
                    {{ $warehouse->name ?? 'Almacén Principal' }}
                </div>
            </div>

            {{-- Notas --}}
            <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Condiciones y Observaciones</label>
                <textarea name="notes" rows="3" maxlength="1000"
                    placeholder="Ej. Precios válidos por 15 días. Entrega inmediata en almacén central."
                    class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">{{ old('notes', $quotation->notes ?? '') }}</textarea>
            </div>

            {{-- Total --}}
            <div class="pt-3 border-t border-gray-100 dark:border-gray-800 space-y-1">
                <div class="flex items-baseline justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Total</span>
                    <span id="grand-total" class="text-3xl font-extrabold text-gray-900 dark:text-white">0.00</span>
                </div>
                <p class="text-[11px] text-gray-400 text-right"><span id="items-count">0</span> producto(s)</p>
            </div>

            <button type="submit" id="submit-btn" disabled
                class="w-full h-12 rounded-lg bg-emerald-600 hover:bg-emerald-700 disabled:bg-gray-300 dark:disabled:bg-slate-700 disabled:cursor-not-allowed text-white font-bold text-sm transition-colors">
                {{ $isEdit ? 'Actualizar Proforma' : 'Guardar Proforma' }}
            </button>
            <a href="{{ route('quotations.index') }}" class="block text-center text-xs font-semibold text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                Cancelar
            </a>
        </div>
    </div>
</form>

<x-ui.image-preview-modal />

{{-- Modal Rápido para Crear Cliente --}}
<x-ui.create-modal id="customer-modal" title="Registrar Nuevo Cliente" :action="route('customers.store')"
    submitText="Guardar Cliente" :columns="2" :fields="[
        [
            'type' => 'text',
            'name' => 'name',
            'label' => 'Nombre / Razón Social',
            'placeholder' => 'Ej. Constructora Andina S.R.L.',
            'required' => true,
            'fullWidth' => true,
        ],
        [
            'type' => 'text',
            'name' => 'document',
            'label' => 'NIT / CI',
            'placeholder' => 'Ej. 102938475',
        ],
        [
            'type' => 'text',
            'name' => 'phone',
            'label' => 'Teléfono / WhatsApp',
            'placeholder' => 'Ej. 76061697',
        ],
        [
            'type' => 'email',
            'name' => 'email',
            'label' => 'Correo Electrónico',
            'placeholder' => 'Ej. contacto@cliente.com',
        ],
        [
            'type' => 'text',
            'name' => 'address',
            'label' => 'Dirección',
            'placeholder' => 'Ej. Av. Banzer km 8',
            'fullWidth' => true,
        ],
    ]" />

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        // ── 1. Manejo del Modal de Cliente Rápido ──
        const customerModal = document.getElementById('customer-modal');
        const customerQuickForm = customerModal?.querySelector('form');
        document.getElementById('btn-quick-new-customer')?.addEventListener('click', () => {
            customerModal?.classList.remove('hidden');
        });
        document.querySelectorAll('[data-modal-close="customer-modal"]').forEach(el => {
            el.addEventListener('click', () => customerModal?.classList.add('hidden'));
        });

        // El modal usa el componente ui.create-modal: por defecto hace un POST normal de página completa,
        // que llevaría a customers.index perdiendo toda la proforma que se estaba armando. Se intercepta
        // el envío y se manda por AJAX (igual que en Ventas), quedándose en esta misma pantalla.
        if (customerQuickForm) {
            const quickSaveBtn = customerQuickForm.querySelector('button[type="submit"]');
            const quickSaveBtnText = quickSaveBtn?.textContent;

            customerQuickForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                if (quickSaveBtn) { quickSaveBtn.disabled = true; quickSaveBtn.textContent = 'Guardando...'; }

                try {
                    const res = await fetch(customerQuickForm.action, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': customerQuickForm.querySelector('input[name=_token]').value,
                        },
                        body: JSON.stringify(Object.fromEntries(new FormData(customerQuickForm))),
                    });
                    const data = await res.json().catch(() => ({}));

                    if (res.status === 422) {
                        alert(Object.values(data.errors || {}).flat().join('\n') || 'Revisa los datos del cliente.');
                        return;
                    }
                    if (!res.ok) throw new Error(data.message || 'No se pudo guardar el cliente.');

                    setCustomer(data);
                    customerModal.classList.add('hidden');
                    customerQuickForm.reset();
                } catch (err) {
                    alert(err.message);
                } finally {
                    if (quickSaveBtn) { quickSaveBtn.disabled = false; quickSaveBtn.textContent = quickSaveBtnText; }
                }
            });
        }

        // ── 2. Buscador de Cliente (igual que en venta) con autocompletado ──
        // La cartera de clientes NO se manda entera al navegador (mismo motivo que el catálogo de
        // productos): se pide al servidor a medida que se escribe (endpoint compartido con Ventas).
        const customersSearchUrl = @json(route('pos.customers.search'));
        async function fetchCustomers(params) {
            const url = new URL(customersSearchUrl, window.location.origin);
            Object.entries(params).forEach(([k, v]) => { if (v !== undefined && v !== null && v !== '') url.searchParams.set(k, v); });
            try {
                const res = await fetch(url, { headers: { Accept: 'application/json' } });
                if (!res.ok) return [];
                const data = await res.json().catch(() => ({ customers: [] }));
                return data.customers || [];
            } catch (e) {
                return [];
            }
        }
        const inputCustId = document.getElementById('input-customer-id');
        const inputCustName = document.getElementById('input-customer-name');
        const inputCustDoc = document.getElementById('input-customer-doc');
        const inputCustPhone = document.getElementById('input-customer-phone');
        const inputCustEmail = document.getElementById('input-customer-email');
        const inputCustAddress = document.getElementById('input-customer-address');
        const customerChip = document.getElementById('customer-chip');
        const customerChipText = document.getElementById('customer-chip-text');
        const customerSearchBox = document.getElementById('customer-search-box');
        const customerSearch = document.getElementById('customer-search');
        const customerResults = document.getElementById('customer-results');

        function setCustomer(c) {
            inputCustId.value = c ? c.id : '';
            customerChip.classList.toggle('hidden', !c);
            customerChip.classList.toggle('flex', !!c);
            customerSearchBox.classList.toggle('hidden', !!c);
            if (c) {
                customerChipText.textContent = c.name + (c.document ? ` (${c.document})` : '');
                inputCustName.value = c.name || '';
                inputCustDoc.value = c.document || '';
                inputCustPhone.value = c.phone || '';
                inputCustEmail.value = c.email || '';
                inputCustAddress.value = c.address || '';
            }
            customerResults.classList.add('hidden');
            customerSearch.value = '';
        }

        function paintCustomerResults(list) {
            customerResults.innerHTML = '';
            list.forEach(c => {
                const b = document.createElement('button');
                b.type = 'button';
                b.className = 'w-full text-left px-3 py-2 text-sm text-gray-800 dark:text-gray-100 hover:bg-emerald-50 dark:hover:bg-slate-700';
                b.textContent = c.name + (c.document ? ' · ' + c.document : '');
                b.addEventListener('click', () => setCustomer(c));
                customerResults.appendChild(b);
            });
            if (!list.length) {
                customerResults.innerHTML = '<div class="px-3 py-2 text-sm text-gray-500 dark:text-gray-400">Sin resultados. Escribe los datos abajo para un cliente nuevo.</div>';
            }
            customerResults.classList.remove('hidden');
        }

        let customerSearchSeq = 0;
        let customerSearchDebounce = null;

        function renderCustomerResults() {
            const q = customerSearch.value;
            const seq = ++customerSearchSeq;
            clearTimeout(customerSearchDebounce);
            customerSearchDebounce = setTimeout(async () => {
                const list = await fetchCustomers({ q });
                if (seq !== customerSearchSeq) return; // llegó una búsqueda más nueva mientras esperábamos esta
                paintCustomerResults(list);
            }, q.trim() ? 200 : 0);
        }

        customerSearch?.addEventListener('input', renderCustomerResults);
        customerSearch?.addEventListener('focus', renderCustomerResults);
        customerSearch?.addEventListener('keydown', (e) => { if (e.key === 'Enter') e.preventDefault(); });
        document.getElementById('customer-clear')?.addEventListener('click', () => setCustomer(null));
        document.addEventListener('click', (e) => {
            if (!customerSearchBox?.contains(e.target)) customerResults?.classList.add('hidden');
        });

        // Si edita el nombre a mano tras haber elegido un cliente, se desvincula el ID (pasa a ser un dato libre)
        inputCustName?.addEventListener('input', () => {
            if (inputCustId.value) setCustomer(null);
        });

        // Estado inicial: si ya hay un cliente vinculado (edición o reenvío tras error de validación),
        // se pide puntualmente por id — no hace falta la cartera entera para redibujar uno solo.
        if (inputCustId.value) {
            // Mientras se confirma si existe, se muestra el nombre ya escrito para no dejar la pantalla en blanco.
            customerChip.classList.remove('hidden');
            customerChip.classList.add('flex');
            customerSearchBox.classList.add('hidden');
            customerChipText.textContent = inputCustName.value || 'Cliente seleccionado';

            fetchCustomers({ ids: inputCustId.value }).then(([existing]) => {
                if (existing) setCustomer(existing);
            });
        }

        // ── 3. Buscador y Gestión Dinámica de Productos ──
        // El catálogo NO se manda entero al navegador (con miles de productos eso cuelga la pantalla):
        // se pide al servidor a medida que se escribe, igual que en Ventas (ver PosSearchController).
        const warehouseId = @json($warehouse->id ?? null);
        const searchUrl = @json(route('pos.products.search'));
        const canEditPrice = @json($canEditPrice);
        const searchInput = document.getElementById('product-search-input');
        const searchList = document.getElementById('product-search-list');
        const tbody = document.getElementById('items-body');
        const emptyState = document.getElementById('items-empty');
        const grandTotalEl = document.getElementById('grand-total');
        const itemsCountEl = document.getElementById('items-count');
        const submitBtn = document.getElementById('submit-btn');

        let rowIndex = 0;
        const addedRows = {};

        const cellInput = 'h-9 px-2 text-xs rounded-md border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white focus:ring-1 focus:ring-emerald-500';
        const stepBtnCls = 'h-9 w-9 flex items-center justify-center border border-gray-300 dark:border-slate-600 bg-gray-50 dark:bg-slate-700 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-slate-600 font-bold text-sm';

        // Marca / Color / Medida, si el producto los tiene, para mostrarlos junto al SKU.
        function variantLabel(u) {
            return [u.marca, u.color, u.medida].filter(Boolean).join(' · ');
        }

        // La respuesta del buscador trae el stock/costo por almacén (stock_by_warehouse/cost_by_warehouse,
        // igual que en Ventas): acá se reduce al almacén de referencia de esta proforma, una sola vez,
        // para que el resto del código (addRow, selectProductUnit) siga usando los mismos nombres de
        // siempre (product_name, unit_name, stock_in_unit, cost).
        function normalizeUnit(u) {
            const stockBase = Number((u.stock_by_warehouse || {})[warehouseId] ?? 0);
            const factor = Number(u.factor) || 1;
            u.product_name = u.name;
            u.unit_name = u.unit;
            u.stock_in_unit = Math.floor(stockBase / factor);
            u.cost = canEditPrice ? Number((u.cost_by_warehouse || {})[warehouseId] ?? 0) : null;
            return u;
        }

        async function fetchUnits(params) {
            const url = new URL(searchUrl, window.location.origin);
            Object.entries(params).forEach(([k, v]) => { if (v !== undefined && v !== null && v !== '') url.searchParams.set(k, v); });
            try {
                const res = await fetch(url, { headers: { Accept: 'application/json' } });
                if (!res.ok) return [];
                const data = await res.json().catch(() => ({ units: [] }));
                return (data.units || []).map(normalizeUnit);
            } catch (e) {
                return [];
            }
        }

        let searchSeq = 0;
        let searchDebounce = null;

        function filterProducts(query) {
            const q = query || '';
            const seq = ++searchSeq;
            clearTimeout(searchDebounce);
            searchDebounce = setTimeout(async () => {
                const filtered = await fetchUnits({ q });
                if (seq !== searchSeq) return; // llegó una búsqueda más nueva mientras esperábamos esta
                paintResults(filtered);
            }, q.trim() ? 200 : 0);
        }

        let lastResults = [];

        function paintResults(filtered) {
            lastResults = filtered;
            if (filtered.length === 0) {
                searchList.innerHTML = '<div class="p-3 text-xs text-gray-500 dark:text-gray-400">No se encontraron productos coincidentes.</div>';
                searchList.classList.remove('hidden');
                return;
            }

            // Fila armada por DOM + textContent, no por HTML interpolado: un nombre con comillas, & o
            // algo con pinta de etiqueta (frecuente en ferretería: 1/2", "3", Fischer & Cia) no puede
            // romper la lista ni comerse el resto de las filas.
            searchList.innerHTML = '';
            filtered.forEach(u => {
                const row = document.createElement('div');
                row.className = 'product-option p-2.5 hover:bg-emerald-50 dark:hover:bg-slate-700/60 cursor-pointer border-b border-gray-100 dark:border-slate-700/40 flex flex-wrap items-center justify-between gap-1 text-xs';
                row.dataset.id = u.id;

                const photo = document.createElement('div');
                photo.className = 'shrink-0';
                if (u.photo_url) {
                    const wrap = document.createElement('div');
                    wrap.className = 'relative group/searchimg cursor-zoom-in';
                    wrap.title = 'Clic para ampliar imagen';
                    const img = document.createElement('img');
                    img.src = u.photo_url;
                    img.alt = '';
                    img.className = 'w-9 h-9 object-cover rounded-lg border border-gray-200 dark:border-gray-700 group-hover/searchimg:ring-2 group-hover/searchimg:ring-emerald-500 transition';
                    wrap.appendChild(img);
                    // stopPropagation: la fila entera agrega el producto al hacer clic; la imagen debe
                    // solo mostrar la vista previa, sin agregarlo por accidente.
                    wrap.addEventListener('click', (e) => { e.stopPropagation(); openImagePreview(u.photo_url, u.product_name || 'Producto'); });
                    photo.appendChild(wrap);
                } else {
                    photo.innerHTML = '<div class="w-9 h-9 rounded-lg border border-dashed border-gray-300 dark:border-slate-700 bg-gray-50 dark:bg-slate-800"></div>';
                }

                const info = document.createElement('div');
                info.className = 'min-w-0';
                const nameLine = document.createElement('div');
                const nameSpan = document.createElement('span');
                nameSpan.className = 'font-semibold text-gray-900 dark:text-white';
                nameSpan.textContent = u.product_name || 'Producto';
                const unitSpan = document.createElement('span');
                unitSpan.className = 'text-gray-500 dark:text-gray-400';
                unitSpan.textContent = ` (${u.unit_name || ''})`;
                nameLine.append(nameSpan, unitSpan);
                if (u.sku) {
                    const skuSpan = document.createElement('span');
                    skuSpan.className = 'text-[10px] text-gray-400 ml-1 font-mono';
                    skuSpan.textContent = `[${u.sku}]`;
                    nameLine.appendChild(skuSpan);
                }
                info.appendChild(nameLine);
                if (variantLabel(u)) {
                    const variantSpan = document.createElement('span');
                    variantSpan.className = 'block text-[10px] text-gray-400';
                    variantSpan.textContent = variantLabel(u);
                    info.appendChild(variantSpan);
                }

                const left = document.createElement('div');
                left.className = 'flex items-center gap-2 min-w-0';
                left.append(photo, info);

                const right = document.createElement('div');
                right.className = 'text-right';
                const priceSpan = document.createElement('span');
                priceSpan.className = 'font-semibold text-emerald-600 dark:text-emerald-400 font-mono mr-2';
                priceSpan.textContent = `Bs. ${(Number(u.price) || 0).toFixed(2)}`;
                const stockSpan = document.createElement('span');
                stockSpan.className = 'px-1.5 py-0.5 rounded text-[10px] ' + (u.stock_in_unit > 0 ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300');
                stockSpan.textContent = `Stock: ${u.stock_in_unit}`;
                right.append(priceSpan, stockSpan);

                row.append(left, right);
                searchList.appendChild(row);
            });
            searchList.classList.remove('hidden');
        }

        searchInput?.addEventListener('input', (e) => filterProducts(e.target.value));
        searchInput?.addEventListener('focus', () => filterProducts(searchInput.value));
        searchInput?.addEventListener('click', () => filterProducts(searchInput.value));

        document.addEventListener('click', (e) => {
            if (!document.getElementById('product-search-wrapper')?.contains(e.target)) {
                searchList?.classList.add('hidden');
            }
        });

        searchList?.addEventListener('click', (e) => {
            const opt = e.target.closest('.product-option');
            if (!opt) return;
            // Comparar como texto siempre: según el servidor (según cómo el motor de base de datos
            // devuelva los IDs), pueden llegar como número o como texto ("1" vs 1) — con === estricto,
            // un lado con id de texto y el otro con id numérico nunca hacen match y el clic no hace nada.
            const unitId = String(opt.dataset.id);
            const unit = lastResults.find(u => String(u.id) === unitId);
            if (!unit) return;
            try {
                selectProductUnit(unit);
            } catch (err) {
                console.error('No se pudo agregar el producto:', err);
                alert('No se pudo agregar "' + (unit.product_name || unit.name || 'el producto') + '": ' + err.message);
            }
        });

        function selectProductUnit(unit) {
            searchInput.value = '';
            searchList.classList.add('hidden');

            // La proforma es solo una cotización: el stock que se muestra es referencial, no bloquea nada
            // acá (no reserva ni descuenta inventario real). Se puede cotizar cualquier producto/cantidad.
            if (addedRows[unit.id]) {
                addedRows[unit.id].querySelector('.quantity-input').focus();
                return;
            }

            addRow(unit);
        }

        function addRow(unit, initialQty = 1, initialPrice = null) {
            const index = rowIndex++;
            const tr = document.createElement('tr');
            tr.className = 'item-row hover:bg-gray-50/50 dark:hover:bg-slate-800/40 transition border-b border-gray-100 dark:border-slate-800/60';

            // Tope de cantidad SOLO para evitar un número absurdo escrito por error, no por stock: una
            // proforma es una cotización, no reserva ni descuenta inventario real.
            const maxQty = 99999;
            const qtyVal = Math.min(Math.max(1, parseInt(initialQty, 10) || 1), maxQty);
            const catalogPrice = parseFloat(unit.price) || 0;
            const cost = parseFloat(unit.cost) || 0; // solo llega si el usuario puede editar precios
            // Con permiso: el precio editable (el guardado en la proforma o el que se escribió); sin permiso: el de catálogo
            const startPrice = canEditPrice && initialPrice !== null && initialPrice !== '' && !isNaN(parseFloat(initialPrice))
                ? parseFloat(initialPrice)
                : catalogPrice;

            // Estructura fija por HTML (sin interpolar nombre/medida/marca ahí adentro): eso se completa
            // después con textContent, así un producto con comillas, & o " en el nombre o la medida
            // (frecuente en ferretería: 1/2", 3/4") no puede romper el HTML de la fila.
            tr.innerHTML = `
                <td class="px-3 py-2 text-center w-20 align-middle">
                    <div class="row-photo flex items-center justify-center"></div>
                </td>
                <td class="px-3 py-2 align-middle">
                    <div class="min-w-0">
                        <span class="row-name block text-gray-900 dark:text-white font-medium text-sm leading-snug"></span>
                        <span class="row-detail block text-xs text-gray-500 dark:text-gray-400 mt-0.5"></span>
                    </div>
                    <input type="hidden" name="items[${index}][product_unit_id]" value="${unit.id}">
                </td>
                <td class="px-3 py-2 align-middle">
                    <div class="flex items-center justify-center">
                        <button type="button" class="${stepBtnCls} rounded-l-lg minus">−</button>
                        <input type="number" step="1" min="1" max="${maxQty}" value="${qtyVal}" name="items[${index}][quantity]" required
                            inputmode="numeric" onkeydown="if (['.', ',', 'e', 'E', '-', '+'].includes(event.key)) event.preventDefault()"
                            class="quantity-input h-9 w-14 text-center text-sm border-y border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white focus:ring-1 focus:ring-emerald-500 focus:outline-none">
                        <button type="button" class="${stepBtnCls} rounded-r-lg plus">+</button>
                    </div>
                </td>
                <td class="px-3 py-2 text-right price-cell align-middle">
                    ${canEditPrice
                        ? `<input type="number" step="0.01" min="0" name="items[${index}][unit_price]" value="${startPrice.toFixed(2)}" required
                                class="price-input ${cellInput} w-24 text-right font-mono ml-auto">`
                        : `Bs. ${catalogPrice.toFixed(2)}<input type="hidden" class="price-input" name="items[${index}][unit_price]" value="${catalogPrice.toFixed(2)}">`}
                </td>
                <td class="px-3 py-2 text-right font-semibold text-gray-900 dark:text-white align-middle">
                    <span class="row-subtotal">${(qtyVal * startPrice).toFixed(2)}</span>
                </td>
                <td class="px-3 py-2 text-right align-middle">
                    <button type="button" class="remove-row text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 text-xl leading-none p-1" title="Quitar">&times;</button>
                </td>
            `;

            // Foto y textos: por textContent/atributos del DOM, nunca concatenados dentro de HTML.
            const photoWrap = tr.querySelector('.row-photo');
            if (unit.photo_url) {
                const wrap = document.createElement('div');
                wrap.className = 'relative group/lineimg cursor-zoom-in shrink-0';
                wrap.title = 'Clic para ampliar imagen';
                const img = document.createElement('img');
                img.src = unit.photo_url;
                img.alt = '';
                img.className = 'w-14 h-14 object-cover rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-slate-800 shadow-sm group-hover/lineimg:ring-2 group-hover/lineimg:ring-emerald-500 transition';
                wrap.appendChild(img);
                wrap.addEventListener('click', () => openImagePreview(unit.photo_url, unit.product_name || 'Producto'));
                photoWrap.appendChild(wrap);
            } else {
                photoWrap.innerHTML = '<div class="w-14 h-14 rounded-xl border border-dashed border-gray-300 dark:border-slate-700 bg-gray-50 dark:bg-slate-800 shrink-0"></div>';
            }
            tr.querySelector('.row-name').textContent = unit.product_name || 'Producto';
            const detailParts = [unit.unit_name, variantLabel(unit), `Stock disponible: ${unit.stock_in_unit}`].filter(Boolean);
            tr.querySelector('.row-detail').textContent = detailParts.join(' · ');

            tbody.appendChild(tr);
            addedRows[unit.id] = tr;

            const qtyInput = tr.querySelector('.quantity-input');
            const subtotalEl = tr.querySelector('.row-subtotal');

            function validateAndClampQty() {
                let val = parseInt(qtyInput.value, 10);
                if (isNaN(val) || val < 1) val = 1;
                if (val > maxQty) {
                    val = maxQty;
                    qtyInput.value = maxQty;
                }
                return val;
            }

            const priceInput = tr.querySelector('.price-input');
            const currentPrice = () => Math.max(0, parseFloat(priceInput.value) || 0);

            function updateSubtotal() {
                const qty = validateAndClampQty();
                const sub = (qty * currentPrice());
                subtotalEl.textContent = sub.toFixed(2);
                calculateGrandTotal();
            }

            if (canEditPrice) {
                priceInput.addEventListener('input', updateSubtotal);
                // Al salir del campo, un precio por debajo del costo sube al costo (no se puede bajar de ahí)
                priceInput.addEventListener('change', () => {
                    const v = parseFloat(priceInput.value);
                    if (isNaN(v) || v < 0) {
                        priceInput.value = catalogPrice.toFixed(2);
                    } else if (cost > 0 && v < cost - 0.004 && Math.abs(v - catalogPrice) > 0.004) {
                        priceInput.value = cost.toFixed(2);
                    }
                    updateSubtotal();
                });
            }

            qtyInput.addEventListener('input', updateSubtotal);
            qtyInput.addEventListener('change', () => {
                let val = parseInt(qtyInput.value, 10);
                if (isNaN(val) || val < 1) qtyInput.value = 1;
                else if (val > maxQty) qtyInput.value = maxQty;
                updateSubtotal();
            });
            tr.querySelector('.minus').addEventListener('click', () => {
                qtyInput.value = Math.max(1, (parseInt(qtyInput.value, 10) || 1) - 1);
                updateSubtotal();
            });
            tr.querySelector('.plus').addEventListener('click', () => {
                qtyInput.value = Math.min(maxQty, (parseInt(qtyInput.value, 10) || 0) + 1);
                updateSubtotal();
            });

            tr.querySelector('.remove-row').addEventListener('click', () => {
                delete addedRows[unit.id];
                tr.remove();
                calculateGrandTotal();
            });

            calculateGrandTotal();
        }

        function calculateGrandTotal() {
            let total = 0;
            const rows = tbody.querySelectorAll('.item-row');
            rows.forEach(r => {
                const sub = parseFloat(r.querySelector('.row-subtotal')?.textContent) || 0;
                total += sub;
            });
            grandTotalEl.textContent = total.toFixed(2);
            itemsCountEl.textContent = rows.length;
            emptyState.classList.toggle('hidden', rows.length > 0);
            submitBtn.disabled = rows.length === 0;
        }

        // Cargar ítems iniciales (si existen por edición o validación previa): se piden puntualmente por
        // id, no hace falta (ni tiene sentido) pedir el catálogo entero para redibujar unas pocas líneas.
        const initialItems = @json(old('items', $initialItems ?? []));
        if (Array.isArray(initialItems) && initialItems.length > 0) {
            (async () => {
                const ids = initialItems.map(it => it.product_unit_id).join(',');
                const units = await fetchUnits({ ids });
                const byId = new Map(units.map(u => [String(u.id), u]));
                initialItems.forEach(it => {
                    const u = byId.get(String(it.product_unit_id));
                    if (u) {
                        addRow(u, it.quantity, it.unit_price ?? null);
                    }
                });
            })();
        }
    });
</script>
@endpush
