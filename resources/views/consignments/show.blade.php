@php
    $canCredit = auth()->user()->can('ventas.credito');
    $openItems = $consignment->items->filter(fn ($i) => $i->pending > 0)->values();

    $tone = match (true) {
        $consignment->isCanceled() => 'gray',
        $consignment->isSettled() => 'emerald',
        $consignment->is_overdue => 'red',
        default => 'amber',
    };
    $toneText = ['gray' => 'text-gray-500', 'emerald' => 'text-emerald-600', 'red' => 'text-red-600', 'amber' => 'text-amber-600'];

    $items = [
        ['label' => 'Consignación N°', 'value' => '#'.$consignment->id],
        ['label' => 'Estado', 'value' => $consignment->status_label],
        ['label' => 'Cliente', 'value' => $consignment->customer->name],
        ['label' => 'Fecha de entrega', 'value' => $consignment->date->format('d/m/Y')],
        ['label' => 'Fecha límite', 'value' => $consignment->due_label],
        ['label' => 'Sucursal', 'value' => $consignment->branch->name],
        ['label' => 'Almacén', 'value' => $consignment->warehouse->name],
        ['label' => 'Registrada por', 'value' => $consignment->user->name],
    ];

    if ($consignment->note) {
        $items[] = ['label' => 'Nota', 'value' => $consignment->note, 'fullWidth' => true];
    }
@endphp

