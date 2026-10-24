<x-app-layout>
    <div class="max-w-10xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <!-- Alertas -->
        @if (session('success'))
            <x-ui.alert type="success" :message="session('success')" />
        @endif

        @if (session('error'))
            <x-ui.alert type="error" :message="session('error')" />
        @endif

        <!-- Encabezado -->
        @php
            $buttons = [
                [
                    'id' => 'modal-toggle-button',
                    'can' => 'crear permisos',
                    'label' => 'Nuevo Permiso',
                    'variant' => 'primary',
                    'icon' =>
                        '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />',
                    'iconPosition' => 'left',
                ],
            ];
        @endphp

        <x-ui.page-header category="Seguridad" title="Gestión de Permisos"
            description="Gestiona los permisos del sistema." :buttons="$buttons" />

        <!-- Buscador -->
        <div class="mb-5">
            <x-ui.search-box placeholder="Buscar permiso por nombre..." filterName="buscar" />
        </div>

        <!-- Tabla -->
        @php
            $columns = [['key' => 'id', 'label' => 'ID'], ['key' => 'name', 'label' => 'Nombre del Permiso']];

            $actions = [
                ['type' => 'edit', 'route' => 'permisos.edit', 'permission' => 'editar permisos'],
            ];

            if (auth()->user()?->hasRole('superadmin')) {
                $actions[] = ['type' => 'delete', 'route' => 'permisos.destroy', 'permission' => 'eliminar permisos', 'confirm' => '¿Eliminar permiso?'];
            }
        @endphp

        <x-ui.data-table :rows="$permisos" :columns="$columns" :actions="$actions" :paginator="$permisos" />

        <!-- Modal Create Permiso -->
        <x-ui.create-modal id="crud-modal" title="Registrar Nuevo Permiso" :action="route('permisos.store')"
            submitText="Guardar Permiso" :fields="[
                [
                    'type' => 'text',
                    'name' => 'name',
                    'label' => 'Nombre del Permiso',
                    'placeholder' => 'Ej. Administrador',
                    'required' => true,
                ],
            ]" />

    </div>

    @push('scripts')
        <script>
            const modal = document.getElementById('crud-modal');
            document.getElementById('modal-toggle-button')?.addEventListener('click', () => modal.classList.remove('hidden'));
            document.querySelectorAll('[data-modal-close="crud-modal"]').forEach(el => {
                el.addEventListener('click', () => modal.classList.add('hidden'));
            });
        </script>
    @endpush
</x-app-layout>
