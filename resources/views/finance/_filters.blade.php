{{-- Filtros de Finanzas. Recibe: $f, $filterData, $action; opcional: $withType (tipo de movimiento), $withOrder + $order (orden de productos) --}}
@php
    $withType = $withType ?? false;
    $withOrder = $withOrder ?? false;
    $fieldClass = 'w-full h-10 px-3 text-sm rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500';
    $labelClass = 'block text-[11px] font-semibold text-gray-600 dark:text-gray-400 mb-1';
    $selected = fn ($a, $b) => (string) $a === (string) $b ? 'selected' : '';
    $uid = 'finance_filters_' . md5($action);
@endphp

<div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-gray-700 rounded-xl p-4 mb-5">
    <button type="button" id="{{ $uid }}-toggle"
        class="flex items-center gap-1.5 text-xs font-semibold text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">
        <svg id="{{ $uid }}-icon" class="w-3.5 h-3.5 transition-transform duration-150 -rotate-90" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
        <span>Filtros</span>
    </button>

    <form method="GET" action="{{ $action }}" id="{{ $uid }}-form" class="mt-4 hidden">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
        <div>
            <label class="{{ $labelClass }}">Desde</label>
            <input type="date" name="from" value="{{ $f['from'] }}" class="{{ $fieldClass }}">
        </div>
        <div>
            <label class="{{ $labelClass }}">Hasta</label>
            <input type="date" name="to" value="{{ $f['to'] }}" class="{{ $fieldClass }}">
        </div>
        <div>
            <label class="{{ $labelClass }}">Sucursal</label>
            <select name="branch" id="f-branch" class="{{ $fieldClass }}">
                <option value="">Todas</option>
                @foreach ($filterData['branches'] as $id => $name)
                    <option value="{{ $id }}" {{ $selected($f['branch'], $id) }}>{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="{{ $labelClass }}">Almacén</label>
            <select name="warehouse" id="f-warehouse" class="{{ $fieldClass }}">
                <option value="">Todos</option>
                @foreach ($filterData['warehouses'] as $w)
                    <option value="{{ $w->id }}" data-branch="{{ $w->branche_id }}" {{ $selected($f['warehouse'], $w->id) }}>{{ $w->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="{{ $labelClass }}">Producto</label>
            <select name="product" class="{{ $fieldClass }}">
                <option value="">Todos</option>
                @foreach ($filterData['products'] as $p)
                    <option value="{{ $p->id }}" {{ $selected($f['product'], $p->id) }}>{{ $p->name }} ({{ $p->sku }})</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="{{ $labelClass }}">Cliente</label>
            <select name="customer" class="{{ $fieldClass }}">
                <option value="">Todos</option>
                @foreach ($filterData['customers'] as $id => $name)
                    <option value="{{ $id }}" {{ $selected($f['customer'], $id) }}>{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="{{ $labelClass }}">Usuario</label>
            <select name="user" class="{{ $fieldClass }}">
                <option value="">Todos</option>
                @foreach ($filterData['users'] as $id => $name)
                    <option value="{{ $id }}" {{ $selected($f['user'], $id) }}>{{ $name }}</option>
                @endforeach
            </select>
        </div>

        @if ($withType)
            <div>
                <label class="{{ $labelClass }}">Tipo de movimiento</label>
                <select name="type" class="{{ $fieldClass }}">
                    <option value="">Todos</option>
                    <option value="sale" {{ $selected($f['type'], 'sale') }}>Ventas</option>
                    <option value="purchase" {{ $selected($f['type'], 'purchase') }}>Compras</option>
                    <option value="expense" {{ $selected($f['type'], 'expense') }}>Gastos</option>
                </select>
            </div>
        @endif

        @if ($withOrder)
            <div>
                <label class="{{ $labelClass }}">Ordenar por</label>
                <select name="order" class="{{ $fieldClass }}">
                    <option value="ganancia" {{ $selected($order, 'ganancia') }}>Mayor ganancia</option>
                    <option value="ventas" {{ $selected($order, 'ventas') }}>Mayor venta</option>
                    <option value="cantidad" {{ $selected($order, 'cantidad') }}>Más unidades vendidas</option>
                    <option value="compras" {{ $selected($order, 'compras') }}>Mayor compra</option>
                </select>
            </div>
        @endif
    </div>

    <div class="mt-4 flex flex-wrap items-center gap-2">
        <x-ui.button type="submit" variant="primary">Aplicar filtros</x-ui.button>
        <a href="{{ $action }}"><x-ui.button type="button" variant="outline">Limpiar</x-ui.button></a>
        <div class="flex flex-wrap gap-1.5 ml-auto text-[11px]">
            <a href="{{ $action }}?from={{ now()->toDateString() }}&to={{ now()->toDateString() }}" class="px-2.5 py-1 rounded-full border border-gray-300 dark:border-slate-600 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-800">Hoy</a>
            <a href="{{ $action }}?from={{ now()->subDays(6)->toDateString() }}&to={{ now()->toDateString() }}" class="px-2.5 py-1 rounded-full border border-gray-300 dark:border-slate-600 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-800">7 días</a>
            <a href="{{ $action }}?from={{ now()->startOfMonth()->toDateString() }}&to={{ now()->toDateString() }}" class="px-2.5 py-1 rounded-full border border-gray-300 dark:border-slate-600 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-800">Este mes</a>
            <a href="{{ $action }}?from={{ now()->startOfYear()->toDateString() }}&to={{ now()->toDateString() }}" class="px-2.5 py-1 rounded-full border border-gray-300 dark:border-slate-600 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-800">Este año</a>
        </div>
    </div>

    @if ($f['customer'] || $f['warehouse'] || $f['product'])
        <p class="mt-3 text-[11px] text-amber-600 dark:text-amber-400">
            @if ($f['customer'])El cliente solo filtra ventas: compras y gastos no se muestran. @endif
            @if ($f['warehouse'] || $f['product'])Los gastos no tienen almacén ni producto, por eso no se muestran con ese filtro (ni la ganancia neta). @endif
        </p>
    @endif
    </form>
</div>

<script>
    // Al elegir sucursal, el selector de almacén solo ofrece los almacenes de esa sucursal.
    (() => {
        const branch = document.getElementById('f-branch'), wh = document.getElementById('f-warehouse');
        const sync = () => {
            [...wh.options].forEach((o) => {
                if (!o.value) return;
                const hide = branch.value && o.dataset.branch !== branch.value;
                o.hidden = hide; o.disabled = hide;
            });
            if (wh.selectedOptions[0]?.disabled) wh.value = '';
        };
        branch.addEventListener('change', sync);
        sync();
    })();

    // Panel de filtros plegable: arranca siempre cerrado (ya lo marca así el HTML) y recuerda
    // si se dejó abierto a propósito la última vez, por pantalla.
    (() => {
        const KEY = 'filters-collapsed:{{ $uid }}';
        const form = document.getElementById('{{ $uid }}-form');
        const icon = document.getElementById('{{ $uid }}-icon');
        const toggle = document.getElementById('{{ $uid }}-toggle');
        if (!form || !toggle) return;

        const apply = (collapsed) => {
            form.classList.toggle('hidden', collapsed);
            icon.classList.toggle('-rotate-90', collapsed);
        };

        let collapsed = true;
        try {
            if (localStorage.getItem(KEY) === '0') collapsed = false;
        } catch (e) {}
        apply(collapsed);

        toggle.addEventListener('click', () => {
            collapsed = !form.classList.contains('hidden');
            apply(collapsed);
            try { localStorage.setItem(KEY, collapsed ? '1' : '0'); } catch (e) {}
        });
    })();
</script>
