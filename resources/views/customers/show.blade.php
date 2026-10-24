@php
    $items = [
        ['label' => 'Nombre / Razón social', 'value' => $customer->name, 'fullWidth' => true],
        ['label' => 'Documento', 'value' => $customer->document_label],
        ['label' => 'Estado', 'value' => $customer->status_label],
        ['label' => 'Teléfono', 'value' => $customer->phone ?? '—'],
        ['label' => 'Correo', 'value' => $customer->email ?? '—'],
        ['label' => 'Dirección', 'value' => $customer->address ?? '—'],
        ['label' => 'Ciudad', 'value' => $customer->city ?? '—'],
        ['label' => 'Órdenes', 'value' => $customer->orders_count],
        ['label' => 'Límite de crédito', 'value' => $customer->credit_limit_label],
        ['label' => 'Deuda pendiente', 'value' => number_format($customer->receivableBalance(), 2)],
    ];
@endphp

<x-ui.show-page title="Detalle de Cliente" category="Ventas" :backRoute="route('customers.index')"
    :editRoute="auth()->user()->can('update', $customer) ? route('customers.edit', $customer) : ''" :items="$items" :columns="2">

    <div class="mt-6">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                Historial de Proformas Emitidas ({{ $customer->quotations->count() }})
            </h3>
            @can('create', \App\Models\Quotation::class)
                <a href="{{ route('quotations.create', ['customer_id' => $customer->id]) }}">
                    <x-ui.button type="button" variant="primary">+ Nueva Proforma para este Cliente</x-ui.button>
                </a>
            @endcan
        </div>

        <div class="overflow-x-auto border border-gray-100 dark:border-gray-800 rounded-lg">
            <table class="w-full text-xs">
                <thead class="bg-gray-50 dark:bg-slate-800/60 text-gray-500 dark:text-gray-400 uppercase">
                    <tr>
                        <th class="px-3 py-2 text-left">N° Proforma</th>
                        <th class="px-3 py-2 text-left">Fecha</th>
                        <th class="px-3 py-2 text-left">Vencimiento</th>
                        <th class="px-3 py-2 text-right">Monto Total</th>
                        <th class="px-3 py-2 text-center">Estado</th>
                        <th class="px-3 py-2 text-right">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($customer->quotations as $quotation)
                        <tr>
                            <td class="px-3 py-2 font-semibold text-emerald-600 dark:text-emerald-400">{{ $quotation->number }}</td>
                            <td class="px-3 py-2 text-gray-700 dark:text-gray-300">{{ $quotation->formatted_date }}</td>
                            <td class="px-3 py-2 text-gray-700 dark:text-gray-300">{{ $quotation->formatted_expires_at }}</td>
                            <td class="px-3 py-2 text-right font-mono font-bold text-gray-900 dark:text-white">{{ $quotation->formatted_amount }}</td>
                            <td class="px-3 py-2 text-center">
                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-gray-100 dark:bg-slate-800 text-gray-700 dark:text-gray-300">{{ $quotation->status_label }}</span>
                            </td>
                            <td class="px-3 py-2 text-right">
                                <a href="{{ route('quotations.show', $quotation) }}" class="text-blue-500 hover:text-blue-700 font-medium">Ver Detalle</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-3 py-6 text-center text-gray-400 italic">Este cliente no tiene proformas registradas aún.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-ui.show-page>
