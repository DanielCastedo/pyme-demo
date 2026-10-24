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

            if (auth()->user()->can('create', \App\Models\Supplier::class)) {
                $buttons[] = [
                    'id' => 'modal-toggle-button',
                    'label' => 'Nuevo Proveedor',
                    'variant' => 'primary',
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />',
                ];
            }
        @endphp

        <x-ui.page-header category="Inventario" title="Proveedores"
            description="A quienes les compras mercadería. Se eligen al registrar una compra." :buttons="$buttons" />

        <div class="mb-5">
            <x-ui.search-box placeholder="Buscar por nombre, documento, contacto, teléfono o correo..." filterName="buscar" />
        </div>

        @php
            $columns = [
                ['key' => 'name', 'label' => 'Nombre'],
                ['key' => 'document_label', 'label' => 'Documento'],
                ['key' => 'phone', 'label' => 'Teléfono'],
                ['key' => 'city', 'label' => 'Ciudad'],
                ['key' => 'contact_name', 'label' => 'Contacto'],
                ['key' => 'purchases_count', 'label' => 'Compras'],
                ['key' => 'status_label', 'label' => 'Estado'],
            ];

            $actions = [
                ['type' => 'view', 'route' => 'suppliers.show'],
                ['type' => 'edit', 'route' => 'suppliers.edit'],
                ['type' => 'delete', 'route' => 'suppliers.destroy', 'confirm' => '¿Eliminar este proveedor? Si tiene compras asociadas no se podrá eliminar.'],
            ];
        @endphp

        <x-ui.data-table :rows="$suppliers" :columns="$columns" :actions="$actions" :paginator="$suppliers"
            emptyText="No hay proveedores registrados." />

        <x-ui.create-modal id="crud-modal" title="Registrar Nuevo Proveedor" :action="route('suppliers.store')"
            submitText="Guardar Proveedor" :columns="2" :fields="[
                [
                    'type' => 'text',
                    'name' => 'name',
                    'label' => 'Nombre / Razón social',
                    'placeholder' => 'Ej. Distribuidora Importadora S.R.L.',
                    'required' => true,
                    'fullWidth' => true,
                ],
                [
                    'type' => 'select',
                    'name' => 'document_type',
                    'label' => 'Tipo de documento',
                    'value' => 'nit',
                    'options' => \App\Models\Supplier::DOCUMENT_TYPES,
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
                    'name' => 'contact_name',
                    'label' => 'Persona de contacto',
                    'placeholder' => 'Opcional',
                    'fullWidth' => true,
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
                    'type' => 'checkbox',
                    'name' => 'is_active',
                    'label' => 'Proveedor activo',
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
