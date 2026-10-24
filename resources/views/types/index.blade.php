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
                    'can' => 'crear types',
                    'label' => 'Nuevo Tipo',
                    'variant' => 'primary',
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />',
                ],
            ];
        @endphp

        <x-ui.page-header category="Configuración" title="Tipos"
            description="Catálogo de tipos usado en transacciones y movimientos de almacén." :buttons="$buttons" />

        <div class="mb-5">
            <x-ui.search-box placeholder="Buscar por nombre o código..." filterName="buscar" />
        </div>

        @php
            $columns = [
                ['key' => 'name', 'label' => 'Nombre'],
                ['key' => 'code', 'label' => 'Código'],
                ['key' => 'sign', 'label' => 'Signo'],
            ];

            $actions = [
                ['type' => 'view', 'route' => 'types.show'],
                ['type' => 'edit', 'route' => 'types.edit'],
                ['type' => 'delete', 'route' => 'types.destroy', 'confirm' => '¿Eliminar este tipo?'],
            ];
        @endphp

        <x-ui.data-table :rows="$types" :columns="$columns" :actions="$actions" :paginator="$types"
            emptyText="No hay tipos registrados." />

        <x-ui.create-modal id="crud-modal" title="Registrar Nuevo Tipo" :action="route('types.store')"
            submitText="Guardar Tipo" :columns="2" :fields="[
                [
                    'type' => 'text',
                    'name' => 'name',
                    'label' => 'Nombre',
                    'placeholder' => 'Ej. Venta',
                    'required' => true,
                ],
                [
                    'type' => 'text',
                    'name' => 'code',
                    'label' => 'Código',
                    'placeholder' => 'Ej. VTA',
                    'required' => true,
                ],
                [
                    'type' => 'text',
                    'name' => 'sign',
                    'label' => 'Signo',
                    'placeholder' => 'Ej. + / -',
                    'help' => 'Opcional. Indica si el tipo suma o resta (+, -), déjalo vacío si no aplica.',
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
