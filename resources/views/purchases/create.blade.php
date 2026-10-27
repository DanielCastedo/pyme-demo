<x-app-layout>
    @php
        $editing = isset($purchase); // true al agregar productos a una compra ya registrada
        $field = 'w-full h-10 px-3 text-sm rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500';
        $label = 'block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5';
        $oldItems = collect(old('items', []))->map(fn ($i) => [
            'id' => (int) ($i['product_unit_id'] ?? 0),
            'quantity' => $i['quantity'] ?? 1,
            'cost' => $i['unit_cost'] ?? 0,
            'distribution' => collect($i['distribution'] ?? [])->map(fn ($d) => ['warehouse_id' => (int) ($d['warehouse_id'] ?? 0), 'quantity' => $d['quantity'] ?? 1])->values(),
        ])->values();
    @endphp

    <div class="max-w-10xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

        @include('sales.partials.alerts')

        @if ($editing)
            <x-ui.page-header category="Inventario" title="Agregar productos a la Compra #{{ $purchase->id }}"
                description="Lo que ya se compró no se modifica. Los productos que agregues entran al inventario (en el almacén que elijas) y, si se paga, salen de la caja." />
        @else
            <x-ui.page-header category="Inventario" title="Nueva Compra"
                description="Registra la mercadería que compraste. Entra al almacén como un lote nuevo con su costo (FIFO) y, si quieres, se descuenta de una caja." />
        @endif

        <form id="purchase-form" action="{{ $editing ? route('purchases.update', $purchase) : route('purchases.store') }}" method="POST" class="grid grid-cols-1 lg:grid-cols-3 gap-5">
            @csrf
            @if ($editing) @method('PUT') @endif

            {{-- ── Izquierda: productos ── --}}
            <div class="lg:col-span-2 space-y-4">
                @if ($editing)
                    {{-- En pantallas chicas cada línea ya comprada se muestra como tarjeta en vez de fila de tabla --}}
                    <style>
                        @media (max-width: 767px) {
                            #purchased-table, #purchased-table tbody { display: block; width: 100%; }
                            #purchased-table thead { display: none; }
                            #purchased-table tbody > tr { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 6px 10px; padding: 12px; }
                            #purchased-table tbody > tr > td { display: block; padding: 0 !important; max-width: none !important; white-space: normal !important; text-align: left !important; }
                            #purchased-table tbody > tr > td:nth-child(1) { grid-column: 1 / 4; grid-row: 1; font-weight: 600; color: #111827; }
                            .dark #purchased-table tbody > tr > td:nth-child(1) { color: #fff; }
                            #purchased-table tbody > tr > td:nth-child(2) { grid-column: 1 / 4; grid-row: 2; }
                            #purchased-table tbody > tr > td:nth-child(3) { grid-column: 1; grid-row: 3; }
                            #purchased-table tbody > tr > td:nth-child(4) { grid-column: 2; grid-row: 3; }
                            #purchased-table tbody > tr > td:nth-child(5) { grid-column: 3; grid-row: 3; text-align: right !important; }
                            #purchased-table tbody > tr > td:nth-child(n+3)::before { display: block; margin-bottom: 2px; font-size: 10px; font-weight: 600; letter-spacing: .05em; text-transform: uppercase; color: #9ca3af; }
                            #purchased-table tbody > tr > td:nth-child(3)::before { content: 'Cantidad'; }
                            #purchased-table tbody > tr > td:nth-child(4)::before { content: 'Costo'; }
                            #purchased-table tbody > tr > td:nth-child(5)::before { content: 'Subtotal'; text-align: right; }
                            #purchased-table tfoot tr { display: flex; justify-content: space-between; align-items: baseline; padding: 10px 12px; }
                            #purchased-table tfoot td { display: block; padding: 0 !important; text-align: left !important; }
                            #purchased-table tfoot td:last-child { text-align: right !important; }
                        }
                    </style>

                    {{-- Productos ya comprados: solo lectura --}}
                    <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 overflow-hidden">
                        <div class="px-4 py-2.5 flex items-center justify-between bg-gray-50 dark:bg-slate-800 border-b border-gray-200 dark:border-gray-700">
                            <span class="text-xs font-bold uppercase tracking-wider text-gray-600 dark:text-gray-300">Ya comprado ({{ $purchase->items->count() }})</span>
                            <span class="inline-flex items-center gap-1 text-[11px] text-gray-500 dark:text-gray-400">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4" /></svg>
                                Bloqueado: no se puede modificar
                            </span>
                        </div>
                        <div class="overflow-x-auto">
                            <table id="purchased-table" class="w-full text-sm">
                                <thead class="text-[11px] uppercase text-gray-400 dark:text-gray-500">
                                    <tr>
                                        <th class="px-2 sm:px-3 py-2 text-left">Producto</th>
                                        <th class="px-2 sm:px-3 py-2 text-left">Destino</th>
                                        <th class="px-1 sm:px-3 py-2 text-right w-16 sm:w-24">Cant.</th>
                                        <th class="px-1 sm:px-3 py-2 text-right w-16 sm:w-28">Costo</th>
                                        <th class="px-1 sm:px-3 py-2 text-right w-16 sm:w-28">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-gray-600 dark:text-gray-400">
                                    @foreach ($purchase->items as $item)
                                        <tr>
                                            <td class="px-2 sm:px-3 py-2" title="{{ $item->product->name }} — {{ $item->productUnit->name }}">{{ $item->product->name }} — {{ $item->productUnit->name }}</td>
                                            <td class="px-2 sm:px-3 py-2 text-xs">
                                                @forelse ($item->distributions as $d)
                                                    <div>{{ $d->warehouse->name }}: {{ (float) $d->quantity }}</div>
                                                @empty
                                                    {{ $purchase->warehouse->name }}
                                                @endforelse
                                            </td>
                                            <td class="px-1 sm:px-3 py-2 text-right">{{ (float) $item->quantity }}</td>
                                            <td class="px-1 sm:px-3 py-2 text-right">{{ number_format((float) $item->unit_cost, 2) }}</td>
                                            <td class="px-1 sm:px-3 py-2 text-right font-semibold">{{ number_format((float) $item->subtotal, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="border-t border-gray-200 dark:border-gray-700">
                                    <tr>
                                        <td colspan="4" class="px-2 sm:px-3 py-2 text-right text-xs font-bold uppercase text-gray-500">Total actual de la compra</td>
                                        <td class="px-1 sm:px-3 py-2 text-right font-bold text-gray-900 dark:text-white">{{ $purchase->amount_label }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                @endif

                <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 p-4">
                    <div class="flex items-center gap-2">
                        <div class="relative flex-1">
                            <input type="text" id="product-search" autofocus autocomplete="off"
                                placeholder="Busca por nombre o código, o escanea el código de barras y presiona Enter..."
                                class="{{ $field }} h-12">
                            <div id="product-results" class="hidden absolute left-0 top-full mt-1 z-30 w-full max-h-80 overflow-y-auto rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 shadow-xl"></div>
                        </div>
                        @if ($canCreateProduct)
                            <button type="button" id="new-product-btn"
                                class="shrink-0 h-12 px-3 rounded-lg border border-emerald-300 dark:border-emerald-700 text-emerald-600 dark:text-emerald-400 text-xs font-semibold hover:bg-emerald-50 dark:hover:bg-emerald-900/20 whitespace-nowrap">
                                + Nuevo producto
                            </button>
                        @endif
                    </div>
                    @if ($canCreateProduct)
                        <p class="mt-1.5 text-[11px] text-gray-500 dark:text-gray-400">¿El producto todavía no existe en el catálogo? Créalo con "+ Nuevo producto" y sigue comprando.</p>
                    @endif
                </div>

                {{-- En pantallas chicas (< 768 px) cada producto se muestra como tarjeta en vez de fila de tabla --}}
                <style>
                    @media (max-width: 767px) {
                        #purchase-lines-table, #purchase-lines-table tbody { display: block; width: 100%; }
                        #purchase-lines-table thead { display: none; }
                        #lines-body > tr:not(.dist-row) {
                            display: grid;
                            grid-template-columns: 1fr 1fr 1fr 28px;
                            gap: 10px;
                            align-items: start;
                            padding: 14px 12px;
                        }
                        #lines-body > tr:not(.dist-row) > td { display: block; padding: 0 !important; height: auto !important; width: auto !important; max-width: none !important; text-align: left !important; min-width: 0; }
                        /* Producto (fila completa) | Quitar */
                        #lines-body > tr:not(.dist-row) > td:nth-child(1) { grid-column: 1 / 4; grid-row: 1; }
                        #lines-body > tr:not(.dist-row) > td:nth-child(5) { grid-column: 4; grid-row: 1; text-align: right !important; }
                        /* Cantidad | Costo | Subtotal */
                        #lines-body > tr:not(.dist-row) > td:nth-child(2) { grid-column: 1; grid-row: 2; }
                        #lines-body > tr:not(.dist-row) > td:nth-child(3) { grid-column: 2; grid-row: 2; }
                        #lines-body > tr:not(.dist-row) > td:nth-child(4) { grid-column: 3; grid-row: 2; text-align: right !important; font-size: 1.05rem; padding-top: 8px !important; }
                        /* Nombre del campo sobre cada control */
                        #lines-body > tr:not(.dist-row) > td:nth-child(n+2):nth-child(-n+4)::before {
                            display: block; margin-bottom: 4px; font-size: 10px; font-weight: 600; letter-spacing: .05em; text-transform: uppercase; color: #6b7280;
                        }
                        #lines-body > tr:not(.dist-row) > td:nth-child(2)::before { content: 'Cantidad'; }
                        #lines-body > tr:not(.dist-row) > td:nth-child(3)::before { content: 'Costo'; }
                        #lines-body > tr:not(.dist-row) > td:nth-child(4)::before { content: 'Subtotal'; text-align: right; }
                        .dark #lines-body > tr:not(.dist-row) > td::before { color: #9ca3af; }
                        /* La cantidad y el costo ocupan todo el ancho de su columna. min-width: 0 es necesario
                           porque un <input type="number"> tiene un ancho mínimo propio bastante grande que si
                           no, gana por sobre flex y empuja los botones −/+ fuera de la tarjeta. */
                        #lines-body > tr:not(.dist-row) .dec, #lines-body > tr:not(.dist-row) .qty, #lines-body > tr:not(.dist-row) .inc {
                            width: auto !important; min-width: 0 !important; flex: 1 1 0% !important;
                        }
                        #lines-body > tr:not(.dist-row) .cost { width: 100% !important; min-width: 0 !important; }
                        /* El reparto entre almacenes es una fila aparte: se deja como bloque simple, sin grilla */
                        #lines-body tr.dist-row, #lines-body tr.dist-row > td { display: block; width: 100% !important; }
                    }
                </style>

                <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table id="purchase-lines-table" class="w-full text-sm">
                            <thead class="bg-gray-50 dark:bg-slate-800 text-[11px] uppercase text-gray-500 dark:text-gray-400">
                                <tr>
                                    <th class="px-2 sm:px-3 py-2 text-left">Producto</th>
                                    <th class="px-1 sm:px-3 py-2 text-center w-24 sm:w-40">Cant.</th>
                                    <th class="px-1 sm:px-3 py-2 text-right w-20 sm:w-32">Costo</th>
                                    <th class="px-1 sm:px-3 py-2 text-right w-16 sm:w-28">Subtotal</th>
                                    <th class="px-1 sm:px-3 py-2 w-6 sm:w-10"></th>
                                </tr>
                            </thead>
                            <tbody id="lines-body" class="divide-y divide-gray-100 dark:divide-gray-800"></tbody>
                        </table>
                    </div>
                    <p id="empty-lines" class="p-8 text-center text-sm text-gray-400">{{ $editing ? 'Busca los productos que quieres agregar a esta compra.' : 'Busca un producto para agregarlo a la compra.' }}</p>
                </div>
            </div>

            {{-- ── Derecha: datos y total ── --}}
            <div class="space-y-4">
                <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 p-4 space-y-3 lg:sticky lg:top-4">
                    @if ($editing)
                        <div class="rounded-lg bg-gray-50 dark:bg-slate-800 border border-gray-200 dark:border-slate-700 p-3 text-xs space-y-1">
                            <div class="flex justify-between gap-2"><span class="text-gray-500">Proveedor</span><span class="font-semibold text-gray-900 dark:text-white text-right">{{ $purchase->supplier_label }}</span></div>
                            <div class="flex justify-between gap-2"><span class="text-gray-500">Factura</span><span class="font-semibold text-gray-900 dark:text-white">{{ $purchase->invoice_number ?: '—' }}</span></div>
                            <div class="flex justify-between gap-2"><span class="text-gray-500">Fecha</span><span class="font-semibold text-gray-900 dark:text-white">{{ $purchase->date->format('d/m/Y') }}</span></div>
                        </div>
                    @else
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300">Proveedor</label>
                            @if ($canCreateSupplier)
                                <button type="button" id="new-supplier-btn" class="text-xs font-semibold text-emerald-600 hover:text-emerald-700 hover:underline">+ Nuevo proveedor</button>
                            @endif
                        </div>
                        <select name="supplier_id" id="supplier-id" class="{{ $field }}">
                            <option value="">Sin proveedor</option>
                            @foreach ($suppliers as $sup)
                                <option value="{{ $sup->id }}" @selected(old('supplier_id', $selectedSupplierId) == $sup->id)>{{ $sup->name }}{{ $sup->document ? ' · '.$sup->document : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="{{ $label }}">N° de factura del proveedor</label>
                        <input type="text" name="invoice_number" value="{{ old('invoice_number') }}" placeholder="Ej. FAC-0001" class="{{ $field }}">
                    </div>
                    @endif
                    @unless ($editing)
                    {{-- Sucursal que compra: solo se listan los almacenes que usa (propios o compartidos) y sus cajas --}}
                    <div>
                        <label class="{{ $label }}">Sucursal *</label>
                        <select name="branche_id" id="branch-id" class="{{ $field }}">
                            @foreach ($branches as $b)
                                <option value="{{ $b->id }}" @selected(old('branche_id', $defaultBranchId) == $b->id)>{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endunless
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        {{-- Se oculta cuando se distribuye en almacenes: los destinos salen del reparto de cada producto --}}
                        <div id="warehouse-field" @class(['col-span-2' => $editing])>
                            <label class="{{ $label }}">Almacén *</label>
                            <select name="warehouse_id" id="warehouse-id" class="{{ $field }}">
                                @foreach ($warehouses as $w)
                                    <option value="{{ $w->id }}" data-branch="{{ $w->branche_id }}" data-branches="{{ implode(',', $w->branch_ids) }}" @selected(old('warehouse_id', $defaultWarehouseId) == $w->id)>{{ $w->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @unless ($editing)
                        <div id="date-field">
                            <label class="{{ $label }}">Fecha</label>
                            <input type="date" name="date" value="{{ old('date', now()->toDateString()) }}" max="{{ now()->toDateString() }}" class="{{ $field }}">
                        </div>
                        @endunless
                    </div>
                    @unless ($editing)
                    <div>
                        <label class="{{ $label }}">Nota</label>
                        <input type="text" name="note" value="{{ old('note') }}" maxlength="1000" placeholder="Opcional" class="{{ $field }}">
                    </div>
                    @endunless

                    {{-- Forma de pago: contado (pago opcional ahora) o crédito (no aplica al agregar productos) --}}
                    <div class="rounded-lg border border-gray-200 dark:border-slate-700 p-3 space-y-3">
                        @unless ($editing)
                            @if ($canCredit)
                                <div class="grid grid-cols-2 gap-1 p-1 rounded-lg bg-gray-100 dark:bg-slate-800">
                                    <button type="button" data-type="cash" id="pay-type-cash" class="pay-type-btn h-9 text-xs font-bold rounded-md">Contado</button>
                                    <button type="button" data-type="credit" id="pay-type-credit" class="pay-type-btn h-9 text-xs font-bold rounded-md">Crédito</button>
                                </div>
                                <input type="hidden" name="payment_type" id="payment-type" value="{{ old('payment_type', 'cash') }}">
                            @endif

                            <div id="credit-only-fields" class="hidden space-y-3">
                                <div>
                                    <label class="{{ $label }}">Vence el <span class="text-red-500">*</span></label>
                                    <input type="date" name="due_date" id="due-date" value="{{ old('due_date', now()->addDays(30)->toDateString()) }}" class="{{ $field }}">
                                </div>
                                <p class="text-[11px] text-amber-600 dark:text-amber-400">Necesita un proveedor seleccionado arriba: si no está en la lista, créalo con "+ Nuevo proveedor".</p>
                            </div>
                        @endunless

                        <label class="flex items-center gap-2 text-sm font-semibold text-gray-800 dark:text-gray-200">
                            <input type="checkbox" name="pay_now" id="pay-now" value="1" @checked(old('pay_now', $defaultPayNow ?? false)) class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                            <span id="pay-now-text">{{ $editing ? 'Pagar lo agregado desde una caja' : 'Pagar ahora desde una caja' }}</span>
                        </label>
                        <div id="pay-fields" class="hidden space-y-3">
                            <div id="initial-payment-field" class="hidden">
                                <label class="{{ $label }}">Monto del anticipo</label>
                                <input type="number" step="0.01" min="0" name="initial_payment" id="initial-payment" value="{{ old('initial_payment') }}" placeholder="0.00" class="{{ $field }}">
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="{{ $label }}">Método</label>
                                    <select name="payment_id" class="{{ $field }}">
                                        @foreach ($paymentMethods as $p)
                                            <option value="{{ $p->id }}" @selected(old('payment_id', $defaultPaymentId ?? null) == $p->id)>{{ $p->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="{{ $label }}">Caja</label>
                                    <select name="cashbox_id" id="cashbox-id" class="{{ $field }}">
                                        @foreach ($cashboxes as $c)
                                            <option value="{{ $c->id }}" data-branch="{{ $c->branche_id }}" @selected(old('cashbox_id', $defaultCashboxId ?? null) == $c->id)>{{ $c->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-baseline justify-between pt-1">
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ $editing ? 'Total de lo agregado' : 'Total' }}</span>
                        <span id="total" class="text-3xl font-extrabold text-gray-900 dark:text-white">0.00</span>
                    </div>
                    <p id="warning" class="hidden text-xs text-red-600"></p>

                    <button type="submit" id="submit-btn" disabled
                        class="w-full h-12 rounded-lg bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold text-sm transition">
                        {{ $editing ? 'Agregar a la compra' : 'Registrar compra' }}
                    </button>
                    <a href="{{ $editing ? route('purchases.show', $purchase) : route('purchases.index') }}" class="block text-center text-xs text-gray-500 hover:underline">Cancelar</a>
                </div>
            </div>
        </form>
    </div>

    {{-- ── Modal: nuevo proveedor ── --}}
    @if ($canCreateSupplier)
        <div id="supplier-modal" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm p-4 flex items-center justify-center">
            <div class="w-full max-w-md bg-white dark:bg-slate-900 rounded-2xl border border-gray-200 dark:border-slate-700 shadow-2xl overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 dark:border-slate-800 flex items-center justify-between">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white">Nuevo proveedor</h3>
                    <button type="button" id="supplier-modal-close" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
                </div>
                <form id="supplier-form" class="p-5 space-y-3">
                    <div id="supplier-form-errors" class="hidden p-2.5 rounded-lg bg-red-50 dark:bg-red-900/20 text-xs text-red-700 dark:text-red-300"></div>
                    <div>
                        <label class="{{ $label }}">Nombre / Razón social <span class="text-red-500">*</span></label>
                        <input type="text" name="name" required maxlength="255" placeholder="Ej. Distribuidora Importadora S.R.L." class="{{ $field }}">
                    </div>
                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="{{ $label }}">Documento</label>
                            <select name="document_type" class="{{ $field }}">
                                @foreach (\App\Models\Supplier::DOCUMENT_TYPES as $value => $text)
                                    <option value="{{ $value }}">{{ $text }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-span-2">
                            <label class="{{ $label }}">N° de documento</label>
                            <input type="text" name="document" maxlength="50" placeholder="Opcional" class="{{ $field }}">
                        </div>
                    </div>
                    <div>
                        <label class="{{ $label }}">Persona de contacto</label>
                        <input type="text" name="contact_name" maxlength="255" placeholder="Opcional" class="{{ $field }}">
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="{{ $label }}">Teléfono</label>
                            <input type="text" name="phone" maxlength="30" placeholder="Ej. 7XXXXXXX" class="{{ $field }}">
                        </div>
                        <div>
                            <label class="{{ $label }}">Correo</label>
                            <input type="email" name="email" maxlength="255" placeholder="Opcional" class="{{ $field }}">
                        </div>
                    </div>
                    <div class="pt-2 flex justify-end gap-2">
                        <button type="button" id="supplier-modal-cancel" class="px-4 h-10 text-xs font-semibold rounded-lg border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-800">Cancelar</button>
                        <button type="submit" id="supplier-save" class="px-4 h-10 text-xs font-semibold rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white">Guardar y seleccionar</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- ── Modal: nuevo producto (para cuando lo que se compra todavía no está en el catálogo) ── --}}
    @if ($canCreateProduct)
        <div id="product-modal" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm p-4 flex items-center justify-center">
            <div class="w-full max-w-md bg-white dark:bg-slate-900 rounded-2xl border border-gray-200 dark:border-slate-700 shadow-2xl overflow-hidden max-h-[90vh] flex flex-col">
                <div class="px-5 py-4 border-b border-gray-100 dark:border-slate-800 flex items-center justify-between shrink-0">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white">Nuevo producto</h3>
                    <button type="button" id="product-modal-close" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
                </div>
                <form id="product-form" class="p-5 space-y-3 overflow-y-auto">
                    <div id="product-form-errors" class="hidden p-2.5 rounded-lg bg-red-50 dark:bg-red-900/20 text-xs text-red-700 dark:text-red-300"></div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="{{ $label }}">SKU / Código <span class="text-red-500">*</span></label>
                            <input type="text" name="sku" required maxlength="100" placeholder="Ej. HM-0001" class="{{ $field }}">
                        </div>
                        <div>
                            <label class="{{ $label }}">Nombre <span class="text-red-500">*</span></label>
                            <input type="text" name="name" required maxlength="255" placeholder="Ej. Martillo de Uña 16oz" class="{{ $field }}">
                        </div>
                    </div>
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="text-xs font-semibold text-gray-700 dark:text-gray-300">Categoría <span class="text-red-500">*</span></label>
                            @if ($canCreateCategory)
                                <button type="button" id="new-category-btn" class="text-xs font-semibold text-emerald-600 hover:text-emerald-700 hover:underline">+ Nueva categoría</button>
                            @endif
                        </div>
                        <select name="category_id" id="product-category-id" required class="{{ $field }}">
                            <option value="">Selecciona una categoría</option>
                            @foreach ($categories as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="{{ $label }}">Unidad de medida <span class="text-red-500">*</span></label>
                            <select name="unit_id" required class="{{ $field }}">
                                <option value="">Selecciona</option>
                                @foreach ($measureUnits as $mu)
                                    <option value="{{ $mu->id }}" @selected($mu->name === 'Unidad')>{{ $mu->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="{{ $label }}">Precio de venta <span class="text-red-500">*</span></label>
                            <input type="number" step="0.01" min="0" name="unit_price" required placeholder="0.00" class="{{ $field }}">
                        </div>
                    </div>
                    <input type="hidden" name="create_unit" value="1">
                    <p class="text-[11px] text-gray-500 dark:text-gray-400">Esta será la presentación base del producto (factor 1). El costo de esta compra se indica al agregarlo al carrito.</p>
                    <div class="pt-2 flex justify-end gap-2">
                        <button type="button" id="product-modal-cancel" class="px-4 h-10 text-xs font-semibold rounded-lg border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-800">Cancelar</button>
                        <button type="submit" id="product-save" class="px-4 h-10 text-xs font-semibold rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white">Guardar y agregar</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ── Sub-modal: nueva categoría (se abre desde el alta de producto) ── --}}
        @if ($canCreateCategory)
            <div id="category-modal" class="hidden fixed inset-0 z-[60] bg-slate-900/60 backdrop-blur-sm p-4 flex items-center justify-center">
                <div class="w-full max-w-sm bg-white dark:bg-slate-900 rounded-2xl border border-gray-200 dark:border-slate-700 shadow-2xl overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-100 dark:border-slate-800 flex items-center justify-between">
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white">Nueva categoría</h3>
                        <button type="button" id="category-modal-close" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
                    </div>
                    <form id="category-form" class="p-5 space-y-3">
                        <div id="category-form-errors" class="hidden p-2.5 rounded-lg bg-red-50 dark:bg-red-900/20 text-xs text-red-700 dark:text-red-300"></div>
                        <div>
                            <label class="{{ $label }}">Nombre <span class="text-red-500">*</span></label>
                            <input type="text" name="name" required maxlength="255" placeholder="Ej. Grifería" class="{{ $field }}">
                        </div>
                        <div class="pt-2 flex justify-end gap-2">
                            <button type="button" id="category-modal-cancel" class="px-4 h-10 text-xs font-semibold rounded-lg border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-800">Cancelar</button>
                            <button type="submit" id="category-save" class="px-4 h-10 text-xs font-semibold rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white">Guardar y seleccionar</button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    @endif

    <x-ui.image-preview-modal />

    <script>
        (async () => {
            // El catálogo NO se manda entero al navegador (con miles de productos eso cuelga la
            // pantalla): se pide al servidor a medida que se escribe (ver PurchaseController::search()),
            // igual que en Ventas/Pedidos/Consignaciones/Proforma.
            const searchUrl = @json(route('purchases.products.search'));
            async function fetchUnits(params) {
                const url = new URL(searchUrl, window.location.origin);
                Object.entries(params).forEach(([k, v]) => { if (v !== undefined && v !== null && v !== '') url.searchParams.set(k, v); });
                try {
                    const res = await fetch(url, { headers: { Accept: 'application/json' } });
                    if (!res.ok) return [];
                    const data = await res.json().catch(() => ({ units: [] }));
                    return data.units || [];
                } catch (e) {
                    return [];
                }
            }

            const warehouses = @json($warehouses);
            const submitLabel = @json($editing ? 'Agregar a la compra' : 'Registrar compra');
            const initial = @json($oldItems);
            const $ = (id) => document.getElementById(id);

            // Almacén predeterminado guardado en este navegador (ver Almacenes → "Usar como predeterminado").
            // No se aplica si el formulario reapareció por un error (se respeta lo ya escrito) ni al agregar
            // productos a una compra existente (esa compra ya tiene su propio almacén).
            @if (! $editing)
                if (! @json(old('warehouse_id') !== null)) {
                    try {
                        const savedWarehouse = JSON.parse(localStorage.getItem('pos-default-location') || '{}').warehouse;
                        if (savedWarehouse && document.querySelector(`#warehouse-id option[value="${savedWarehouse}"]`)) {
                            document.getElementById('warehouse-id').value = savedWarehouse;
                        }
                    } catch (e) {}
                }
            @endif
            const money = (n) => (Math.round(n * 100) / 100).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            const lines = [];
            let distributing = false; // true si algún producto se está repartiendo entre almacenes

            const search = $('product-search'), results = $('product-results'), body = $('lines-body');
            const submitBtn = $('submit-btn'), warning = $('warning'), payNow = $('pay-now');

            function refresh() {
                let total = 0, valid = lines.length > 0, problems = [];
                lines.forEach((l) => {
                    const qty = Number(l.qty.value), cost = parseFloat(l.cost.value);
                    const sub = (isNaN(qty) || isNaN(cost)) ? 0 : Math.round(qty * cost * 100) / 100;
                    l.sub.textContent = money(sub);
                    total += sub;
                    if (!Number.isInteger(qty) || qty < 1) valid = false;
                    if (isNaN(cost) || cost < 0) valid = false;
                    if (!checkDistribution(l)) valid = false;
                });
                if (lines.some((l) => parseFloat(l.cost.value) === 0)) problems.push('Hay productos con costo 0: se guardarán sin costo.');

                // Si se distribuye en almacenes: el campo Almacén se oculta y todos los productos deben repartirse.
                distributing = lines.some((l) => l.dist.rows.length > 0);
                $('warehouse-field').classList.toggle('hidden', distributing);
                $('date-field')?.classList.toggle('col-span-2', distributing);
                if (distributing) {
                    const first = lines.find((l) => l.dist.rows.length)?.dist.rows[0].select.value;
                    if (first) wh.value = first; // la sucursal de la compra sale del primer destino
                    const missing = lines.filter((l) => !l.dist.rows.length);
                    if (missing.length) {
                        valid = false;
                        problems.push('Estás distribuyendo: reparte también ' + missing.map((l) => l.unit.name).join(', ') + '.');
                    }
                }
                syncCashboxes();
                $('total').textContent = money(total);
                $('empty-lines').classList.toggle('hidden', lines.length > 0);
                warning.textContent = problems.join(' · ');
                warning.classList.toggle('hidden', problems.length === 0);
                submitBtn.disabled = !valid;
                submitBtn.textContent = lines.length && valid ? `${submitLabel} · ${money(total)}` : submitLabel;
            }

            function reindex() {
                lines.forEach((l, i) => {
                    l.hid.name = `items[${i}][product_unit_id]`;
                    l.qty.name = `items[${i}][quantity]`;
                    l.cost.name = `items[${i}][unit_cost]`;
                    l.dist.rows.forEach((r, j) => {
                        r.select.name = `items[${i}][distribution][${j}][warehouse_id]`;
                        r.qty.name = `items[${i}][distribution][${j}][quantity]`;
                    });
                });
            }

            // ── Reparto de un producto entre almacenes ──
            const fieldClass = 'h-9 px-2 text-sm rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white';
            const distSum = (line) => line.dist.rows.reduce((s, r) => s + (parseInt(r.qty.value, 10) || 0), 0);

            function buildDistribution(line) {
                const tr = document.createElement('tr');
                tr.className = 'hidden dist-row bg-emerald-50/40 dark:bg-slate-800/40';
                tr.innerHTML = `<td colspan="5" class="px-3 py-3">
                    <div class="dist-list space-y-2"></div>
                    <div class="flex items-center justify-between gap-2 mt-2">
                        <button type="button" class="dist-add text-xs font-semibold text-emerald-600 hover:text-emerald-700 hover:underline">+ Agregar almacén</button>
                        <span class="dist-status text-xs font-semibold"></span>
                    </div>
                </td>`;
                line.distRow = tr;
                line.dist.list = tr.querySelector('.dist-list');
                line.dist.status = tr.querySelector('.dist-status');

                // Un solo botón hace de interruptor: el texto y el color avisan qué va a pasar si lo tocás.
                // Antes había además un título "Repartir entre almacenes" y un botón "Quitar reparto" que
                // repetían lo mismo y confundían — se saca todo eso y queda un único camino.
                line.setDistButtonState = (open) => {
                    line.distBtn.textContent = open ? 'Quitar reparto entre almacenes' : 'Distribuir en almacenes';
                    line.distBtn.classList.toggle('text-emerald-600', !open);
                    line.distBtn.classList.toggle('hover:text-emerald-700', !open);
                    line.distBtn.classList.toggle('text-red-600', open);
                    line.distBtn.classList.toggle('hover:text-red-700', open);
                };

                line.distBtn.addEventListener('click', () => {
                    const opening = tr.classList.contains('hidden');
                    tr.classList.toggle('hidden', !opening);
                    line.setDistButtonState(opening);
                    if (opening) lines.forEach(ensureDistribution); // al distribuir, todos los productos deben repartirse
                    else clearDistribution(line);
                    reindex(); refresh();
                });
                tr.querySelector('.dist-add').addEventListener('click', () => {
                    const used = line.dist.rows.map((r) => Number(r.select.value));
                    const free = warehouses.find((w) => !used.includes(w.id));
                    const left = Math.max(1, (Number(line.qty.value) || 0) - distSum(line));
                    addDistRow(line, free ? free.id : warehouses[0].id, left);
                    reindex(); refresh();
                });
            }

            // Abre el reparto de un producto con todo su cantidad en el almacén elegido por defecto.
            function ensureDistribution(line) {
                if (line.dist.rows.length) return;
                line.distRow.classList.remove('hidden');
                line.setDistButtonState(true);
                addDistRow(line, Number(wh.value) || warehouses[0].id, Number(line.qty.value) || 1);
            }

            function addDistRow(line, warehouseId, qty) {
                const div = document.createElement('div');
                div.className = 'flex items-center gap-2';
                const select = document.createElement('select');
                select.className = fieldClass + ' flex-1';
                warehouses.forEach((w) => select.add(new Option(w.name, w.id, false, String(w.id) === String(warehouseId))));
                const input = document.createElement('input');
                input.type = 'number'; input.min = '1'; input.step = '1'; input.inputMode = 'numeric';
                input.className = fieldClass + ' w-24 text-center';
                input.value = qty;
                input.addEventListener('keydown', (e) => { if (['.', ',', 'e', 'E', '-', '+'].includes(e.key)) e.preventDefault(); });
                const rm = document.createElement('button');
                rm.type = 'button'; rm.innerHTML = '&times;'; rm.title = 'Quitar';
                rm.className = 'text-gray-400 hover:text-red-600 text-lg leading-none px-1';
                div.append(select, input, rm);
                line.dist.list.appendChild(div);
                const row = { el: div, select, qty: input };
                line.dist.rows.push(row);
                [select, input].forEach((el) => el.addEventListener('input', refresh));
                rm.addEventListener('click', () => {
                    line.dist.rows.splice(line.dist.rows.indexOf(row), 1);
                    div.remove();
                    if (!line.dist.rows.length) { line.distRow.classList.add('hidden'); line.setDistButtonState(false); }
                    reindex(); refresh();
                });
            }

            function clearDistribution(line) {
                line.dist.rows.forEach((r) => r.el.remove());
                line.dist.rows = [];
            }

            // Sin reparto todo va al almacén principal; con reparto tiene que sumar exactamente lo comprado.
            function checkDistribution(line) {
                if (!line.dist.rows.length) return true;
                const qty = parseInt(line.qty.value, 10) || 0;
                const ids = line.dist.rows.map((r) => r.select.value);
                const sum = distSum(line);
                const distinct = new Set(ids).size === ids.length;
                const ok = distinct && sum === qty && line.dist.rows.every((r) => (parseInt(r.qty.value, 10) || 0) >= 1);
                line.dist.status.className = 'dist-status text-xs font-semibold ' + (ok ? 'text-emerald-600' : 'text-red-600');
                line.dist.status.textContent = !distinct ? 'Hay un almacén repetido'
                    : sum === qty ? `Repartido completo: ${sum} de ${qty}`
                    : sum < qty ? `Repartido ${sum} de ${qty} · faltan ${qty - sum}`
                    : `Repartido ${sum} de ${qty} · sobran ${sum - qty}`;
                return ok;
            }

            function addLine(u, qty = 1, cost = null, distribution = []) {
                const existing = lines.find((l) => l.unit.id === u.id);
                if (existing) { existing.qty.value = (parseInt(existing.qty.value, 10) || 0) + 1; refresh(); return; }

                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td class="px-2 sm:px-3 py-2"><div class="font-semibold text-gray-900 dark:text-white sm:truncate"></div><div class="text-[11px] text-gray-500 sm:truncate"></div></td>
                    <td class="px-1 sm:px-3 py-2">
                        <div class="flex items-center justify-center">
                            <button type="button" class="dec w-7 h-9 sm:w-9 rounded-l-lg border border-gray-300 dark:border-slate-600 bg-gray-50 dark:bg-slate-800 text-gray-700 dark:text-gray-200 text-lg font-bold hover:bg-gray-100 dark:hover:bg-slate-700" aria-label="Disminuir">&minus;</button>
                            <input type="number" step="1" min="1" inputmode="numeric" placeholder="1" class="qty w-9 sm:w-14 h-9 px-0.5 sm:px-1 text-sm text-center border-y border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none">
                            <button type="button" class="inc w-7 h-9 sm:w-9 rounded-r-lg border border-gray-300 dark:border-slate-600 bg-gray-50 dark:bg-slate-800 text-gray-700 dark:text-gray-200 text-lg font-bold hover:bg-gray-100 dark:hover:bg-slate-700" aria-label="Aumentar">+</button>
                        </div>
                    </td>
                    <td class="px-1 sm:px-3 py-2"><input type="number" step="0.01" min="0" placeholder="0.00" class="cost w-full h-9 px-1 sm:px-2 text-sm text-right rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"></td>
                    <td class="px-1 sm:px-3 py-2 text-right text-xs sm:text-sm font-semibold text-gray-900 dark:text-white sub">0.00</td>
                    <td class="px-1 sm:px-3 py-2 text-center"><button type="button" class="rm text-gray-400 hover:text-red-600 text-lg leading-none" title="Quitar">&times;</button><input type="hidden" class="hid"></td>`;
                tr.children[0].children[0].textContent = u.name + ' — ' + u.unit;
                tr.children[0].children[0].title = u.name + ' — ' + u.unit;
                tr.children[0].children[1].textContent = u.sku + (u.factor !== 1 ? ` · 1 ${u.unit} = ${u.factor} unid. base` : '');
                const distBtn = document.createElement('button');
                distBtn.type = 'button';
                distBtn.className = 'mt-1 text-[11px] font-semibold text-emerald-600 hover:text-emerald-700 hover:underline';
                distBtn.textContent = 'Distribuir en almacenes';
                tr.children[0].appendChild(distBtn);
                const line = { unit: u, row: tr, qty: tr.querySelector('.qty'), cost: tr.querySelector('.cost'), sub: tr.querySelector('.sub'), hid: tr.querySelector('.hid'), dist: { rows: [] }, distBtn };
                line.hid.value = u.id;
                line.qty.value = Math.max(1, Math.round(Number(qty)) || 1);
                // Cantidad siempre entera: botones − / + y se descartan los decimales al escribir.
                const step = (d) => { line.qty.value = Math.max(1, (parseInt(line.qty.value, 10) || 0) + d); refresh(); };
                tr.querySelector('.dec').addEventListener('click', () => step(-1));
                tr.querySelector('.inc').addEventListener('click', () => step(1));
                line.qty.addEventListener('keydown', (e) => { if (['.', ',', 'e', 'E', '-', '+'].includes(e.key)) e.preventDefault(); });
                line.qty.addEventListener('change', () => { line.qty.value = Math.max(1, Math.round(Number(line.qty.value)) || 1); refresh(); });
                line.cost.value = cost ?? u.cost;
                [line.qty, line.cost].forEach((el) => {
                    el.addEventListener('input', refresh);
                    el.addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); search.focus(); } });
                });
                tr.querySelector('.rm').addEventListener('click', () => { lines.splice(lines.indexOf(line), 1); tr.remove(); line.distRow.remove(); reindex(); refresh(); });
                buildDistribution(line);
                lines.push(line);
                body.appendChild(tr);
                body.appendChild(line.distRow);
                distribution.forEach((d) => addDistRow(line, d.warehouse_id, d.quantity));
                if (distribution.length) { line.distRow.classList.remove('hidden'); line.setDistButtonState(true); }
                else if (distributing) ensureDistribution(line); // un producto nuevo mientras se distribuye también se reparte
                reindex();
                refresh();
                line.qty.focus(); line.qty.select();
            }

            // ── Buscador de productos ──
            let lastResults = [];

            function paintResults(found) {
                lastResults = found;
                results.innerHTML = '';
                if (!found.length) {
                    results.innerHTML = '<div class="px-3 py-3 text-sm text-gray-500">Sin resultados.</div>';
                    results.classList.remove('hidden');
                    return;
                }
                found.forEach((u) => {
                    const b = document.createElement('button');
                    b.type = 'button';
                    b.className = 'w-full flex items-center gap-3 text-left px-3 py-2 hover:bg-emerald-50 dark:hover:bg-slate-700 border-b border-gray-100 dark:border-slate-700';

                    const imgWrap = document.createElement('div');
                    imgWrap.className = 'shrink-0';
                    if (u.photo_url) {
                        imgWrap.classList.add('relative', 'group/searchimg', 'cursor-zoom-in');
                        imgWrap.title = 'Clic para ampliar imagen';
                        const img = document.createElement('img');
                        img.src = u.photo_url;
                        img.alt = '';
                        img.className = 'w-10 h-10 object-cover rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-slate-800';
                        imgWrap.appendChild(img);
                        imgWrap.addEventListener('click', (e) => { e.stopPropagation(); openImagePreview(u.photo_url, u.name || ''); });
                    } else {
                        imgWrap.innerHTML = '<div class="w-10 h-10 rounded-lg bg-gray-100 dark:bg-slate-800 flex items-center justify-center text-gray-300 dark:text-gray-600"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14M4 8h.01M4 4h16a1 1 0 011 1v14a1 1 0 01-1 1H4a1 1 0 01-1-1V5a1 1 0 011-1z"/></svg></div>';
                    }

                    const info = document.createElement('div');
                    info.className = 'min-w-0 flex-1';
                    const title = document.createElement('div');
                    title.className = 'text-sm font-semibold text-gray-900 dark:text-white truncate';
                    title.textContent = `${u.name} — ${u.unit}`;
                    const subtitle = document.createElement('div');
                    subtitle.className = 'text-[11px] text-gray-500 truncate';
                    subtitle.textContent = `${u.sku}${u.barcode && u.barcode !== u.sku ? ' · ' + u.barcode : ''} · último costo ${money(u.cost)}`;
                    info.append(title, subtitle);

                    b.append(imgWrap, info);
                    b.addEventListener('click', () => { addLine(u); search.value = ''; results.classList.add('hidden'); });
                    results.appendChild(b);
                });
                results.classList.remove('hidden');
            }

            let searchSeq = 0;
            let searchDebounce = null;

            function renderResults() {
                const q = search.value.trim();
                const seq = ++searchSeq;
                clearTimeout(searchDebounce);
                if (!q) { results.classList.add('hidden'); return; }
                searchDebounce = setTimeout(async () => {
                    const found = await fetchUnits({ q });
                    if (seq !== searchSeq) return; // llegó una búsqueda más nueva mientras esperábamos esta
                    paintResults(found);
                }, 200);
            }
            search.addEventListener('input', renderResults);
            search.addEventListener('keydown', (e) => {
                if (e.key !== 'Enter') return;
                e.preventDefault();
                const q = search.value.trim();
                if (!q) return;
                // Un código de barras/SKU exacto se agrega directo (pistola lectora); si no, el primero
                // de lo que ya está mostrado (o se pide de nuevo si todavía no se mostró nada).
                (async () => {
                    const exact = (await fetchUnits({ code: q }))[0];
                    const first = exact || lastResults[0] || (await fetchUnits({ q }))[0];
                    if (first) { addLine(first); search.value = ''; results.classList.add('hidden'); }
                })();
            });
            document.addEventListener('click', (e) => { if (!results.contains(e.target) && e.target !== search) results.classList.add('hidden'); });

            // ── Sucursal → almacenes que usa (propios o compartidos) y sus cajas ──
            const wh = $('warehouse-id'), cb = $('cashbox-id'), branchSel = $('branch-id');
            function syncWarehouses() {
                if (!branchSel) return;
                let firstVisible = null;
                [...wh.options].forEach((o) => {
                    const show = o.dataset.branches.split(',').includes(branchSel.value);
                    o.hidden = !show; o.disabled = !show;
                    if (show && !firstVisible) firstVisible = o;
                });
                if (wh.selectedOptions[0]?.disabled && firstVisible) wh.value = firstVisible.value;
            }
            function syncCashboxes() {
                // Al distribuir hay varios destinos (posiblemente de varias sucursales): se pueden usar todas las cajas.
                const branch = distributing ? null : (branchSel ? branchSel.value : wh.selectedOptions[0]?.dataset.branch);
                let firstVisible = null;
                [...cb.options].forEach((o) => {
                    const show = branch === null || o.dataset.branch === branch;
                    o.hidden = !show; o.disabled = !show;
                    if (show && !firstVisible) firstVisible = o;
                });
                if (cb.selectedOptions[0]?.disabled && firstVisible) cb.value = firstVisible.value;
            }
            wh.addEventListener('change', syncCashboxes);
            branchSel?.addEventListener('change', () => { syncWarehouses(); syncCashboxes(); });
            syncWarehouses();

            // ── Contado / Crédito ──
            const payType = $('payment-type');
            const creditFields = $('credit-only-fields');
            const initialPaymentField = $('initial-payment-field');
            const payNowText = $('pay-now-text');

            function syncPayFields() {
                $('pay-fields').classList.toggle('hidden', !payNow.checked);
                if (initialPaymentField) initialPaymentField.classList.toggle('hidden', !(payType && payType.value === 'credit' && payNow.checked));
            }

            function setPayType(type) {
                if (!payType) return;
                payType.value = type;
                document.querySelectorAll('.pay-type-btn').forEach((b) => {
                    const active = b.dataset.type === type;
                    b.classList.toggle('bg-white', active);
                    b.classList.toggle('dark:bg-slate-900', active);
                    b.classList.toggle('shadow', active);
                    b.classList.toggle('text-gray-900', active);
                    b.classList.toggle('dark:text-white', active);
                    b.classList.toggle('text-gray-500', !active);
                    b.classList.toggle('dark:text-gray-400', !active);
                });
                creditFields?.classList.toggle('hidden', type !== 'credit');
                if (payNowText) payNowText.textContent = type === 'credit' ? 'Pagar un anticipo ahora' : 'Pagar ahora desde una caja';
                syncPayFields();
            }

            document.querySelectorAll('.pay-type-btn').forEach((b) => b.addEventListener('click', () => setPayType(b.dataset.type)));
            if (payType) setPayType(payType.value === 'credit' ? 'credit' : 'cash');

            payNow.addEventListener('change', syncPayFields);
            syncPayFields();
            syncCashboxes();

            // ── Alta rápida de proveedor (modal) ──
            const supModal = $('supplier-modal');
            if (supModal) {
                const form = $('supplier-form'), errBox = $('supplier-form-errors'), saveBtn = $('supplier-save'), sel = $('supplier-id');
                const close = () => supModal.classList.add('hidden');
                $('new-supplier-btn').addEventListener('click', () => { form.reset(); errBox.classList.add('hidden'); supModal.classList.remove('hidden'); form.elements.name.focus(); });
                $('supplier-modal-close').addEventListener('click', close);
                $('supplier-modal-cancel').addEventListener('click', close);
                form.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    saveBtn.disabled = true; saveBtn.textContent = 'Guardando...'; errBox.classList.add('hidden');
                    try {
                        const res = await fetch(@json(route('suppliers.store')), {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('#purchase-form input[name=_token]').value },
                            body: JSON.stringify(Object.fromEntries(new FormData(form))),
                        });
                        const data = await res.json().catch(() => ({}));
                        if (res.status === 422) {
                            errBox.innerHTML = Object.values(data.errors || {}).flat().map((m) => `<div>${m.replace(/</g, '&lt;')}</div>`).join('');
                            errBox.classList.remove('hidden');
                            return;
                        }
                        if (!res.ok) throw new Error(data.message || 'No se pudo guardar el proveedor.');
                        const opt = new Option(data.name + (data.document ? ' · ' + data.document : ''), data.id, true, true);
                        sel.add(opt);
                        sel.value = data.id;
                        close();
                    } catch (err) {
                        errBox.textContent = err.message; errBox.classList.remove('hidden');
                    } finally {
                        saveBtn.disabled = false; saveBtn.textContent = 'Guardar y seleccionar';
                    }
                });
            }

            // ── Alta rápida de producto (modal): para cuando lo que se compra todavía no está en el catálogo ──
            const prodModal = $('product-modal');
            if (prodModal) {
                const form = $('product-form'), errBox = $('product-form-errors'), saveBtn = $('product-save');
                const close = () => prodModal.classList.add('hidden');
                $('new-product-btn').addEventListener('click', () => { form.reset(); errBox.classList.add('hidden'); prodModal.classList.remove('hidden'); form.elements.sku.focus(); });
                $('product-modal-close').addEventListener('click', close);
                $('product-modal-cancel').addEventListener('click', close);
                form.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    saveBtn.disabled = true; saveBtn.textContent = 'Guardando...'; errBox.classList.add('hidden');
                    try {
                        const res = await fetch(@json(route('products.store')), {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('#purchase-form input[name=_token]').value },
                            body: JSON.stringify(Object.fromEntries(new FormData(form))),
                        });
                        const data = await res.json().catch(() => ({}));
                        if (res.status === 422) {
                            errBox.innerHTML = Object.values(data.errors || {}).flat().map((m) => `<div>${m.replace(/</g, '&lt;')}</div>`).join('');
                            errBox.classList.remove('hidden');
                            return;
                        }
                        if (!res.ok) throw new Error(data.message || 'No se pudo guardar el producto.');
                        if (data.unit) { units.push(data.unit); addLine(data.unit); }
                        close();
                        search.value = ''; results.classList.add('hidden');
                    } catch (err) {
                        errBox.textContent = err.message; errBox.classList.remove('hidden');
                    } finally {
                        saveBtn.disabled = false; saveBtn.textContent = 'Guardar y agregar';
                    }
                });
            }

            // ── Alta rápida de categoría (sub-modal, se abre desde el alta de producto) ──
            const catModal = $('category-modal');
            if (catModal) {
                const form = $('category-form'), errBox = $('category-form-errors'), saveBtn = $('category-save'), sel = $('product-category-id');
                const close = () => catModal.classList.add('hidden');
                $('new-category-btn').addEventListener('click', () => { form.reset(); errBox.classList.add('hidden'); catModal.classList.remove('hidden'); form.elements.name.focus(); });
                $('category-modal-close').addEventListener('click', close);
                $('category-modal-cancel').addEventListener('click', close);
                form.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    saveBtn.disabled = true; saveBtn.textContent = 'Guardando...'; errBox.classList.add('hidden');
                    try {
                        const res = await fetch(@json(route('categories.store')), {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('#purchase-form input[name=_token]').value },
                            body: JSON.stringify(Object.fromEntries(new FormData(form))),
                        });
                        const data = await res.json().catch(() => ({}));
                        if (res.status === 422) {
                            errBox.innerHTML = Object.values(data.errors || {}).flat().map((m) => `<div>${m.replace(/</g, '&lt;')}</div>`).join('');
                            errBox.classList.remove('hidden');
                            return;
                        }
                        if (!res.ok) throw new Error(data.message || 'No se pudo guardar la categoría.');
                        const opt = new Option(data.category.name, data.category.id, true, true);
                        sel.add(opt);
                        sel.value = data.category.id;
                        close();
                    } catch (err) {
                        errBox.textContent = err.message; errBox.classList.remove('hidden');
                    } finally {
                        saveBtn.disabled = false; saveBtn.textContent = 'Guardar y seleccionar';
                    }
                });
            }

            $('purchase-form').addEventListener('keydown', (e) => { if (e.key === 'Enter' && e.target.tagName === 'INPUT' && e.target.type !== 'submit') e.preventDefault(); });
            $('purchase-form').addEventListener('submit', () => { submitBtn.disabled = true; submitBtn.textContent = 'Guardando...'; });

            // Las líneas ya elegidas (venir de un error de validación) se piden puntualmente por id: no
            // hace falta pedir el catálogo entero para esto. Se compara como texto porque el id puede
            // llegar como número o como texto según el servidor (el de acá siempre es número por el cast
            // explícito en $oldItems, pero el de la búsqueda puede no serlo).
            if (initial.length) {
                const found = await fetchUnits({ ids: initial.map((i) => i.id).join(',') });
                const byId = new Map(found.map((u) => [String(u.id), u]));
                initial.forEach((i) => {
                    const u = byId.get(String(i.id));
                    if (u) addLine(u, i.quantity, i.cost, i.distribution || []);
                });
            }
            refresh();
        })();
    </script>
</x-app-layout>
