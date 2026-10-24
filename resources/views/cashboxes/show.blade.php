@php
    $items = [
        ['label' => 'Caja', 'value' => $cashbox->name],
        ['label' => 'Sucursal', 'value' => $cashbox->branch->name],
        ['label' => 'Efectivo en caja', 'value' => $cashbox->cash_label],
        ['label' => 'Saldo total', 'value' => $cashbox->balance_label],
    ];

    $from = request('from');
    $to = request('to');

    // El signo del tipo (+/-) indica si suma o resta dinero a la caja.
    $net = (float) \App\Support\Bc::sum($totals->map(fn ($row) => match ($row['type']->sign) {
        '+' => $row['total'],
        '-' => \App\Support\Bc::sub(0, $row['total']),
        default => 0,
    }));
@endphp

<x-ui.show-page title="Caja: {{ $cashbox->name }}" category="Finanzas" :backRoute="route('cashboxes.index')"
    :editRoute="auth()->user()->can('update', $cashbox) ? route('cashboxes.edit', $cashbox) : ''" :items="$items" :columns="2">

    <div>
        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3 mb-3">
            <span class="block text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">
                Totales por tipo de transacción (solo vigentes)
            </span>

            <form method="GET" class="flex items-end gap-2">
                <div>
                    <label class="block text-[10px] text-gray-500 mb-0.5">Desde</label>
                    <input type="date" name="from" value="{{ $from }}"
                        class="h-8 px-2 text-xs rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-[10px] text-gray-500 mb-0.5">Hasta</label>
                    <input type="date" name="to" value="{{ $to }}"
                        class="h-8 px-2 text-xs rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white">
                </div>
                <x-ui.button type="submit" variant="outline">Filtrar</x-ui.button>
                @if ($from || $to)
                    <a href="{{ route('cashboxes.show', $cashbox) }}" class="text-xs text-gray-500 hover:underline pb-2">Limpiar</a>
                @endif
            </form>
        </div>

        <div class="flex justify-end mb-3">
            <a href="{{ route('cashboxes.totals.export', array_filter(['cashbox' => $cashbox->id, 'from' => $from, 'to' => $to])) }}"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-medium transition shadow-sm">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Exportar a Excel
            </a>
        </div>

        @error('to')
            <p class="text-xs text-red-500 mb-2">{{ $message }}</p>
        @enderror

        <div class="overflow-x-auto border border-gray-100 dark:border-gray-800 rounded-lg">
            <table class="w-full text-xs">
                <thead class="bg-gray-50 dark:bg-slate-800/60 text-gray-500 dark:text-gray-400 uppercase">
                    <tr>
                        <th class="px-3 py-2 text-left">Tipo</th>
                        <th class="px-3 py-2 text-left">Efecto</th>
                        <th class="px-3 py-2 text-right">Movimientos</th>
                        <th class="px-3 py-2 text-right">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($totals as $row)
                        <tr>
                            <td class="px-3 py-2 text-gray-800 dark:text-gray-200">{{ $row['type']->name }}</td>
                            <td class="px-3 py-2">
                                @if ($row['type']->sign === '+')
                                    <span class="text-emerald-600 font-semibold">Suma (+)</span>
                                @elseif ($row['type']->sign === '-')
                                    <span class="text-rose-600 font-semibold">Resta (−)</span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-right text-gray-800 dark:text-gray-200">{{ $row['count'] }}</td>
                            <td class="px-3 py-2 text-right font-medium text-gray-900 dark:text-white">{{ number_format($row['total'], 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-3 py-4 text-center text-gray-400">Sin transacciones en este período.</td></tr>
                    @endforelse
                </tbody>
                @if ($totals->isNotEmpty())
                    <tfoot class="bg-gray-50 dark:bg-slate-800/60">
                        <tr>
                            <td colspan="3" class="px-3 py-2 text-right font-bold uppercase text-gray-600 dark:text-gray-300">Flujo neto</td>
                            <td class="px-3 py-2 text-right font-bold {{ $net >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">{{ number_format($net, 2) }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
</x-ui.show-page>
