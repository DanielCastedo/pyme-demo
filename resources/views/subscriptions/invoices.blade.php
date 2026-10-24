<x-app-layout>
    <div class="max-w-10xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <x-ui.page-header category="Configuración" title="Facturas de Suscripción"
            description="Historial de pagos y facturación." />

        @php
            $columns = [
                ['key' => 'invoice_number', 'label' => 'N° Factura'],
                ['key' => 'issue_date', 'label' => 'Fecha'],
                ['key' => 'total', 'label' => 'Total'],
                ['key' => 'currency', 'label' => 'Moneda'],
                ['key' => 'status', 'label' => 'Estado'],
            ];

            $actions = [
                ['type' => 'view', 'route' => 'invoices.show'],
            ];
        @endphp

        <x-ui.data-table :rows="$invoices" :columns="$columns" :actions="$actions" :paginator="$invoices"
            emptyText="Aún no tienes facturas generadas." />
    </div>
</x-app-layout>