<x-ui.show-page title="Consignación #{{ $consignment->id }}" category="Ventas" :backRoute="route('consignments.index')" :pdfRoute="auth()->user()->can('consignaciones.exportar') ? route('consignments.pdf', $consignment) : ''" :items="$items" :columns="3">

    @include('sales.partials.alerts')

    {{-- Resumen --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
        <div class="p-3 rounded-xl bg-white dark:bg-slate-900 border border-gray-200 dark:border-gray-800">
            <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider">Entregado</span>
            <span class="text-lg font-extrabold text-gray-900 dark:text-white">{{ $consignment->sent_value_label }}</span>
        </div>
        <div class="p-3 rounded-xl bg-white dark:bg-slate-900 border border-gray-200 dark:border-gray-800">
            <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider">Vendido (facturado)</span>
            <span class="text-lg font-extrabold text-gray-900 dark:text-white">{{ $consignment->sold_value_label }}</span>
        </div>
        <div class="p-3 rounded-xl bg-white dark:bg-slate-900 border border-gray-200 dark:border-gray-800">
            <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider">Devuelto al stock</span>
            <span class="text-lg font-extrabold text-gray-900 dark:text-white">{{ number_format($consignment->returned_value, 2) }}</span>
        </div>
        <div class="p-3 rounded-xl bg-white dark:bg-slate-900 border {{ $tone === 'red' ? 'border-red-300 dark:border-red-800' : ($tone === 'amber' ? 'border-amber-200 dark:border-amber-800' : 'border-gray-200 dark:border-gray-800') }}">
            <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider">En manos del cliente</span>
            <span class="text-lg font-extrabold {{ $toneText[$tone] }}">{{ $consignment->pending_value_label }}</span>
        </div>
    </div>

    {{-- Productos --}}
    <div class="mb-6">
        <span class="block text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">
            Productos ({{ $consignment->items->count() }})
        </span>
        <div class="overflow-x-auto border border-gray-100 dark:border-gray-800 rounded-lg">
            <table class="w-full text-xs">
                <thead class="bg-gray-50 dark:bg-slate-800/60 text-gray-500 dark:text-gray-400 uppercase">
                    <tr>
                        <th class="px-3 py-2 text-left">Producto</th>
                        <th class="px-3 py-2 text-left">Unidad</th>
                        <th class="px-3 py-2 text-right">Precio</th>
                        <th class="px-3 py-2 text-right">Entregado</th>
                        <th class="px-3 py-2 text-right">Vendido</th>
                        <th class="px-3 py-2 text-right">Devuelto</th>
                        <th class="px-3 py-2 text-right">Pendiente</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach ($consignment->items as $item)
                        <tr>
                            <td class="px-3 py-2 text-gray-800 dark:text-gray-200">{{ $item->product->name }}</td>
                            <td class="px-3 py-2 text-gray-800 dark:text-gray-200">{{ $item->productUnit->name }}</td>
                            <td class="px-3 py-2 text-right text-gray-800 dark:text-gray-200">{{ number_format((float) $item->unit_price, 2) }}</td>
                            <td class="px-3 py-2 text-right text-gray-800 dark:text-gray-200">{{ (float) $item->quantity_sent }}</td>
                            <td class="px-3 py-2 text-right text-gray-800 dark:text-gray-200">{{ (float) $item->quantity_sold }}</td>
                            <td class="px-3 py-2 text-right text-gray-800 dark:text-gray-200">{{ (float) $item->quantity_returned }}</td>
                            <td class="px-3 py-2 text-right font-semibold {{ $item->pending > 0 ? 'text-amber-600' : 'text-gray-400' }}">{{ $item->pending }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Historial de liquidaciones --}}
    <div class="mb-6">
        <span class="block text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">
            Liquidaciones ({{ $consignment->settlements->count() }})
        </span>
        <div class="overflow-x-auto border border-gray-100 dark:border-gray-800 rounded-lg">
            <table class="w-full text-xs">
                <thead class="bg-gray-50 dark:bg-slate-800/60 text-gray-500 dark:text-gray-400 uppercase">
                    <tr>
                        <th class="px-3 py-2 text-left">Fecha</th>
                        <th class="px-3 py-2 text-left">Detalle</th>
                        <th class="px-3 py-2 text-left">Registrada por</th>
                        <th class="px-3 py-2 text-right">Vendido</th>
                        <th class="px-3 py-2 text-left">Venta generada</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($consignment->settlements as $settlement)
                        <tr>
                            <td class="px-3 py-2 text-gray-800 dark:text-gray-200">{{ $settlement->date->format('d/m/Y') }}</td>
                            <td class="px-3 py-2 text-gray-800 dark:text-gray-200">
                                {{ $settlement->items->map(function ($l) {
                                    $parts = [];
                                    if ((float) $l->quantity_sold > 0) $parts[] = (float) $l->quantity_sold.' vendidas';
                                    if ((float) $l->quantity_returned > 0) $parts[] = (float) $l->quantity_returned.' devueltas';
                                    return $l->consignmentItem->product->name.': '.implode(' y ', $parts);
                                })->implode(' · ') }}
                            </td>
                            <td class="px-3 py-2 text-gray-800 dark:text-gray-200">{{ $settlement->user->name }}</td>
                            <td class="px-3 py-2 text-right font-medium text-gray-900 dark:text-white">{{ (float) $settlement->sold_amount > 0 ? number_format((float) $settlement->sold_amount, 2) : '—' }}</td>
                            <td class="px-3 py-2">
                                @if ($settlement->order)
                                    <a href="{{ route('sales.show', $settlement->order) }}" class="text-sky-600 hover:underline font-semibold">Venta #{{ $settlement->order->id }}</a>
                                @else
                                    <span class="text-gray-400">— (solo devolución)</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-3 py-4 text-center text-gray-400">Todavía no se liquidó nada.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Liquidar --}}
    @can('settle', $consignment)
        <div class="mb-6 p-4 rounded-xl border border-emerald-200 dark:border-emerald-800/50 bg-emerald-50/50 dark:bg-emerald-900/10">
            <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-1">Liquidar consignación</h3>
            <p class="text-xs text-gray-600 dark:text-gray-400 mb-4">
                Indica cuánto vendió y cuánto devuelve el cliente. Lo <strong>vendido</strong> se factura como venta (a los precios acordados) y lo
                <strong>devuelto</strong> vuelve al stock de <strong>{{ $consignment->warehouse->name }}</strong>. Puedes liquidar por partes.
            </p>

            <form action="{{ route('consignments.settle', $consignment) }}" method="POST" id="settle-form">
                @csrf

                <div class="overflow-x-auto border border-gray-200 dark:border-gray-700 rounded-lg mb-3 bg-white dark:bg-slate-900">
                    <table class="w-full text-xs">
                        <thead class="bg-gray-50 dark:bg-slate-800/60 text-gray-500 dark:text-gray-400 uppercase">
                            <tr>
                                <th class="px-3 py-2 text-left">Producto</th>
                                <th class="px-3 py-2 text-right">Pendiente</th>
                                <th class="px-3 py-2 text-right">Precio</th>
                                <th class="px-3 py-2 text-left w-28">Vendió</th>
                                <th class="px-3 py-2 text-left w-28">Devuelve</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach ($openItems as $i => $item)
                                <tr data-price="{{ (float) $item->unit_price }}" data-pending="{{ $item->pending }}">
                                    <td class="px-3 py-2 text-gray-800 dark:text-gray-200">{{ $item->product->name }} — {{ $item->productUnit->name }}</td>
                                    <td class="px-3 py-2 text-right font-semibold text-amber-600">{{ $item->pending }}</td>
                                    <td class="px-3 py-2 text-right text-gray-700 dark:text-gray-300">{{ number_format((float) $item->unit_price, 2) }}</td>
                                    <td class="px-3 py-2">
                                        <input type="hidden" name="lines[{{ $i }}][consignment_item_id]" value="{{ $item->id }}">
                                        <input type="number" step="1" min="0" max="{{ (int) floor($item->pending) }}" inputmode="numeric" onkeydown="if (['.', ',', 'e', 'E', '-', '+'].includes(event.key)) event.preventDefault()" name="lines[{{ $i }}][sold]" value="{{ old("lines.$i.sold") }}" placeholder="0"
                                            class="sold-input w-full h-9 px-2 text-sm rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white">
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="number" step="1" min="0" max="{{ (int) floor($item->pending) }}" inputmode="numeric" onkeydown="if (['.', ',', 'e', 'E', '-', '+'].includes(event.key)) event.preventDefault()" name="lines[{{ $i }}][returned]" value="{{ old("lines.$i.returned") }}" placeholder="0"
                                            class="returned-input w-full h-9 px-2 text-sm rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="flex flex-wrap items-center gap-2 mb-4">
                    <button type="button" id="all-sold" class="px-3 h-8 text-xs font-semibold rounded-lg border border-emerald-300 text-emerald-700 hover:bg-emerald-50">Todo vendido</button>
                    <button type="button" id="all-returned" class="px-3 h-8 text-xs font-semibold rounded-lg border border-sky-300 text-sky-700 hover:bg-sky-50">Todo devuelto</button>
                    <button type="button" id="clear-all" class="px-3 h-8 text-xs font-semibold rounded-lg border border-gray-300 text-gray-600 hover:bg-gray-50">Limpiar</button>
                    <span class="ml-auto text-sm text-gray-700 dark:text-gray-300">Total vendido: <strong id="sold-total">0.00</strong></span>
                </div>

                <p id="overflow-warning" class="hidden mb-3 text-xs font-medium text-red-600">Hay una línea donde vendido + devuelto pasa de lo pendiente.</p>

                {{-- Cómo se cobra lo vendido (solo si hay algo vendido) --}}
                <div id="payment-terms" class="hidden grid grid-cols-1 md:grid-cols-4 gap-4 items-end mb-4">
                    @if ($canCredit)
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Lo vendido se cobra</label>
                            <select name="payment_type" id="settle-type"
                                class="w-full h-10 px-3 text-sm rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white">
                                <option value="cash">Al contado</option>
                                <option value="credit">A crédito</option>
                            </select>
                        </div>
                        <div id="settle-credit" class="hidden md:col-span-3 grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Vence el</label>
                                <input type="date" name="due_date" value="{{ now()->addDays(30)->toDateString() }}" disabled
                                    class="w-full h-10 px-3 text-sm rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Anticipo (opcional)</label>
                                <input type="number" step="0.01" min="0" name="initial_payment" placeholder="0.00" disabled
                                    class="w-full h-10 px-3 text-sm rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white">
                            </div>
                        </div>
                    @endif
                    <x-ui.form-field type="select" name="payment_id" label="Método de pago (del cobro o anticipo)"
                        :options="$paymentMethods->pluck('name', 'id')->toArray()" />
                    <x-ui.form-field type="select" name="cashbox_id" label="Caja"
                        :options="$cashboxes->pluck('name', 'id')->toArray()" />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                    <div class="md:col-span-3">
                        <input type="text" name="note" maxlength="1000" placeholder="Nota de la liquidación (opcional)" value="{{ old('note') }}"
                            class="w-full h-10 px-3 text-sm rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white placeholder-gray-400">
                    </div>
                    <x-ui.button type="submit" variant="primary" :fullWidth="true" id="settle-submit">Registrar liquidación</x-ui.button>
                </div>
            </form>
        </div>

        <script>
            (function() {
                const rows = [...document.querySelectorAll('#settle-form tbody tr')];
                const money = (n) => n.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                const num = (el) => parseInt(el.value, 10) || 0;

                function recalc() {
                    let total = 0, overflow = false, anySold = false, anyLine = false;
                    rows.forEach((tr) => {
                        const sold = num(tr.querySelector('.sold-input'));
                        const ret = num(tr.querySelector('.returned-input'));
                        total += sold * parseFloat(tr.dataset.price);
                        if (sold > 0) anySold = true;
                        if (sold + ret > 0) anyLine = true;
                        const bad = sold + ret > parseFloat(tr.dataset.pending) + 0.0001;
                        overflow = overflow || bad;
                        tr.classList.toggle('bg-red-50', bad);
                    });
                    document.getElementById('sold-total').textContent = money(total);
                    document.getElementById('payment-terms').classList.toggle('hidden', !anySold);
                    document.getElementById('overflow-warning').classList.toggle('hidden', !overflow);
                    document.getElementById('settle-submit').disabled = overflow || !anyLine;
                }

                const fill = (soldFn, retFn) => {
                    rows.forEach((tr) => {
                        tr.querySelector('.sold-input').value = soldFn(tr);
                        tr.querySelector('.returned-input').value = retFn(tr);
                    });
                    recalc();
                };
                document.getElementById('all-sold').addEventListener('click', () => fill((tr) => tr.dataset.pending, () => ''));
                document.getElementById('all-returned').addEventListener('click', () => fill(() => '', (tr) => tr.dataset.pending));
                document.getElementById('clear-all').addEventListener('click', () => fill(() => '', () => ''));
                rows.forEach((tr) => tr.querySelectorAll('input[type=number]').forEach((i) => i.addEventListener('input', recalc)));

                const type = document.getElementById('settle-type');
                const credit = document.getElementById('settle-credit');
                type?.addEventListener('change', () => {
                    const on = type.value === 'credit';
                    credit.classList.toggle('hidden', !on);
                    credit.querySelectorAll('input').forEach((i) => { i.disabled = !on; });
                });

                recalc();
            })();
        </script>
    @endcan

    {{-- Acciones --}}
    <div class="flex flex-wrap items-center gap-2">
        @can('create', \App\Models\Consignment::class)
            <a href="{{ route('consignments.create') }}">
                <x-ui.button type="button" variant="primary">Nueva consignación</x-ui.button>
            </a>
        @endcan

        @if ($consignment->settlements->isEmpty())
            @can('cancel', $consignment)
                <form action="{{ route('consignments.cancel', $consignment) }}" method="POST"
                    onsubmit="return confirm('¿Anular esta consignación? La mercadería vuelve al stock, a los mismos lotes de donde salió.')">
                    @csrf
                    <x-ui.button type="submit" variant="outline">Anular consignación</x-ui.button>
                </form>
            @endcan
        @endif
    </div>
</x-ui.show-page>
