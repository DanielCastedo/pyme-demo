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

            if (auth()->user()->can('create', \App\Models\Purchase::class)) {
                $buttons[] = [
                    'route' => route('purchases.create'),
                    'label' => 'Nueva Compra',
                    'variant' => 'primary',
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />',
                ];
            }
        @endphp

        <x-ui.page-header category="Inventario" title="Compras"
            description="Mercadería que compras a tus proveedores. Al registrar una compra el stock sube; al anularla, baja." :buttons="$buttons" />

        <div class="mb-5">
            <x-ui.search-box placeholder="Buscar por N° de compra, proveedor o factura..." filterName="buscar" />
        </div>

        @php
            $columns = [
                ['key' => 'id', 'label' => 'N°'],
                ['key' => 'date_label', 'label' => 'Fecha'],
                ['key' => 'supplier_label', 'label' => 'Proveedor'],
                ['key' => 'invoice_number', 'label' => 'Factura'],
                ['key' => 'warehouse_label', 'label' => 'Almacén'],
                ['key' => 'amount_label', 'label' => 'Total'],
                ['key' => 'status_label', 'label' => 'Estado'],
            ];

            $actions = [
                ['type' => 'view', 'route' => 'purchases.show'],
                ['type' => 'edit', 'route' => 'purchases.edit', 'label' => 'Agregar productos'],
            ];
        @endphp

        <x-ui.data-table :rows="$purchases" :columns="$columns" :actions="$actions" :paginator="$purchases"
            emptyText="Todavía no hay compras registradas." />
    </div>
</x-app-layout>
