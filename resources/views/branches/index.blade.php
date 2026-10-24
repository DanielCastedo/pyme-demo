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
            $buttons = [
                [
                    'id' => 'modal-toggle-button',
                    'can' => 'crear branches',
                    'label' => 'Nueva Sucursal',
                    'variant' => 'primary',
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />',
                ],
            ];
        @endphp

        <x-ui.page-header category="Inventario" title="Sucursales"
            description="Administra las sucursales, su logo y su QR de cobro." :buttons="$buttons" />

        <div class="mb-5">
            <x-ui.search-box placeholder="Buscar por nombre..." filterName="buscar" />
        </div>

        @php
            $columns = [
                ['key' => 'name', 'label' => 'Nombre'],
                ['key' => 'address', 'label' => 'Dirección'],
                ['key' => 'phone', 'label' => 'Teléfono'],
            ];

            $actions = [
                ['type' => 'view', 'route' => 'branches.show'],
                ['type' => 'edit', 'route' => 'branches.edit'],
                ['type' => 'delete', 'route' => 'branches.destroy', 'confirm' => '¿Eliminar esta sucursal?'],
            ];
        @endphp

        <x-ui.data-table :rows="$branches" :columns="$columns" :actions="$actions" :paginator="$branches"
            emptyText="No hay sucursales registradas." />

        <x-ui.create-modal id="crud-modal" title="Registrar Nueva Sucursal" :action="route('branches.store')"
            submitText="Guardar Sucursal" :hasFile="true" :columns="2" :fields="[
                [
                    'type' => 'text',
                    'name' => 'name',
                    'label' => 'Nombre',
                    'placeholder' => 'Ej. Sucursal Central - Santa Cruz',
                    'required' => true,
                    'fullWidth' => true,
                ],
                [
                    'type' => 'text',
                    'name' => 'address',
                    'label' => 'Dirección',
                    'placeholder' => 'Ej. Av. Cristo Redentor, 3er anillo interno',
                ],
                [
                    'type' => 'text',
                    'name' => 'phone',
                    'label' => 'Teléfono',
                    'placeholder' => 'Ej. 3-3456789',
                ],
                [
                    'type' => 'checkbox',
                    'name' => 'is_active',
                    'label' => 'Sucursal activa',
                    'value' => true,
                ],
                [
                    'type' => 'file',
                    'name' => 'logo',
                    'label' => 'Logo',
                    'help' => 'Se imprime en el encabezado de los recibos de esta sucursal. JPG, PNG o WEBP, máx 10MB.',
                    'fullWidth' => true,
                ],
                [
                    'type' => 'file',
                    'name' => 'payment_qr_image',
                    'label' => 'QR de Cobro',
                    'help' => 'QR que verán los clientes de esta sucursal al pagar con QR en el punto de venta.',
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
