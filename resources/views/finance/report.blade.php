<x-app-layout>
    <div class="max-w-10xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        @php
            $buttons = [
                [
                    'route' => route('finance.report.pdf', request()->query()),
                    'can' => 'reportes.exportar',
                    'label' => 'Imprimir / Exportar PDF',
                    'variant' => 'danger',
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />',
                    'target' => '_blank',
                ],
                [
                    'route' => route('finance.report.excel', request()->query()),
                    'can' => 'reportes.exportar',
                    'label' => 'Exportar Excel',
                    'variant' => 'success',
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />',
                    'target' => '_blank',
                ],
            ];
        @endphp

        <x-ui.page-header category="Finanzas" title="Reportes"
            description="Ventas, compras, gastos y ganancia del período, con todos sus movimientos." :buttons="$buttons" />

        @include('finance._filters', ['action' => route('finance.report'), 'withType' => true])

        @include('finance._summary')

        {{-- ── Ventas por usuario ── --}}
        <h2 class="text-sm font-bold text-gray-900 dark:text-white mb-2">Ventas por usuario</h2>
        @php
            $userRows = $byUser->through(fn ($r) => (object) [
                'name' => $r->name,
                'orders' => $r->orders,
                'revenue_label' => number_format((float) $r->revenue, 2),
                'ticket_label' => number_format($r->orders > 0 ? (float) $r->revenue / $r->orders : 0, 2),
                'cost_label' => number_format((float) $r->cost, 2),
                'profit_label' => number_format((float) $r->revenue - (float) $r->cost, 2),
            ]);
        @endphp
        <div class="mb-8">
            <x-ui.data-table :rows="$userRows" :paginator="$userRows" rowKey="name"
                :columns="[
                    ['key' => 'name', 'label' => 'Usuario'],
                    ['key' => 'orders', 'label' => 'N° de ventas'],
                    ['key' => 'revenue_label', 'label' => 'Total vendido'],
                    ['key' => 'ticket_label', 'label' => 'Promedio por venta'],
                    ['key' => 'cost_label', 'label' => 'Costo'],
                    ['key' => 'profit_label', 'label' => 'Ganancia'],
                ]"
                emptyText="Nadie registró ventas en este período." />
        </div>

        {{-- ── Todos los movimientos ── --}}
        <h2 class="text-sm font-bold text-gray-900 dark:text-white mb-2">Movimientos ({{ $movements->total() }})</h2>
        <x-ui.data-table :rows="$movements" :paginator="$movements" rowKey="id"
            :columns="[
                ['key' => 'date_label', 'label' => 'Fecha'],
                ['key' => 'type_label', 'label' => 'Tipo'],
                ['key' => 'number', 'label' => 'N°'],
                ['key' => 'detail', 'label' => 'Detalle'],
                ['key' => 'branch', 'label' => 'Sucursal'],
                ['key' => 'user_name', 'label' => 'Usuario'],
                ['key' => 'amount_label', 'label' => 'Monto'],
            ]"
            :actions="[['type' => 'view', 'url' => fn ($row) => $row->url]]"
            emptyText="No hay movimientos con estos filtros." />
    </div>
</x-app-layout>
