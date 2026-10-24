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
            if (auth()->user()->can('create', \App\Models\Category::class)) {
                $buttons[] = [
                    'id' => 'modal-toggle-button',
                    'label' => 'Nueva Categoría',
                    'variant' => 'primary',
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />',
                ];
            }
        @endphp

        <x-ui.page-header category="Inventario" title="Categorías"
            description="Organiza el catálogo de productos por categoría." :buttons="$buttons" />

        <div class="mb-5">
            <x-ui.search-box placeholder="Buscar por nombre..." filterName="buscar" />
        </div>

        @php
            $columns = [
                ['key' => 'name', 'label' => 'Nombre'],
                ['key' => 'description', 'label' => 'Descripción'],
            ];

            $actions = [
                ['type' => 'view', 'route' => 'categories.show'],
                ['type' => 'edit', 'route' => 'categories.edit'],
                ['type' => 'delete', 'route' => 'categories.destroy', 'confirm' => '¿Eliminar esta categoría?'],
            ];
        @endphp

        <x-ui.data-table :rows="$categories" :columns="$columns" :actions="$actions" :paginator="$categories"
            emptyText="No hay categorías registradas." />

        @can('create', \App\Models\Category::class)
        <x-ui.create-modal id="crud-modal" title="Registrar Nueva Categoría" :action="route('categories.store')"
            submitText="Guardar Categoría" :columns="2" :fields="[
                [
                    'type' => 'text',
                    'name' => 'name',
                    'label' => 'Nombre',
                    'placeholder' => 'Ej. Herramientas Manuales',
                    'required' => true,
                ],
                [
                    'type' => 'textarea',
                    'name' => 'description',
                    'label' => 'Descripción',
                    'placeholder' => 'Ej. Martillos, destornilladores y llaves.',
                    'fullWidth' => true,
                    'rows' => 3,
                ],
            ]" />
        @endcan
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
