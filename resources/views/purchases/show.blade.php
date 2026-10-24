@php
    $items = [
        ['label' => 'Compra N°', 'value' => '#'.$purchase->id],
        ['label' => 'Estado', 'value' => $purchase->status_label],
        ['label' => 'Proveedor', 'value' => $purchase->supplier_label.($purchase->supplier?->document ? ' ('.$purchase->supplier->document_label.')' : '')],
        ['label' => 'Fecha', 'value' => $purchase->date->format('d/m/Y')],
        ['label' => 'Factura del proveedor', 'value' => $purchase->invoice_number ?: '—'],
        ['label' => 'Sucursal', 'value' => $purchase->branch->name],
        ['label' => $purchase->isDistributed() ? 'Almacenes donde entró' : 'Almacén donde entró', 'value' => $purchase->isDistributed() ? $purchase->receipts->map(fn ($r) => $r->warehouse->name)->implode(', ') : $purchase->warehouse->name],
        ['label' => 'Registrada por', 'value' => $purchase->user->name],
        ['label' => 'Total', 'value' => $purchase->amount_label],
    ];

    if ($purchase->isCredit()) {
        $items[] = ['label' => 'Forma de pago', 'value' => 'A crédito'];
    } elseif ($purchase->transaction) {
        $items[] = ['label' => 'Pago', 'value' => ($purchase->transaction->paymentMethod->name ?? '—').' · '.$purchase->transaction->cashbox->name];
        $items[] = ['label' => 'Transacción', 'value' => $purchase->transaction->number.($purchase->transaction->canceled ? ' (anulada)' : ''), 'mono' => true];
    } else {
        $items[] = ['label' => 'Pago', 'value' => 'No se registró salida de caja'];
    }

    // Pagos de productos agregados después de registrar la compra
    foreach ($purchase->extraTransactions as $extra) {
        $items[] = [
            'label' => 'Pago de productos agregados',
            'value' => number_format((float) $extra->amount, 2).' · '.($extra->paymentMethod->name ?? '—').' · '.$extra->cashbox->name.' · '.$extra->number.($extra->canceled ? ' (anulada)' : ''),
        ];
    }

    if ($purchase->note) {
        $items[] = ['label' => 'Nota', 'value' => $purchase->note, 'fullWidth' => true];
    }

    if ($purchase->isCredit()) {
        $percentPaid = \App\Support\Bc::gt($purchase->amount, 0) ? (float) \App\Support\Bc::min(100, \App\Support\Bc::round(\App\Support\Bc::mul(\App\Support\Bc::div($purchase->paid_amount, $purchase->amount), 100))) : 0;

        $tone = match (true) {
            $purchase->isCanceled() => 'gray',
            $purchase->is_paid => 'emerald',
            $purchase->is_payment_overdue => 'red',
            default => 'amber',
        };
        $toneClasses = [
            'gray' => 'border-gray-200 dark:border-gray-700 text-gray-500',
            'emerald' => 'border-emerald-200 dark:border-emerald-800 text-emerald-600',
            'red' => 'border-red-300 dark:border-red-800 text-red-600',
            'amber' => 'border-amber-200 dark:border-amber-800 text-amber-600',
        ];
        $barClasses = ['gray' => 'bg-gray-400', 'emerald' => 'bg-emerald-500', 'red' => 'bg-red-500', 'amber' => 'bg-amber-500'];
    }
@endphp

