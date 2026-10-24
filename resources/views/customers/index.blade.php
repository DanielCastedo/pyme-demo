<x-app-layout>
    <div class="max-w-10xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        @if (session('success'))
            <x-ui.alert type="success" :message="session('success')" />
        @endif

        @if (session('error'))
            <x-ui.alert type="error" :message="session('error')" />
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
            $buttons = [];

            if (auth()->user()->can('create', \App\Models\Customer::class)) {
                $buttons[] = [
                    'id' => 'modal-toggle-button',
                    'label' => 'Nuevo Cliente',
                    'variant' => 'primary',
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />',
                ];
            }
        @endphp

        <x-ui.page-header category="Ventas" title="Clientes"
            description="Registro de clientes para tus órdenes y ventas." :buttons="$buttons" />

        <div class="mb-5">
            <x-ui.search-box placeholder="Buscar por nombre, documento, teléfono o correo..." filterName="buscar" />
        </div>

        @php
            $columns = [
                ['key' => 'name', 'label' => 'Nombre'],
                ['key' => 'document_label', 'label' => 'Documento'],
                ['key' => 'phone', 'label' => 'Teléfono'],
                ['key' => 'city', 'label' => 'Ciudad'],
                ['key' => 'quotations_count', 'label' => 'Proformas'],
                ['key' => 'status_label', 'label' => 'Estado'],
            ];

            $actions = [
                ['type' => 'view', 'route' => 'customers.show'],
                ['type' => 'edit', 'route' => 'customers.edit'],
                ['type' => 'delete', 'route' => 'customers.destroy', 'confirm' => '¿Eliminar este cliente? Si tiene ventas, pedidos o proformas asociadas no se podrá eliminar.'],
            ];
        @endphp

        <x-ui.data-table :rows="$customers" :columns="$columns" :actions="$actions" :paginator="$customers"
            emptyText="No hay clientes registrados." />

        <x-ui.create-modal id="crud-modal" title="Registrar Nuevo Cliente" :action="route('customers.store')"
            submitText="Guardar Cliente" :columns="2" :fields="[
                [
                    'type' => 'text',
                    'name' => 'name',
                    'label' => 'Nombre / Razón social',
                    'placeholder' => 'Ej. Constructora Andina S.R.L.',
                    'required' => true,
                    'fullWidth' => true,
                ],
                [
                    'type' => 'select',
                    'name' => 'document_type',
                    'label' => 'Tipo de documento',
                    'value' => 'ci',
                    'options' => \App\Models\Customer::DOCUMENT_TYPES,
                    'required' => true,
                ],
                [
                    'type' => 'text',
                    'name' => 'document',
                    'label' => 'N° de documento',
                    'placeholder' => 'Opcional',
                ],
                [
                    'type' => 'text',
                    'name' => 'phone',
                    'label' => 'Teléfono',
                    'placeholder' => 'Ej. 7XXXXXXX',
                ],
                [
                    'type' => 'email',
                    'name' => 'email',
                    'label' => 'Correo',
                    'placeholder' => 'Opcional',
                ],
                [
                    'type' => 'text',
                    'name' => 'address',
                    'label' => 'Dirección',
                    'placeholder' => 'Opcional',
                ],
                [
                    'type' => 'text',
                    'name' => 'city',
                    'label' => 'Ciudad',
                    'placeholder' => 'Ej. Santa Cruz',
                ],
                [
                    'type' => 'number',
                    'name' => 'credit_limit',
                    'label' => 'Límite de crédito',
                    'placeholder' => 'Vacío = sin límite',
                ],
                [
                    'type' => 'checkbox',
                    'name' => 'is_active',
                    'label' => 'Cliente activo',
                    'value' => true,
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
