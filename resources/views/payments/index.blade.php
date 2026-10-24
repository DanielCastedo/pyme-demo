<x-app-layout>
    <div class="max-w-10xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        @if (session('success'))
            <x-ui.alert type="success" :message="session('success')" />
        @endif

        @if ($errors->any())
            <x-ui.alert type="error">
                <ul class="list-disc pl-4 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-ui.alert>
        @endif

        @php
            $buttons = [
                [
                    'id' => 'modal-toggle-button',
                    'can' => 'crear payments',
                    'label' => 'Nuevo Método de Pago',
                    'variant' => 'primary',
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />',
                ],
            ];
        @endphp

        <x-ui.page-header category="Ventas" title="Métodos de Pago"
            description="Catálogo de cuentas/métodos de cobro usados en las transacciones." :buttons="$buttons" />

        <div class="mb-5">
            <x-ui.search-box placeholder="Buscar por nombre..." filterName="buscar" />
        </div>

        @php
            $columns = [
                ['key' => 'name', 'label' => 'Nombre'],
                ['key' => 'number', 'label' => 'N° de Cuenta / Celular'],
                ['key' => 'cash_label', 'label' => 'Tipo'],
            ];

            $actions = [
                ['type' => 'view', 'route' => 'payments.show'],
                ['type' => 'edit', 'route' => 'payments.edit'],
                ['type' => 'delete', 'route' => 'payments.destroy', 'confirm' => '¿Eliminar este método de pago?'],
            ];
        @endphp

        <x-ui.data-table :rows="$paymentMethods" :columns="$columns" :actions="$actions" :paginator="$paymentMethods"
            emptyText="No hay métodos de pago registrados." />

        <x-ui.create-modal id="crud-modal" title="Registrar Nuevo Método de Pago" :action="route('payments.store')"
            submitText="Guardar" :columns="2" :hasFile="true" :fields="[
                [
                    'type' => 'text',
                    'name' => 'name',
                    'label' => 'Nombre',
                    'placeholder' => 'Ej. Transferencia Bancaria',
                    'required' => true,
                ],
                [
                    'type' => 'text',
                    'name' => 'number',
                    'label' => 'N° de Cuenta / Celular',
                    'placeholder' => 'Opcional',
                ],
                [
                    'type' => 'checkbox',
                    'name' => 'is_cash',
                    'label' => 'Es efectivo (suma al efectivo físico de la caja)',
                    'value' => false,
                    'fullWidth' => true,
                ],
                [
                    'type' => 'textarea',
                    'name' => 'note',
                    'label' => 'Nota / Instrucciones',
                    'placeholder' => 'Ej. Banco Mercantil Santa Cruz, Cuenta Corriente.',
                    'fullWidth' => true,
                    'rows' => 3,
                ],
                [
                    'type' => 'file',
                    'name' => 'photo',
                    'label' => 'Imagen QR / Comprobante',
                    'help' => 'Formatos admitidos: JPG, PNG o WEBP. Máx 10MB (se optimiza automáticamente).',
                    'fullWidth' => true,
                ],
            ]" />
    </div>

    @push('scripts')
        <script>
            const crudModal = document.getElementById('crud-modal');
            document.getElementById('modal-toggle-button')?.addEventListener('click', () => crudModal.classList.remove('hidden'));
            document.querySelectorAll('[data-modal-close="crud-modal"]').forEach(el => {
                el.addEventListener('click', () => crudModal.classList.add('hidden'));
            });
        </script>
    @endpush
</x-app-layout>
