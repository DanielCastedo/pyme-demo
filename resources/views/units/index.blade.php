<x-app-layout>
    <div class="max-w-10xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        @include('sales.partials.alerts')

        @php
            $buttons = [];

            if (auth()->user()->can('create', \App\Models\Unit::class)) {
                $buttons[] = [
                    'id' => 'modal-toggle-button',
                    'label' => 'Nueva Unidad',
                    'variant' => 'primary',
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />',
                ];
            }
        @endphp

        <x-ui.page-header category="Inventario" title="Unidades de medida"
            description="Catálogo de unidades (Unidad, Caja, Docena, Kilogramo...). Al crear la presentación de un producto eliges una de esta lista." :buttons="$buttons" />

        <div class="mb-5">
            <x-ui.search-box placeholder="Buscar por nombre o abreviatura..." filterName="buscar" />
        </div>

        @php
            $columns = [
                ['key' => 'name', 'label' => 'Nombre'],
                ['key' => 'abbreviation', 'label' => 'Abreviatura'],
                ['key' => 'product_units_count', 'label' => 'Productos que la usan'],
                ['key' => 'status_label', 'label' => 'Estado'],
            ];

            $actions = [
                ['type' => 'edit', 'route' => 'units.edit'],
                ['type' => 'delete', 'route' => 'units.destroy', 'confirm' => '¿Eliminar esta unidad? Si hay productos que la usan no se podrá eliminar.'],
            ];
        @endphp

        <x-ui.data-table :rows="$units" :columns="$columns" :actions="$actions" :paginator="$units"
            emptyText="No hay unidades registradas." />

        <x-ui.create-modal id="crud-modal" title="Registrar Nueva Unidad" :action="route('units.store')"
            submitText="Guardar Unidad" :columns="1" :fields="[
                ['type' => 'text', 'name' => 'name', 'label' => 'Nombre', 'placeholder' => 'Ej. Caja', 'required' => true],
                ['type' => 'text', 'name' => 'abbreviation', 'label' => 'Abreviatura', 'placeholder' => 'Ej. cja (opcional)'],
                ['type' => 'checkbox', 'name' => 'is_active', 'label' => 'Unidad activa', 'value' => true],
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
