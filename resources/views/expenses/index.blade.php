<x-app-layout>
    <div class="max-w-10xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        @include('sales.partials.alerts')

        @php
            $buttons = [];

            if (auth()->user()->can('create', \App\Models\Expense::class)) {
                $buttons[] = [
                    'route' => route('expenses.create'),
                    'label' => 'Nuevo Gasto',
                    'variant' => 'primary',
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />',
                ];
            }
        @endphp

        <x-ui.page-header category="Gastos" title="Gastos"
            description="Lo que gasta el negocio (servicios, alquiler, sueldos, transporte...). Cada gasto sale de una caja." :buttons="$buttons" />

        <div class="mb-3 inline-flex items-baseline gap-2 px-4 py-2 rounded-xl bg-white dark:bg-slate-900 border border-gray-200 dark:border-gray-800">
            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Total gastado (sin anulados)</span>
            <span class="text-lg font-extrabold text-gray-900 dark:text-white">{{ number_format((float) $total, 2) }}</span>
        </div>

        <div class="mb-5">
            <x-ui.search-box placeholder="Buscar por concepto, categoría, proveedor o recibo..." filterName="buscar" />
        </div>

        @php
            $columns = [
                ['key' => 'id', 'label' => 'N°'],
                ['key' => 'date_label', 'label' => 'Fecha'],
                ['key' => 'concept', 'label' => 'Concepto'],
                ['key' => 'category_label', 'label' => 'Categoría'],
                ['key' => 'amount_label', 'label' => 'Monto'],
                ['key' => 'status_label', 'label' => 'Estado'],
            ];

            $actions = [
                ['type' => 'view', 'route' => 'expenses.show'],
                ['type' => 'edit', 'route' => 'expenses.edit', 'condition' => fn ($row) => ! $row->isCanceled()],
                ['type' => 'delete', 'route' => 'expenses.destroy', 'label' => 'Anular', 'confirm' => '¿Anular este gasto? El dinero vuelve a la caja.', 'condition' => fn ($row) => ! $row->isCanceled()],
                ['type' => 'delete', 'route' => 'expenses.destroy', 'confirm' => '¿Eliminar definitivamente este gasto anulado?', 'condition' => fn ($row) => $row->isCanceled()],
            ];
        @endphp

        <x-ui.data-table :rows="$expenses" :columns="$columns" :actions="$actions" :paginator="$expenses"
            emptyText="Todavía no hay gastos registrados." />
    </div>
</x-app-layout>
