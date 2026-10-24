<x-app-layout>
    <div class="max-w-10xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        @include('sales.partials.alerts')

        @php
            $buttons = [];

            if (auth()->user()->can('create', \App\Models\ExpenseCategory::class)) {
                $buttons[] = [
                    'id' => 'modal-toggle-button',
                    'label' => 'Nueva Categoría',
                    'variant' => 'primary',
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />',
                ];
            }
        @endphp

        <x-ui.page-header category="Gastos" title="Categorías de gasto"
            description="Agrupan tus gastos para saber en qué se va el dinero." :buttons="$buttons" />

        <div class="mb-5">
            <x-ui.search-box placeholder="Buscar categoría..." filterName="buscar" />
        </div>

        @php
            $columns = [
                ['key' => 'name', 'label' => 'Nombre'],
                ['key' => 'expenses_count', 'label' => 'Gastos'],
                ['key' => 'status_label', 'label' => 'Estado'],
            ];

            $actions = [
                ['type' => 'edit', 'route' => 'expense-categories.edit'],
                ['type' => 'delete', 'route' => 'expense-categories.destroy', 'confirm' => '¿Eliminar esta categoría? Si tiene gastos asociados no se podrá eliminar.'],
            ];
        @endphp

        <x-ui.data-table :rows="$categories" :columns="$columns" :actions="$actions" :paginator="$categories"
            emptyText="No hay categorías registradas." />

        <x-ui.create-modal id="crud-modal" title="Registrar Nueva Categoría" :action="route('expense-categories.store')"
            submitText="Guardar Categoría" :columns="1" :fields="[
                ['type' => 'text', 'name' => 'name', 'label' => 'Nombre', 'placeholder' => 'Ej. Servicios básicos', 'required' => true],
                ['type' => 'checkbox', 'name' => 'is_active', 'label' => 'Categoría activa', 'value' => true],
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
