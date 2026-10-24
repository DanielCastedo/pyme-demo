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

            if (auth()->user()->can('create', \App\Models\Order::class)) {
                $buttons[] = [
                    'route' => route('sales.create'),
                    'label' => 'Nueva Venta',
                    'variant' => 'primary',
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />',
                ];
            }
        @endphp

        <x-ui.page-header category="Ventas" title="Ventas"
            description="Cada venta descuenta stock (FIFO) y suma el cobro a la caja." :buttons="$buttons" />

        <x-ui.filter-bar :action="route('sales.index')" :resetRoute="route('sales.index')" :collapsible="true" :fields="[
            ['type' => 'date', 'name' => 'from', 'label' => 'Desde', 'value' => $f['from']],
            ['type' => 'date', 'name' => 'to', 'label' => 'Hasta', 'value' => $f['to']],
            ['type' => 'select', 'name' => 'branch', 'label' => 'Sucursal', 'value' => $f['branch'], 'options' => ['' => 'Todas las sucursales'] + $branches->toArray()],
            ['type' => 'select', 'name' => 'customer', 'label' => 'Cliente', 'value' => $f['customer'], 'options' => ['' => 'Todos los clientes'] + $customers->toArray()],
            ['type' => 'select', 'name' => 'user', 'label' => 'Usuario', 'value' => $f['user'], 'options' => ['' => 'Todos los usuarios'] + $users->toArray()],
        ]" />

        @php
            $columns = [
                ['key' => 'id', 'label' => 'N°'],
                ['key' => 'date_label', 'label' => 'Fecha'],
                ['key' => 'customer_label', 'label' => 'Cliente'],
                ['key' => 'user.name', 'label' => 'Usuario'],
                ['key' => 'payment_label', 'label' => 'Pago'],
                ['key' => 'branch.name', 'label' => 'Sucursal'],
                ['key' => 'amount_label', 'label' => 'Total'],
                ['key' => 'balance_label', 'label' => 'Saldo'],
                ['key' => 'payment_status_label', 'label' => 'Cobro'],
            ];

            $actions = [
                ['type' => 'view', 'route' => 'sales.show'],
            ];
        @endphp

        <x-ui.data-table :rows="$sales" :columns="$columns" :actions="$actions" :paginator="$sales"
            emptyText="Todavía no hay ventas registradas." />
    </div>
</x-app-layout>
