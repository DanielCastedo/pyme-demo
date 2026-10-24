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
            $buttons = [];
            if (auth()->user()->can('create', \App\Models\Warehouse::class)) {
                $buttons[] = [
                    'id' => 'modal-toggle-button',
                    'label' => 'Nuevo Almacén',
                    'variant' => 'primary',
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />',
                ];
            }
        @endphp

        <x-ui.page-header category="Inventario" title="Almacenes"
            description="Gestiona los almacenes de cada sucursal." :buttons="$buttons" />

        <div class="mb-5">
            <x-ui.search-box placeholder="Buscar por nombre o código..." filterName="buscar" />
        </div>

        @php
            $columns = [
                ['key' => 'name', 'label' => 'Nombre'],
                ['key' => 'code', 'label' => 'Código'],
                ['key' => 'branch.name', 'label' => 'Sucursal'],
            ];

            $actions = [
                ['type' => 'view', 'route' => 'warehouses.show'],
                ['type' => 'edit', 'route' => 'warehouses.edit'],
                ['type' => 'delete', 'route' => 'warehouses.destroy', 'confirm' => '¿Eliminar este almacén?'],
            ];
        @endphp

        <x-ui.data-table :rows="$warehouses" :columns="$columns" :actions="$actions" :paginator="$warehouses"
            emptyText="No hay almacenes registrados." />

        @can('create', \App\Models\Warehouse::class)
        <x-ui.create-modal id="crud-modal" title="Registrar Nuevo Almacén" :action="route('warehouses.store')"
            submitText="Guardar Almacén" :columns="2" :fields="[
                [
                    'type' => 'select',
                    'name' => 'branche_id',
                    'label' => 'Sucursal',
                    'options' => $branches->pluck('name', 'id')->toArray(),
                    'required' => true,
                ],
                [
                    'type' => 'text',
                    'name' => 'name',
                    'label' => 'Nombre del Almacén',
                    'placeholder' => 'Ej. Almacén Central',
                    'required' => true,
                ],
                [
                    'type' => 'text',
                    'name' => 'code',
                    'label' => 'Código',
                    'placeholder' => 'Ej. ALM-01',
                    'required' => true,
                ],
                [
                    'type' => 'checkbox',
                    'name' => 'is_active',
                    'label' => 'Almacén activo',
                    'value' => true,
                ],
                [
                    'type' => 'checkbox',
                    'name' => 'default_warehouse',
                    'label' => 'Usar como almacén predeterminado para todas las operaciones',
                    'help' => 'Se guarda solo en este navegador (no en el servidor): preselecciona este almacén al vender, hacer pedidos, consignar, comprar y en movimientos de stock.',
                    'fullWidth' => true,
                ],
            ]" />
        @endcan
    </div>

    @if (session('default_warehouse'))
        <script>
            // "Usar como almacén predeterminado" no se guarda en la base de datos: se escribe acá,
            // una sola vez, en el localStorage de este navegador.
            (function() {
                try {
                    const w = @json(session('default_warehouse'));
                    const loc = JSON.parse(localStorage.getItem('pos-default-location') || '{}');
                    loc.branch = w.branche_id;
                    loc.warehouse = w.id;
                    delete loc.cashbox; // la caja depende de la sucursal: que se vuelva a elegir
                    localStorage.setItem('pos-default-location', JSON.stringify(loc));
                } catch (e) {
                    // localStorage bloqueado o navegación privada: no es grave, solo no queda recordado.
                }
            })();
        </script>
    @endif

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
