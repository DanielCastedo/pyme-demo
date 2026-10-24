<x-app-layout>
    <div class="max-w-10xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

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

        <x-ui.page-header category="Inventario" title="Nuevo Movimiento de Stock"
            description="Registra una entrada o salida de inventario en un almacén." />

        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 p-5 md:p-6">
            <form action="{{ route('stock-movements.store') }}" method="POST">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <x-ui.form-field type="select" name="type_id" label="Tipo de Movimiento" required
                        :value="old('type_id')" :options="$types->pluck('name', 'id')->toArray()" />
                    <x-ui.form-field type="date" name="date" label="Fecha" required
                        :value="old('date', date('Y-m-d'))" />
                    <x-ui.form-field type="select" name="warehouse_id" label="Almacén" required
                        :value="old('warehouse_id')" :options="$warehouses->pluck('name', 'id')->toArray()" />
                </div>

                <div class="mb-6">
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Buscar Producto</label>
                    <div class="relative w-full" id="product-search-wrapper">
                        <input type="text" id="product-search-input" placeholder="Escribe el nombre del producto..." autocomplete="off"
                            class="w-full px-3 h-10 text-sm rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">

                        <div id="product-search-list"
                            class="hidden absolute left-0 top-full mt-1 z-20 w-full max-h-56 overflow-y-auto rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 shadow-lg">
                        </div>
                    </div>
                    <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-1">
                        Selecciona un producto de la lista para agregarlo abajo.
                    </p>
                </div>

                <div class="mb-6">
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">Productos agregados</label>

                    {{-- Desde md: tabla. En celular: cada producto es una tarjeta con sus campos apilados (etiqueta + campo). --}}
                    <div class="md:overflow-x-auto border border-gray-200 dark:border-gray-700 rounded-lg">
                        <table class="w-full text-sm block md:table">
                            <thead class="hidden md:table-header-group bg-gray-50 dark:bg-slate-800 text-[11px] uppercase text-gray-500 dark:text-gray-400">
                                <tr>
                                    <th class="px-3 py-2 text-left">Producto / Unidad</th>
                                    <th class="px-3 py-2 text-left w-28">Cantidad</th>
                                    <th class="px-3 py-2 text-left w-44">Costo unitario <span class="normal-case font-normal text-gray-400">(por unidad base)</span></th>
                                    <th class="px-3 py-2 text-left w-44">Costo total</th>
                                    <th class="px-3 py-2 w-10"></th>
                                </tr>
                            </thead>
                            <tbody id="items-body" class="block md:table-row-group divide-y divide-gray-100 dark:divide-gray-800">
                                <tr id="items-empty-row" class="block md:table-row">
                                    <td colspan="5" class="block md:table-cell px-3 py-6 text-center text-xs text-gray-400 dark:text-gray-500 italic">
                                        Todavía no agregaste ningún producto. Buscalo arriba.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-1">
                        El costo solo se completa en movimientos de <strong>entrada</strong> (compra). En salidas queda
                        deshabilitado porque no aplica. Si sabes cuánto costó todo (por ejemplo un paquete o una caja),
                        escríbelo en «Costo total» y el sistema calcula el costo unitario (total ÷ cantidad, y ÷ lo que trae la presentación).
                        Lo que se guarda es el costo unitario.
                    </p>
                </div>

                <x-ui.form-field type="textarea" name="note" label="Nota" :value="old('note')" :rows="2" />

                <div class="mt-6 pt-4 border-t border-gray-100 dark:border-gray-800 flex flex-col-reverse sm:flex-row justify-end gap-2">
                    <a href="{{ route('stock-movements.index') }}" class="w-full sm:w-auto">
                        <x-ui.button type="button" variant="outline" :fullWidth="true">Cancelar</x-ui.button>
                    </a>
                    <x-ui.button type="submit" variant="primary" :fullWidth="true" class="sm:w-auto">Guardar Movimiento</x-ui.button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            (function() {
                // El catálogo NO se manda entero al navegador (con miles de productos eso cuelga la
                // pantalla): se pide al servidor a medida que se escribe (ver StockMovementController::search()).
                const searchUrl = @json(route('stock-movements.products.search'));
                async function fetchUnits(q) {
                    const url = new URL(searchUrl, window.location.origin);
                    if (q) url.searchParams.set('q', q);
                    try {
                        const res = await fetch(url, { headers: { Accept: 'application/json' } });
                        if (!res.ok) return [];
                        const data = await res.json().catch(() => ({ units: [] }));
                        return data.units || [];
                    } catch (e) {
                        return [];
                    }
                }
                const typeSigns = @json($types->pluck('sign', 'id'));

                // Almacén predeterminado guardado en este navegador (ver Almacenes → "Usar como predeterminado").
                // El campo "Almacén" es el buscador-desplegable de x-ui.form-field (un input oculto con el id
                // + un input de texto con la etiqueta), no un <select> común, así que se llenan los dos a mano.
                // No se aplica si el formulario reapareció por un error: ahí se respeta lo ya elegido.
                if (! @json(old('warehouse_id') !== null)) {
                    try {
                        const warehouseNames = @json($warehouses->pluck('name', 'id'));
                        const savedWarehouse = JSON.parse(localStorage.getItem('pos-default-location') || '{}').warehouse;
                        const hidden = document.getElementById('warehouse_id');
                        const label = savedWarehouse ? warehouseNames[savedWarehouse] : null;
                        if (hidden && label) {
                            hidden.value = savedWarehouse;
                            const textInput = hidden.closest('[data-ac-wrapper]')?.querySelector('[data-ac-input]');
                            if (textInput) textInput.value = label;
                        }
                    } catch (e) {}
                }

                const searchInput = document.getElementById('product-search-input');
                const searchList = document.getElementById('product-search-list');
                const tbody = document.getElementById('items-body');
                const emptyRow = document.getElementById('items-empty-row');
                const typeHidden = document.getElementById('type_id');

                let rowIndex = 0;
                const addedRows = {}; // product_unit_id -> <tr>

                const inputClass = 'w-full h-9 px-2 text-sm rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white placeholder-gray-400 disabled:opacity-50 disabled:cursor-not-allowed';

                function costAllowed() {
                    const sign = typeSigns[typeHidden?.value] ?? null;
                    return sign === '+';
                }

                function refreshCostFields() {
                    const allowed = costAllowed();
                    tbody.querySelectorAll('.unit-cost-input, .total-cost-input').forEach(input => {
                        input.disabled = !allowed;
                        if (!allowed) {
                            input.value = '';
                        }
                    });
                    tbody.querySelectorAll('.total-cost-input').forEach(input => {
                        input.disabled = !allowed;
                        if (!allowed) {
                            input.value = '';
                            input.closest('td').querySelector('.total-cost-hint').textContent = '';
                        }
                    });
                }

                let searchSeq = 0;
                let searchDebounce = null;

                function renderSearchResults(query) {
                    const q = query.trim();
                    const seq = ++searchSeq;
                    clearTimeout(searchDebounce);
                    searchDebounce = setTimeout(async () => {
                        const filtered = await fetchUnits(q);
                        if (seq !== searchSeq) return; // llegó una búsqueda más nueva mientras esperábamos esta
                        paintSearchResults(filtered);
                    }, q ? 200 : 0);
                }

                function paintSearchResults(filtered) {
                    searchList.innerHTML = '';

                    if (!filtered.length) {
                        searchList.innerHTML = '<div class="px-3 py-2 text-sm text-gray-500 dark:text-gray-400">Sin resultados</div>';
                        searchList.classList.remove('hidden');
                        return;
                    }

                    filtered.forEach(u => {
                        const btn = document.createElement('button');
                        btn.type = 'button';
                        btn.className = 'w-full text-left px-3 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-emerald-50 dark:hover:bg-slate-700';
                        btn.textContent = u.label;
                        btn.addEventListener('click', () => selectProductUnit(u));
                        searchList.appendChild(btn);
                    });

                    searchList.classList.remove('hidden');
                }

                function selectProductUnit(unit) {
                    searchInput.value = '';
                    searchList.classList.add('hidden');

                    // Si ya está agregado, solo enfoca su cantidad en vez de duplicar la línea.
                    if (addedRows[unit.id]) {
                        addedRows[unit.id].querySelector('.quantity-input').focus();
                        return;
                    }

                    addRow(unit);
                }

                function addRow(unit) {
                    emptyRow.remove();

                    const index = rowIndex++;
                    const tr = document.createElement('tr');
                    // Celular: tarjeta (bloque) con botón de quitar arriba a la derecha. Desde md: fila de tabla normal.
                    tr.className = 'block md:table-row relative p-3 md:p-0';
                    const allowed = costAllowed();
                    const label = (text) => `<span class="md:hidden block mb-1 text-[10px] font-semibold uppercase text-gray-500 dark:text-gray-400">${text}</span>`;

                    tr.innerHTML = `
                        <td class="block md:table-cell md:px-3 md:py-2 pr-8 md:pr-3 mb-3 md:mb-0 text-gray-800 dark:text-gray-200 font-medium">
                            ${unit.label}
                            <input type="hidden" name="items[${index}][product_unit_id]" value="${unit.id}">
                        </td>
                        <td class="block md:table-cell md:px-3 md:py-2 mb-3 md:mb-0">
                            ${label('Cantidad')}
                            <input type="number" step="1" min="1" inputmode="numeric" onkeydown="if (['.', ',', 'e', 'E', '-', '+'].includes(event.key)) event.preventDefault()" name="items[${index}][quantity]" required placeholder="Cantidad" class="quantity-input ${inputClass}">
                        </td>
                        <td class="block md:table-cell md:px-3 md:py-2 mb-3 md:mb-0">
                            ${label('Costo unitario (por unidad base)')}
                            <input type="number" step="0.01" min="0" inputmode="decimal" placeholder="Ej. 3.00" name="items[${index}][unit_cost]" class="unit-cost-input ${inputClass}" ${allowed ? '' : 'disabled'}>
                        </td>
                        <td class="block md:table-cell md:px-3 md:py-2">
                            ${label('Costo total')}
                            <input type="number" step="0.01" min="0" inputmode="decimal" placeholder="Ej. 300.00" class="total-cost-input ${inputClass}" ${allowed ? '' : 'disabled'} aria-label="Costo total (solo ayuda, no se guarda)">
                            <span class="total-cost-hint block mt-1 text-[10px] text-gray-500 dark:text-gray-400"></span>
                        </td>
                        <td class="block md:table-cell absolute top-2 right-2 md:static md:px-3 md:py-2 md:text-right">
                            <button type="button" class="remove-row text-red-500 hover:text-red-700 p-1" title="Quitar producto" aria-label="Quitar producto">
                                <svg class="w-5 h-5 md:w-4 md:h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </td>
                    `;

                    tbody.appendChild(tr);
                    addedRows[unit.id] = tr;

                    tr.querySelector('.remove-row').addEventListener('click', () => {
                        delete addedRows[unit.id];
                        tr.remove();
                        if (!tbody.children.length) {
                            tbody.appendChild(emptyRow);
                        }
                    });

                    // Costo total y costo unitario se calculan entre sí: escribes uno y el otro se completa.
                    //   costo unitario (por unidad base) = costo total ÷ (cantidad × unidades base que trae la presentación)
                    // Solo se guarda el costo unitario; el total no se envía.
                    const totalInput = tr.querySelector('.total-cost-input');
                    const unitCostInput = tr.querySelector('.unit-cost-input');
                    const totalHint = tr.querySelector('.total-cost-hint');
                    const quantityField = tr.querySelector('.quantity-input');
                    const factor = Math.max(1, parseInt(unit.factor, 10) || 1);
                    const money = (n) => n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    const trim = (n, d) => n.toFixed(d).replace(/0+$/, '').replace(/\.$/, '');
                    let lastEdited = null; // 'total' | 'unit'

                    function recalcFromTotal() {
                        const total = parseFloat(totalInput.value);
                        const qty = parseInt(quantityField.value, 10);
                        if (isNaN(total) || total < 0) { totalHint.textContent = ''; return; }
                        if (isNaN(qty) || qty < 1) { totalHint.textContent = 'Indica la cantidad para calcular.'; return; }
                        const base = qty * factor;
                        const perBase = total / base;
                        unitCostInput.value = perBase.toFixed(2);
                        const rounded = Math.abs(parseFloat(unitCostInput.value) - perBase) > 0.00001;
                        totalHint.textContent = `${money(total)} ÷ ${base}${factor > 1 ? ` (${qty} × ${factor})` : ''} = ${trim(perBase, 4)} c/u${rounded ? ' → se guarda ' + unitCostInput.value : ''}`;
                    }

                    function recalcFromUnit() {
                        const unitCost = parseFloat(unitCostInput.value);
                        const qty = parseInt(quantityField.value, 10);
                        if (isNaN(unitCost) || unitCost < 0) { totalInput.value = ''; totalHint.textContent = ''; return; }
                        if (isNaN(qty) || qty < 1) { totalInput.value = ''; totalHint.textContent = 'Indica la cantidad para calcular el total.'; return; }
                        const base = qty * factor;
                        const total = unitCost * base;
                        totalInput.value = total.toFixed(2);
                        totalHint.textContent = `${trim(unitCost, 4)} × ${base}${factor > 1 ? ` (${qty} × ${factor})` : ''} = ${money(total)}`;
                    }

                    totalInput.addEventListener('input', () => { lastEdited = 'total'; recalcFromTotal(); });
                    unitCostInput.addEventListener('input', () => { lastEdited = 'unit'; recalcFromUnit(); });
                    // Al cambiar la cantidad se conserva lo que el usuario escribió y se recalcula el otro valor
                    quantityField.addEventListener('input', () => {
                        if (lastEdited === 'total') recalcFromTotal();
                        else if (lastEdited === 'unit') recalcFromUnit();
                    });
                    quantityField.focus();
                }

                searchInput.addEventListener('focus', () => renderSearchResults(searchInput.value));
                searchInput.addEventListener('input', () => renderSearchResults(searchInput.value));
                document.addEventListener('click', (e) => {
                    if (!document.getElementById('product-search-wrapper').contains(e.target)) {
                        searchList.classList.add('hidden');
                    }
                });

                typeHidden?.addEventListener('change', refreshCostFields);
                refreshCostFields();
            })();
        </script>
    @endpush
</x-app-layout>
