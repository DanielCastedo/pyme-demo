@php
    $money = fn ($n) => number_format((float) $n, 2);
    $session = $cashSession;

    $items = [
        ['label' => 'Usuario', 'value' => $session->user->name],
        ['label' => 'Caja', 'value' => $session->cashbox->name.' · '.$session->cashbox->branch->name],
        ['label' => 'Estado', 'value' => $session->status_label],
        ['label' => 'Apertura', 'value' => $session->opened_at->format('d/m/Y H:i')],
        ['label' => 'Cierre', 'value' => $session->closed_at ? $session->closed_at->format('d/m/Y H:i').($session->closer && $session->closer->id !== $session->user_id ? ' (por '.$session->closer->name.')' : '') : '—'],
        ['label' => 'Fondo inicial (para dar cambio)', 'value' => $money($session->opening_amount)],
        ['label' => 'Ventas en efectivo', 'value' => $money($summary['cash_in'])],
        ['label' => 'Cobros con otros métodos', 'value' => $money($summary['other_in'])],
        ['label' => 'Salidas en efectivo', 'value' => $money($summary['cash_out'])],
        ['label' => 'Efectivo esperado', 'value' => $money($session->expected_cash ?? $summary['expected_cash'])],
        ['label' => 'Efectivo contado', 'value' => $session->counted_cash === null ? '—' : $money($session->counted_cash)],
        ['label' => 'Diferencia', 'value' => $session->difference_label],
    ];

    if ($session->opening_note) {
        $items[] = ['label' => 'Nota de apertura', 'value' => $session->opening_note, 'fullWidth' => true];
    }
    if ($session->closing_note) {
        $items[] = ['label' => 'Nota de cierre', 'value' => $session->closing_note, 'fullWidth' => true];
    }
@endphp

<x-ui.show-page title="Caja de {{ $session->user->name }} — {{ $session->opened_at->format('d/m/Y') }}" category="Ventas"
    :backRoute="route('cash-sessions.index')" :items="$items" :columns="3">

    @include('sales.partials.alerts')

    <p class="mb-4 text-xs text-gray-600 dark:text-gray-400">
        Efectivo esperado = fondo inicial + ventas en efectivo − salidas en efectivo. El fondo no se cuenta como venta:
        total cobrado en el turno (sin el fondo) <strong>{{ $money($summary['sales_total']) }}</strong>@if ($summary['by_method']) —
            @foreach ($summary['by_method'] as $method => $amount){{ $method }} {{ $money($amount) }}@if (! $loop->last) · @endif @endforeach
        @endif.
    </p>

    @if ($session->isOpen())
        <form action="{{ route('cash-sessions.close', $session) }}" method="POST" class="mb-6 grid grid-cols-1 md:grid-cols-4 gap-4 items-end p-4 rounded-xl border border-amber-200 dark:border-amber-800/50 bg-amber-50/50 dark:bg-amber-900/10"
            onsubmit="return confirm('¿Cerrar esta caja?')">
            @csrf
            <x-ui.form-field type="number" name="counted_cash" label="Efectivo contado" required :value="old('counted_cash')" />
            <div class="md:col-span-2">
                <x-ui.form-field type="text" name="closing_note" label="Nota de cierre (opcional)" :value="old('closing_note')" />
            </div>
            <x-ui.button type="submit" variant="primary" :fullWidth="true">Cerrar caja</x-ui.button>
        </form>
    @endif

    <span class="block text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">Movimientos del turno ({{ $transactions->count() }})</span>
    <div class="overflow-x-auto border border-gray-100 dark:border-gray-800 rounded-lg">
        <table class="w-full text-xs">
            <thead class="bg-gray-50 dark:bg-slate-800/60 text-gray-500 dark:text-gray-400 uppercase">
                <tr>
                    <th class="px-3 py-2 text-left">Fecha</th>
                    <th class="px-3 py-2 text-left">Transacción</th>
                    <th class="px-3 py-2 text-left">Tipo</th>
                    <th class="px-3 py-2 text-left">Método</th>
                    <th class="px-3 py-2 text-left">Detalle</th>
                    <th class="px-3 py-2 text-right">Monto</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($transactions as $t)
                    <tr class="{{ $t->canceled ? 'opacity-50 line-through' : '' }}">
                        <td class="px-3 py-2 text-gray-800 dark:text-gray-200">{{ $t->date->format('d/m/Y H:i') }}</td>
                        <td class="px-3 py-2 font-mono text-gray-800 dark:text-gray-200">{{ $t->number }}</td>
                        <td class="px-3 py-2 text-gray-800 dark:text-gray-200">{{ $t->type->name }}</td>
                        <td class="px-3 py-2 text-gray-800 dark:text-gray-200">{{ $t->paymentMethod->name ?? '—' }}</td>
                        <td class="px-3 py-2 text-gray-800 dark:text-gray-200">{{ $t->memo }}</td>
                        <td class="px-3 py-2 text-right font-mono font-semibold {{ $t->type->sign === '+' ? 'text-emerald-600' : 'text-red-600' }}">{{ $t->type->sign }}{{ $money($t->amount) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-3 py-6 text-center text-gray-400 italic">Todavía no hay movimientos en este turno.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-ui.show-page>
