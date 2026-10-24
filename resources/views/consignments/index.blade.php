<x-app-layout>
    <div class="max-w-10xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        @if (session('success'))
            <x-ui.alert type="success" :message="session('success')" />
        @endif

        @if (session('error'))
            <x-ui.alert type="error" :message="session('error')" />
        @endif

        @php
            $buttons = [];

            if (auth()->user()->can('create', \App\Models\Consignment::class)) {
                $buttons[] = [
                    'route' => route('consignments.create'),
                    'label' => 'Nueva Consignación',
                    'variant' => 'primary',
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />',
                ];
            }
        @endphp

        <x-ui.page-header category="Ventas" title="Consignaciones"
            description="Mercadería entregada a clientes sin cobrar. Lo que venden se factura como venta; lo que devuelven vuelve al stock." :buttons="$buttons" />

        <div class="mb-5">
            <x-ui.search-box placeholder="Buscar por N° de consignación o cliente..." filterName="buscar" />
        </div>

        @php
            $columns = [
                ['key' => 'id', 'label' => 'N°'],
                ['key' => 'customer.name', 'label' => 'Cliente'],
                ['key' => 'date_label', 'label' => 'Fecha'],
                ['key' => 'due_label', 'label' => 'Límite'],
                ['key' => 'sent_value_label', 'label' => 'Entregado'],
                ['key' => 'sold_value_label', 'label' => 'Vendido'],
                ['key' => 'pending_value_label', 'label' => 'En manos del cliente'],
                ['key' => 'status_label', 'label' => 'Estado'],
            ];

            $actions = [
                ['type' => 'view', 'route' => 'consignments.show'],
            ];
        @endphp

        <x-ui.data-table :rows="$consignments" :columns="$columns" :actions="$actions" :paginator="$consignments"
            emptyText="Todavía no hay consignaciones registradas." />
    </div>
</x-app-layout>
