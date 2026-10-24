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
                    'can' => 'crear roles',
                    'label' => 'Nuevo Rol',
                    'variant' => 'primary',
                    'icon' =>
                        '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />',
                    'iconPosition' => 'left',
                ],
            ];
        @endphp

        <x-ui.page-header category="Seguridad" title="Gestión de Roles"
            description="Gestiona los roles y permisos del sistema." :buttons="$buttons" />

        <!-- Buscador -->
        <div class="mb-5">
            <x-ui.search-box placeholder="Buscar menú por nombre..."  filterName="buscar"/>
        </div>

        <!-- tabla -->
        @php
            $columns = [['key' => 'id', 'label' => 'ID'], ['key' => 'name', 'label' => 'Nombre del Rol']];

            $actions = [
                ['type' => 'view', 'route' => 'roles.show'],
                ['type' => 'edit', 'route' => 'roles.edit'],
            ];

            if (auth()->user()?->hasRole('superadmin')) {
                $actions[] = ['type' => 'delete', 'route' => 'roles.destroy', 'confirm' => '¿Eliminar rol?'];
            }
        @endphp

        <x-ui.data-table :rows="$roles" :columns="$columns" :actions="$actions" :paginator="$roles" />

        <!-- Modal Create Rol -->
        <x-ui.create-modal id="crud-modal" title="Registrar Nuevo Rol" :action="route('roles.store')" submitText="Guardar Rol"
            style="simple" :fields="[
                [
                    'type' => 'text',
                    'name' => 'name',
                    'label' => 'Nombre del Rol',
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
