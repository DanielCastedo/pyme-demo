<x-app-layout>
    @php
        $field = 'w-full h-10 px-3 text-sm rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white';
        $label = 'block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5';
        $money = fn ($n) => number_format((float) $n, 2);
        $tile = 'p-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-slate-900';
        $tileLabel = 'block text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider';
    @endphp

    <div class="max-w-10xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        @include('sales.partials.alerts')

        <x-ui.page-header category="Ventas" title="Mi caja"
            description="Abre tu caja con el fondo para dar cambio al empezar y ciérrala contando el efectivo al terminar." />

        @if ($current)
            {{-- Caja abierta: resumen del turno y cierre --}}
            <div class="mb-6 p-4 rounded-xl border border-emerald-200 dark:border-emerald-800/50 bg-emerald-50/50 dark:bg-emerald-900/10">
                <div class="flex flex-wrap items-baseline justify-between gap-2 mb-4">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white">
                        Caja abierta: {{ $current->cashbox->name }}
                        <span class="font-normal text-gray-500 dark:text-gray-400">· {{ $current->cashbox->branch->name }} · desde {{ $current->opened_at->format('d/m/Y H:i') }}</span>
                    </h3>
                    <a href="{{ route('cash-sessions.show', $current) }}" class="text-xs font-semibold text-emerald-700 dark:text-emerald-400 hover:underline">Ver movimientos del turno</a>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-4">
                    <div class="{{ $tile }}">
                        <span class="{{ $tileLabel }}">Fondo inicial</span>
                        <span class="text-lg font-extrabold text-gray-900 dark:text-white">{{ $money($summary['opening']) }}</span>
                        <span class="block text-[10px] text-gray-500">para dar cambio · no es venta</span>
                    </div>
                    <div class="{{ $tile }}">
                        <span class="{{ $tileLabel }}">Ventas en efectivo</span>
                        <span class="text-lg font-extrabold text-emerald-600 dark:text-emerald-400">{{ $money($summary['cash_in']) }}</span>
                    </div>
                    <div class="{{ $tile }}">
                        <span class="{{ $tileLabel }}">Cobros con otros métodos</span>
                        <span class="text-lg font-extrabold text-sky-600 dark:text-sky-400">{{ $money($summary['other_in']) }}</span>
                        <span class="block text-[10px] text-gray-500">QR, tarjeta, transferencia</span>
                    </div>
                    <div class="{{ $tile }}">
                        <span class="{{ $tileLabel }}">Salidas en efectivo</span>
                        <span class="text-lg font-extrabold text-red-600 dark:text-red-400">{{ $money($summary['cash_out']) }}</span>
                    </div>
                    <div class="{{ $tile }} ring-2 ring-emerald-500/40">
                        <span class="{{ $tileLabel }}">Efectivo esperado</span>
                        <span class="text-lg font-extrabold text-gray-900 dark:text-white">{{ $money($summary['expected_cash']) }}</span>
                        <span class="block text-[10px] text-gray-500">fondo + ventas en efectivo − salidas</span>
                    </div>
                </div>

                @if ($summary['by_method'])
                    <p class="mb-4 text-xs text-gray-600 dark:text-gray-400">
                        Cobrado por método:
                        @foreach ($summary['by_method'] as $method => $amount)
                            <span class="inline-block mr-3"><strong>{{ $method }}</strong> {{ $money($amount) }}</span>
                        @endforeach
                        · Total cobrado (sin el fondo): <strong>{{ $money($summary['sales_total']) }}</strong>
                    </p>
                @endif

                <form action="{{ route('cash-sessions.close', $current) }}" method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end"
                    onsubmit="return confirm('¿Cerrar la caja? Ya no podrás cobrar en este turno.')">
                    @csrf
                    <div>
                        <label class="{{ $label }}">Efectivo contado <span class="text-red-500">*</span></label>
                        <input type="number" step="0.01" min="0" name="counted_cash" id="counted-cash" required value="{{ old('counted_cash') }}" placeholder="0.00" class="{{ $field }}">
                        <p id="close-difference" class="mt-1 text-xs font-semibold"></p>
                    </div>
                    <div class="md:col-span-2">
                        <label class="{{ $label }}">Nota de cierre (opcional)</label>
                        <input type="text" name="closing_note" maxlength="255" value="{{ old('closing_note') }}" placeholder="Ej. faltan 2 Bs de cambio" class="{{ $field }}">
                    </div>
                    <x-ui.button type="submit" variant="primary" :fullWidth="true">Cerrar caja</x-ui.button>
                </form>

                <script>
                    (function () {
                        // Mientras escribe lo contado, muestra si cuadra con lo esperado.
                        const expected = {{ (float) $summary['expected_cash'] }};
                        const input = document.getElementById('counted-cash');
                        const out = document.getElementById('close-difference');
                        const money = (n) => Number(n).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                        const paint = () => {
                            if (input.value === '') { out.textContent = ''; return; }
                            const diff = Math.round((Number(input.value) - expected) * 100) / 100;
                            out.textContent = diff === 0 ? 'Cuadra con lo esperado' : (diff > 0 ? `Sobrante ${money(diff)}` : `Faltante ${money(-diff)}`);
                            out.className = 'mt-1 text-xs font-semibold ' + (diff === 0 ? 'text-emerald-600' : 'text-red-600');
                        };
                        input.addEventListener('input', paint);
                        paint();
                    })();
                </script>
            </div>
        @else
            {{-- Sin caja abierta: apertura con el fondo inicial --}}
            <div class="mb-6 p-4 rounded-xl border border-amber-200 dark:border-amber-800/50 bg-amber-50/50 dark:bg-amber-900/10">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-1">Abrir caja</h3>
                <p class="text-xs text-gray-600 dark:text-gray-400 mb-4">
                    El fondo inicial es efectivo de la caja que recibes para dar cambio: <strong>no es una venta</strong>, no cambia el saldo
                    y sigue siendo parte de la caja. Al cerrar se cuenta junto con lo vendido en efectivo.
                </p>

                @if ($cashboxes->isEmpty())
                    <p class="text-xs font-medium text-red-600">No hay cajas registradas. Pide a un administrador que cree una en Finanzas → Cajas.</p>
                @else
                    <form action="{{ route('cash-sessions.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                        @csrf
                        <div>
                            <label class="{{ $label }}">Caja <span class="text-red-500">*</span></label>
                            <select name="cashbox_id" class="{{ $field }}">
                                @foreach ($cashboxes as $box)
                                    <option value="{{ $box->id }}" @selected(old('cashbox_id') == $box->id)>{{ $box->name }} (efectivo {{ $money($box->cash) }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="{{ $label }}">Fondo inicial <span class="text-red-500">*</span></label>
                            <input type="number" step="0.01" min="0" name="opening_amount" required value="{{ old('opening_amount', $defaultOpening) }}" class="{{ $field }}">
                        </div>
                        <div>
                            <label class="{{ $label }}">Nota (opcional)</label>
                            <input type="text" name="opening_note" maxlength="255" value="{{ old('opening_note') }}" placeholder="Ej. 5 billetes de 20" class="{{ $field }}">
                        </div>
                        <x-ui.button type="submit" variant="primary" :fullWidth="true">Abrir caja</x-ui.button>
                    </form>
                @endif
            </div>
        @endif

        {{-- Historial de turnos --}}
        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3 mb-3">
            <h3 class="text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                {{ $canSeeAll ? 'Aperturas y cierres de caja' : 'Mis aperturas y cierres' }}
            </h3>
            <form method="GET" class="flex flex-wrap items-end gap-2">
                @if ($canSeeAll)
                    <select name="user" class="h-8 px-2 text-xs rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white">
                        <option value="">Todos los usuarios</option>
                        @foreach ($users as $u)
                            <option value="{{ $u->id }}" @selected(request('user') == $u->id)>{{ $u->name }}</option>
                        @endforeach
                    </select>
                @endif
                <select name="cashbox" class="h-8 px-2 text-xs rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white">
                    <option value="">Todas las cajas</option>
                    @foreach ($allCashboxes as $box)
                        <option value="{{ $box->id }}" @selected(request('cashbox') == $box->id)>{{ $box->name }}</option>
                    @endforeach
                </select>
                <input type="date" name="from" value="{{ request('from') }}" class="h-8 px-2 text-xs rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white">
                <input type="date" name="to" value="{{ request('to') }}" class="h-8 px-2 text-xs rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white">
                <x-ui.button type="submit" variant="outline">Filtrar</x-ui.button>
            </form>
        </div>

        <div class="overflow-x-auto border border-gray-100 dark:border-gray-800 rounded-lg">
            <table class="w-full text-xs">
                <thead class="bg-gray-50 dark:bg-slate-800/60 text-gray-500 dark:text-gray-400 uppercase">
                    <tr>
                        <th class="px-3 py-2 text-left">Apertura</th>
                        <th class="px-3 py-2 text-left">Cierre</th>
                        @if ($canSeeAll)
                            <th class="px-3 py-2 text-left">Usuario</th>
                        @endif
                        <th class="px-3 py-2 text-left">Caja</th>
                        <th class="px-3 py-2 text-right">Fondo</th>
                        <th class="px-3 py-2 text-right">Esperado</th>
                        <th class="px-3 py-2 text-right">Contado</th>
                        <th class="px-3 py-2 text-left">Diferencia</th>
                        <th class="px-3 py-2 text-right"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($history as $session)
                        @php $diff = $session->difference === null ? null : \App\Support\Bc::cmp($session->difference, 0); @endphp
                        <tr>
                            <td class="px-3 py-2 text-gray-800 dark:text-gray-200">{{ $session->opened_at->format('d/m/Y H:i') }}</td>
                            <td class="px-3 py-2 text-gray-800 dark:text-gray-200">
                                @if ($session->isOpen())
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">Abierta</span>
                                @else
                                    {{ $session->closed_at->format('d/m/Y H:i') }}
                                @endif
                            </td>
                            @if ($canSeeAll)
                                <td class="px-3 py-2 text-gray-800 dark:text-gray-200">{{ $session->user->name }}</td>
                            @endif
                            <td class="px-3 py-2 text-gray-800 dark:text-gray-200">{{ $session->cashbox->name }}</td>
                            <td class="px-3 py-2 text-right font-mono text-gray-800 dark:text-gray-200">{{ $money($session->opening_amount) }}</td>
                            <td class="px-3 py-2 text-right font-mono text-gray-800 dark:text-gray-200">{{ $session->expected_cash === null ? '—' : $money($session->expected_cash) }}</td>
                            <td class="px-3 py-2 text-right font-mono text-gray-800 dark:text-gray-200">{{ $session->counted_cash === null ? '—' : $money($session->counted_cash) }}</td>
                            <td class="px-3 py-2 font-semibold {{ $diff === null ? 'text-gray-400' : ($diff === 0 ? 'text-emerald-600' : 'text-red-600') }}">{{ $session->difference_label }}</td>
                            <td class="px-3 py-2 text-right">
                                <a href="{{ route('cash-sessions.show', $session) }}" class="text-blue-500 hover:text-blue-700 font-medium">Ver</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="px-3 py-6 text-center text-gray-400 italic">Todavía no hay aperturas de caja.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $history->links() }}</div>
    </div>
</x-app-layout>
