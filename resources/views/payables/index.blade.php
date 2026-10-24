<x-app-layout>
    <div class="max-w-10xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <x-ui.page-header category="Compras" title="Cuentas por pagar"
            description="Compras a crédito con saldo pendiente. Entra a cada una para registrar pagos." />

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-5">
            <div class="p-4 rounded-xl bg-white dark:bg-slate-900 border border-gray-200 dark:border-gray-800">
                <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider">Total por pagar</span>
                <span class="text-2xl font-extrabold text-gray-900 dark:text-white">{{ number_format($totalOwed, 2) }}</span>
            </div>
            <div class="p-4 rounded-xl bg-white dark:bg-slate-900 border {{ $totalOverdue > 0 ? 'border-red-300 dark:border-red-800' : 'border-gray-200 dark:border-gray-800' }}">
                <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider">De eso, vencido</span>
                <span class="text-2xl font-extrabold {{ $totalOverdue > 0 ? 'text-red-600' : 'text-gray-900 dark:text-white' }}">{{ number_format($totalOverdue, 2) }}</span>
            </div>
        </div>

        <div class="mb-5 flex flex-col sm:flex-row sm:items-center gap-3">
            <div class="flex-1">
                <x-ui.search-box placeholder="Buscar por N° de compra o proveedor..." filterName="buscar" />
            </div>
            @if (request('filter.vencidas'))
                <a href="{{ route('payables.index', request()->except('filter.vencidas', 'page')) }}">
                    <x-ui.button type="button" variant="outline">Ver todas</x-ui.button>
                </a>
            @else
                <a href="{{ route('payables.index', array_merge(request()->except('page'), ['filter' => array_merge((array) request('filter'), ['vencidas' => 1])])) }}">
                    <x-ui.button type="button" variant="outline">Solo vencidas</x-ui.button>
                </a>
            @endif
        </div>

        @php
            $columns = [
                ['key' => 'id', 'label' => 'Compra'],
                ['key' => 'supplier_label', 'label' => 'Proveedor'],
                ['key' => 'date_label', 'label' => 'Fecha'],
                ['key' => 'due_label', 'label' => 'Vence'],
                ['key' => 'amount_label', 'label' => 'Total'],
                ['key' => 'paid_label', 'label' => 'Pagado'],
                ['key' => 'balance_label', 'label' => 'Saldo'],
                ['key' => 'payment_status_label', 'label' => 'Estado'],
            ];

            $actions = [
                ['type' => 'view', 'route' => 'purchases.show'],
            ];
        @endphp

        <x-ui.data-table :rows="$payables" :columns="$columns" :actions="$actions" :paginator="$payables"
            emptyText="No hay cuentas por pagar. ¡Todo está al día!" />
    </div>
</x-app-layout>
