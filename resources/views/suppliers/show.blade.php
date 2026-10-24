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
</x-ui.show-page>
