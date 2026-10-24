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

            if (auth()->user()->can('createPedido', \App\Models\Order::class)) {
                $buttons[] = [
                    'route' => route('pedidos.create'),
                    'label' => 'Nuevo Pedido',
                    'variant' => 'primary',
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />',
                ];
            }
        @endphp

        <x-ui.page-header category="Ventas" title="Pedidos"
            description="Pedidos en espera con fecha de entrega. No mueven stock ni caja hasta que los confirmas y pasan a venta." :buttons="$buttons" />

        <div class="mb-5">
            <x-ui.search-box placeholder="Buscar por N° de pedido o cliente..." filterName="buscar" />
        </div>

        @php
            $columns = [
                ['key' => 'id', 'label' => 'N°'],
                ['key' => 'customer_label', 'label' => 'Cliente'],
                ['key' => 'date_label', 'label' => 'Registrado'],
                ['key' => 'delivery_label', 'label' => 'Entrega'],
                ['key' => 'amount_label', 'label' => 'Total'],
                ['key' => 'pedido_status_label', 'label' => 'Estado'],
            ];

            $actions = [
                ['type' => 'view', 'route' => 'pedidos.show'],
                ['type' => 'edit', 'route' => 'pedidos.edit', 'can' => 'updatePedido'],
                ['type' => 'delete', 'route' => 'pedidos.destroy', 'can' => 'deletePedido', 'confirm' => '¿Eliminar este pedido? Solo se pueden eliminar los que están en espera.'],
            ];
        @endphp

        <x-ui.data-table :rows="$pedidos" :columns="$columns" :actions="$actions" :paginator="$pedidos"
            emptyText="No hay pedidos registrados." />
    </div>
</x-app-layout>
