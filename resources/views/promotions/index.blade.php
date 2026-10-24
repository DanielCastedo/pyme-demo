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
                    'can' => 'crear promotions',
                    'label' => 'Nueva Promoción',
                    'variant' => 'primary',
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />',
                ],
            ];
        @endphp

        <x-ui.page-header category="Inventario" title="Promociones"
            description="Gestiona descuentos y su vigencia por producto." :buttons="$buttons" />

        <div class="mb-5">
            <x-ui.search-box placeholder="Buscar por nombre..." filterName="buscar" />
        </div>

        @php
            $columns = [
                ['key' => 'name', 'label' => 'Nombre'],
                ['key' => 'value', 'label' => 'Valor'],
                ['key' => 'min_quantity', 'label' => 'Cantidad Mínima'],
                ['key' => 'start_date_label', 'label' => 'Desde'],
                ['key' => 'end_date_label', 'label' => 'Hasta'],
            ];

            $actions = [
                ['type' => 'view', 'route' => 'promotions.show'],
                ['type' => 'edit', 'route' => 'promotions.edit'],
                ['type' => 'delete', 'route' => 'promotions.destroy', 'confirm' => '¿Eliminar esta promoción?'],
            ];
        @endphp

        <x-ui.data-table :rows="$promotions" :columns="$columns" :actions="$actions" :paginator="$promotions"
            emptyText="No hay promociones registradas." />

        <x-ui.create-modal id="crud-modal" title="Registrar Nueva Promoción" :action="route('promotions.store')"
            submitText="Guardar Promoción" :columns="2" :fields="[
                [
                    'type' => 'text',
                    'name' => 'name',
                    'label' => 'Nombre',
                    'placeholder' => 'Ej. Lanzamiento Herramientas Eléctricas',
                    'required' => true,
                    'fullWidth' => true,
                ],
                [
                    'type' => 'number',
                    'name' => 'value',
                    'label' => 'Valor del Descuento',
                    'placeholder' => 'Ej. 10',
                    'required' => true,
                ],
                [
                    'type' => 'number',
                    'name' => 'min_quantity',
                    'label' => 'Cantidad Mínima',
                    'placeholder' => 'Ej. 2',
                    'value' => 1,
                ],
                [
                    'type' => 'date',
                    'name' => 'start_date',
                    'label' => 'Fecha de Inicio',
                    'value' => date('Y-m-d'),
                    'required' => true,
                ],
                [
                    'type' => 'date',
                    'name' => 'end_date',
                    'label' => 'Fecha de Fin',
                    'required' => true,
                ],
                [
                    'type' => 'checkbox',
                    'name' => 'is_active',
                    'label' => 'Promoción activa',
                    'value' => true,
                ],
                [
                    'type' => 'checkbox-grid',
                    'name' => 'product_ids',
                    'label' => 'Productos incluidos',
                    'options' => $products->pluck('name', 'id')->toArray(),
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
