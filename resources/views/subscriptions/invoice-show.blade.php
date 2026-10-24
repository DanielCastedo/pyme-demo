@php
    $statusLabels = [
        'draft' => 'Borrador', 'issued' => 'Emitida', 'paid' => 'Pagada',
        'overdue' => 'Vencida', 'canceled' => 'Cancelada',
    ];

    $items = [
        ['label' => 'N° Factura', 'value' => $invoice->invoice_number, 'mono' => true],
        ['label' => 'Empresa', 'value' => $invoice->company->name],
        ['label' => 'Fecha de Emisión', 'value' => $invoice->issue_date->format('d/m/Y')],
        ['label' => 'Fecha de Vencimiento', 'value' => optional($invoice->due_date)->format('d/m/Y') ?? '—'],
        ['label' => 'Estado', 'value' => $statusLabels[$invoice->status] ?? $invoice->status],
        ['label' => 'Plan', 'value' => $invoice->subscription?->plan?->name ?? '—'],
        ['label' => 'Subtotal', 'value' => number_format($invoice->subtotal, 2) . ' ' . $invoice->currency],
        ['label' => 'Impuesto', 'value' => number_format($invoice->tax, 2) . ' ' . $invoice->currency],
        ['label' => 'Total', 'value' => number_format($invoice->total, 2) . ' ' . $invoice->currency, 'fullWidth' => true],
    ];
@endphp

<x-ui.show-page
    title="Factura {{ $invoice->invoice_number }}"
    category="Suscripciones"
    :backRoute="route('subscriptions.invoices')"
    :items="$items"
    :columns="2"
>
    <div>
        <span class="block text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">Detalle</span>
        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-slate-800/60">
                    <tr>
                        <th class="text-left px-3 py-2 font-semibold text-gray-600 dark:text-gray-300">Descripción</th>
                        <th class="text-center px-3 py-2 font-semibold text-gray-600 dark:text-gray-300">Cant.</th>
                        <th class="text-right px-3 py-2 font-semibold text-gray-600 dark:text-gray-300">P. Unitario</th>
                        <th class="text-right px-3 py-2 font-semibold text-gray-600 dark:text-gray-300">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach ($invoice->items as $item)
                        <tr>
                            <td class="px-3 py-2 text-gray-900 dark:text-white">{{ $item->description }}</td>
                            <td class="px-3 py-2 text-center text-gray-700 dark:text-gray-300">{{ $item->quantity }}</td>
                            <td class="px-3 py-2 text-right text-gray-700 dark:text-gray-300">{{ number_format($item->unit_price, 2) }}</td>
                            <td class="px-3 py-2 text-right font-medium text-gray-900 dark:text-white">{{ number_format($item->subtotal, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($invoice->payment)
            <div class="mt-4 p-3 bg-gray-50 dark:bg-slate-800/40 rounded-lg border border-gray-100 dark:border-gray-800/60">
                <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Pago asociado</span>
                <span class="text-sm text-gray-700 dark:text-gray-300">
                    {{ ucfirst($invoice->payment->method) }} — {{ $invoice->payment->reference ?? 'Sin referencia' }}
                    ({{ optional($invoice->payment->paid_at)->format('d/m/Y') }})
                </span>
            </div>
        @endif
    </div>
</x-ui.show-page>