{{-- Tabla de productos: en pantallas chicas (< 768px) cada fila se ve como tarjeta, sin scroll horizontal --}}
<style>
    @media (max-width: 767px) {
        .rtable thead { display: none; }
        .rtable, .rtable tbody, .rtable tr, .rtable td { display: block; width: 100%; }
        .rtable tr { padding: 10px 12px; border-bottom: 1px solid rgb(243 244 246); }
        .dark .rtable tr { border-bottom-color: rgb(30 41 59); }
        .rtable td { display: flex !important; justify-content: space-between; align-items: center; gap: 12px; text-align: right !important; padding: 3px 0 !important; border: none !important; }
        .rtable td::before { content: attr(data-label); font-weight: 700; text-transform: uppercase; font-size: 9.5px; letter-spacing: .03em; color: #9ca3af; text-align: left; }
        .rtable td:empty::before { content: none; }
        .rtable tr.rtable-empty td { display: block !important; text-align: center !important; }
        .rtable tr.rtable-empty td::before { content: none; }
    }
</style>

<x-ui.show-page title="Compra #{{ $purchase->id }}" category="Inventario" :backRoute="route('purchases.index')" :editRoute="auth()->user()->can('update', $purchase) ? route('purchases.edit', $purchase) : ''" editText="Agregar productos" :pdfRoute="auth()->user()->can('compras.exportar') ? route('purchases.pdf', $purchase) : ''" :items="$items" :columns="3">

    @include('sales.partials.alerts')

    @if ($purchase->isCredit())
        {{-- Resumen del crédito --}}
        <div class="mb-6">
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-3">
                <div class="p-3 rounded-xl bg-white dark:bg-slate-900 border border-gray-200 dark:border-gray-800">
                    <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider">Total</span>
                    <span class="text-lg font-extrabold text-gray-900 dark:text-white">{{ $purchase->amount_label }}</span>
                </div>
                <div class="p-3 rounded-xl bg-white dark:bg-slate-900 border border-gray-200 dark:border-gray-800">
                    <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider">Pagado</span>
                    <span class="text-lg font-extrabold text-gray-900 dark:text-white">{{ $purchase->paid_label }}</span>
                </div>
                <div class="p-3 rounded-xl bg-white dark:bg-slate-900 border {{ $toneClasses[$tone] }}">
                    <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider">Saldo pendiente</span>
                    <span class="text-lg font-extrabold {{ $toneClasses[$tone] }}">{{ $purchase->balance_label }}</span>
                </div>
                <div class="p-3 rounded-xl bg-white dark:bg-slate-900 border {{ $toneClasses[$tone] }}">
                    <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider">Vence</span>
                    <span class="text-lg font-extrabold {{ $toneClasses[$tone] }}">{{ $purchase->due_label }}</span>
                    <span class="block text-[11px] font-semibold {{ $toneClasses[$tone] }}">{{ $purchase->payment_status_label }}</span>
                </div>
            </div>

            <div class="h-2 rounded-full bg-gray-100 dark:bg-slate-800 overflow-hidden" title="{{ $percentPaid }}% pagado">
                <div class="h-full {{ $barClasses[$tone] }}" style="width: {{ $percentPaid }}%"></div>
            </div>
            <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">{{ $percentPaid }}% pagado</p>
        </div>
    @endif

    <div class="mb-6">
        <span class="block text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">
            Productos ({{ $purchase->items->count() }})
        </span>
        <div class="overflow-x-auto border border-gray-100 dark:border-gray-800 rounded-lg">
            <table class="rtable w-full text-xs">
                <thead class="bg-gray-50 dark:bg-slate-800/60 text-gray-500 dark:text-gray-400 uppercase">
                    <tr>
                        <th class="px-3 py-2 text-left">Producto</th>
                        <th class="px-3 py-2 text-left">Presentación</th>
                        <th class="px-3 py-2 text-left">Destino</th>
                        <th class="px-3 py-2 text-right">Cantidad</th>
                        <th class="px-3 py-2 text-right">Costo</th>
                        <th class="px-3 py-2 text-right">Entra al stock (unid. base)</th>
                        <th class="px-3 py-2 text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach ($purchase->items as $item)
                        <tr>
                            <td class="px-3 py-2 text-gray-800 dark:text-gray-200" data-label="Producto">
                                {{ $item->product->name }}
                                @if ($item->created_at->gt($purchase->created_at->copy()->addMinute()))
                                    <span class="ml-1 text-[10px] font-semibold text-emerald-600">agregado el {{ $item->created_at->format('d/m/Y') }}</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-gray-800 dark:text-gray-200" data-label="Presentación">{{ $item->productUnit->name }}</td>
                            <td class="px-3 py-2 text-gray-800 dark:text-gray-200" data-label="Destino">
                                @forelse ($item->distributions as $d)
                                    <div>{{ $d->warehouse->name }}: <span class="font-semibold">{{ (float) $d->quantity }}</span></div>
                                @empty
                                    {{ $purchase->warehouse->name }}
                                @endforelse
                            </td>
                            <td class="px-3 py-2 text-right text-gray-800 dark:text-gray-200" data-label="Cantidad">{{ (float) $item->quantity }}</td>
                            <td class="px-3 py-2 text-right text-gray-800 dark:text-gray-200" data-label="Costo">{{ number_format((float) $item->unit_cost, 2) }}</td>
                            <td class="px-3 py-2 text-right text-gray-800 dark:text-gray-200" data-label="Entra al stock">{{ (float) $item->quantity * (float) $item->conversion_factor }}</td>
                            <td class="px-3 py-2 text-right font-semibold text-gray-900 dark:text-white" data-label="Subtotal">{{ number_format((float) $item->subtotal, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @if ($purchase->isCredit())
        @include('purchases.partials.payments-table')

        @can('pay', $purchase)
            <div class="mb-6 p-4 rounded-xl border border-amber-200 dark:border-amber-800/50 bg-amber-50/50 dark:bg-amber-900/10">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-1">Registrar pago</h3>
                <p class="text-xs text-gray-600 dark:text-gray-400 mb-4">
                    Saldo pendiente: <strong>{{ $purchase->balance_label }}</strong> · Vence: <strong>{{ $purchase->due_label }}</strong>
                </p>

                <form action="{{ route('purchases.payments.store', $purchase) }}" method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                    @csrf
                    <x-ui.form-field type="number" name="amount" label="Monto a pagar" required :value="old('amount', $purchase->balance)" />
                    <x-ui.form-field type="select" name="payment_id" label="Método de pago" required
                        :options="$paymentMethods->pluck('name', 'id')->toArray()" />
                    <x-ui.form-field type="select" name="cashbox_id" label="Caja" required
                        :options="$cashboxes->pluck('name', 'id')->toArray()" />
                    <x-ui.button type="submit" variant="primary" :fullWidth="true">Registrar pago</x-ui.button>
                </form>
            </div>
        @endcan
    @endif

    <div class="flex flex-wrap items-center gap-3 mt-4">
        @can('cancel', $purchase)
            <form action="{{ route('purchases.cancel', $purchase) }}" method="POST"
                onsubmit="return confirm('¿Anular esta compra? La mercadería sale del inventario y el dinero pagado vuelve a la caja. Solo se puede si nada de esa mercadería se vendió.')">
                @csrf
                <x-ui.button type="submit" variant="outline">Anular compra</x-ui.button>
            </form>
        @endcan
    </div>
</x-ui.show-page>
