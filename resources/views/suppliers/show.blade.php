@php
    $items = [
        ['label' => 'Nombre / Razón social', 'value' => $supplier->name, 'fullWidth' => true],
        ['label' => 'Documento', 'value' => $supplier->document_label],
        ['label' => 'Estado', 'value' => $supplier->status_label],
        ['label' => 'Persona de contacto', 'value' => $supplier->contact_name ?? '—'],
        ['label' => 'Teléfono', 'value' => $supplier->phone ?? '—'],
        ['label' => 'Correo', 'value' => $supplier->email ?? '—'],
        ['label' => 'Dirección', 'value' => $supplier->address ?? '—'],
        ['label' => 'Ciudad', 'value' => $supplier->city ?? '—'],
        ['label' => 'Compras registradas', 'value' => $supplier->purchases_count],
    ];
@endphp

<x-ui.show-page title="Detalle de Proveedor" category="Inventario" :backRoute="route('suppliers.index')"
    :editRoute="auth()->user()->can('update', $supplier) ? route('suppliers.edit', $supplier) : ''" :items="$items" :columns="2">

    <div class="mt-6">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                Últimas compras ({{ $supplier->purchases->count() }})
            </h3>
            @can('create', \App\Models\Purchase::class)
                <a href="{{ route('purchases.create', ['supplier_id' => $supplier->id]) }}">
                    <x-ui.button type="button" variant="primary">+ Nueva compra a este proveedor</x-ui.button>
                </a>
            @endcan
        </div>

        <div class="overflow-x-auto border border-gray-100 dark:border-gray-800 rounded-lg">
            <table class="w-full text-xs">
                <thead class="bg-gray-50 dark:bg-slate-800/60 text-gray-500 dark:text-gray-400 uppercase">
                    <tr>
                        <th class="px-3 py-2 text-left">N°</th>
                        <th class="px-3 py-2 text-left">Fecha</th>
                        <th class="px-3 py-2 text-left">Factura</th>
                        <th class="px-3 py-2 text-right">Total</th>
                        <th class="px-3 py-2 text-center">Estado</th>
                        <th class="px-3 py-2 text-right">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($supplier->purchases as $purchase)
                        <tr>
                            <td class="px-3 py-2 font-semibold text-emerald-600 dark:text-emerald-400">#{{ $purchase->id }}</td>
                            <td class="px-3 py-2 text-gray-700 dark:text-gray-300">{{ $purchase->date_label }}</td>
                            <td class="px-3 py-2 text-gray-700 dark:text-gray-300">{{ $purchase->invoice_number ?: '—' }}</td>
                            <td class="px-3 py-2 text-right font-mono font-bold text-gray-900 dark:text-white">{{ $purchase->amount_label }}</td>
                            <td class="px-3 py-2 text-center">
                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-gray-100 dark:bg-slate-800 text-gray-700 dark:text-gray-300">{{ $purchase->status_label }}</span>
                            </td>
                            <td class="px-3 py-2 text-right">
                                <a href="{{ route('purchases.show', $purchase) }}" class="text-blue-500 hover:text-blue-700 font-medium">Ver Detalle</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-3 py-6 text-center text-gray-400 italic">Todavía no le compraste a este proveedor.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Estado de cuenta: cada factura a crédito con sus pagos (y el N° de recibo de cada uno) y lo que queda por pagar --}}
    @if ($creditPurchases->isNotEmpty())
        @php $money = fn ($n) => number_format((float) $n, 2); @endphp
        <div class="mt-8">
            <h3 class="text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-3">Estado de cuenta (compras a crédito)</h3>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-4">
                <div class="p-3 rounded-lg border border-gray-200 dark:border-gray-700">
                    <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider">Facturado a crédito</span>
                    <span class="text-lg font-extrabold text-gray-900 dark:text-white">{{ $money($statement['total']) }}</span>
                </div>
                <div class="p-3 rounded-lg border border-gray-200 dark:border-gray-700">
                    <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider">Pagado</span>
                    <span class="text-lg font-extrabold text-emerald-600 dark:text-emerald-400">{{ $money($statement['paid']) }}</span>
                </div>
                <div class="p-3 rounded-lg border border-gray-200 dark:border-gray-700">
                    <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider">Saldo pendiente</span>
                    <span class="text-lg font-extrabold {{ \App\Support\Bc::gt($statement['balance'], 0) ? 'text-amber-600 dark:text-amber-400' : 'text-gray-900 dark:text-white' }}">{{ $money($statement['balance']) }}</span>
                </div>
            </div>

            <div class="overflow-x-auto border border-gray-100 dark:border-gray-800 rounded-lg">
                <table class="w-full text-xs">
                    <thead class="bg-gray-50 dark:bg-slate-800/60 text-gray-500 dark:text-gray-400 uppercase">
                        <tr>
                            <th class="px-3 py-2 text-left">Factura / Recibo</th>
                            <th class="px-3 py-2 text-left">Fecha</th>
                            <th class="px-3 py-2 text-left">Detalle</th>
                            <th class="px-3 py-2 text-right">Cargo</th>
                            <th class="px-3 py-2 text-right">Pago</th>
                            <th class="px-3 py-2 text-right">Saldo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($creditPurchases as $purchase)
                            @php $running = $purchase->amount; @endphp
                            <tr class="bg-gray-50/60 dark:bg-slate-800/30">
                                <td class="px-3 py-2 font-semibold text-gray-900 dark:text-white">
                                    <a href="{{ route('purchases.show', $purchase) }}" class="hover:underline">Factura {{ $purchase->invoice_number ?: 'sin número' }}</a>
                                    <span class="block text-[10px] font-normal text-gray-500">Compra #{{ $purchase->id }}</span>
                                </td>
                                <td class="px-3 py-2 text-gray-700 dark:text-gray-300">{{ $purchase->date_label }}</td>
                                <td class="px-3 py-2 text-gray-700 dark:text-gray-300">Vence {{ $purchase->due_label }} · {{ $purchase->payment_status_label }}</td>
                                <td class="px-3 py-2 text-right font-mono text-gray-900 dark:text-white">{{ $money($purchase->amount) }}</td>
                                <td class="px-3 py-2"></td>
                                <td class="px-3 py-2 text-right font-mono font-semibold text-gray-900 dark:text-white">{{ $money($running) }}</td>
                            </tr>
                            @foreach ($purchase->payments as $payment)
                                @php $running = \App\Support\Bc::round(\App\Support\Bc::sub($running, $payment->amount), 2); @endphp
                                <tr>
                                    <td class="px-3 py-2 pl-6 font-mono text-gray-700 dark:text-gray-300">{{ $payment->receipt_number ? 'Recibo '.$payment->receipt_number : 'Sin N° de recibo' }}</td>
                                    <td class="px-3 py-2 text-gray-700 dark:text-gray-300">{{ $payment->created_at->format('d/m/Y') }}</td>
                                    <td class="px-3 py-2 text-gray-700 dark:text-gray-300">{{ $payment->transaction->paymentMethod->name ?? 'Pago' }}{{ $payment->note ? ' · '.$payment->note : '' }}</td>
                                    <td class="px-3 py-2"></td>
                                    <td class="px-3 py-2 text-right font-mono text-emerald-600 dark:text-emerald-400">{{ $money($payment->amount) }}</td>
                                    <td class="px-3 py-2 text-right font-mono text-gray-700 dark:text-gray-300">{{ $money($running) }}</td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-1.5">Cada pago aparece debajo de su factura con el N° de recibo del proveedor. No se puede pagar más que el saldo de una factura, ni registrar dos veces el mismo recibo o la misma factura.</p>
        </div>
    @endif
</x-ui.show-page>
