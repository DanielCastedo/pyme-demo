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
            $buttons = [];
            if (auth()->user()->can('crear usuarios')) {
                $buttons[] = [
                    'id' => 'modal-toggle-button',
                    'label' => 'Añadir Usuario',
                    'variant' => 'primary',
                    'icon' =>
                        '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />',
                    'iconPosition' => 'left',
                ];
            }
        @endphp

        <x-ui.page-header category="Seguridad" title="Gestión de Usuarios"
            description="Gestiona los usuarios y sus permisos del sistema." :buttons="$buttons" />

        <!-- Buscador -->
        <div class="mb-5">
            <x-ui.search-box placeholder="Buscar menú por nombre..."  filterName="buscar"/>
        </div>

        <!-- Tabla Responsiva -->
        <x-ui.data-table :rows="$usuarios" emptyText="No hay usuarios registrados en el sistema." :paginator="$usuarios"
            :columns="[
                [
                    'label' => 'ID',
                    'key' => 'id',
                ],
                [
                    'label' => 'Usuario',
                    'key' => 'name',
                    'type' => 'avatar-text',
                    'nameKey' => 'name',
                    'subKey' => 'email',
                    'color' => 'emerald',
                ],
                [
                    'label' => 'Roles',
                    'key' => 'roles',
                    'type' => 'badge-list',
                    'labelKey' => 'name',
                    'color' => 'emerald',
                    'emptyText' => 'Sin roles asignados',
                ],
            ]" :actions="[
                [
                    'type' => 'edit',
                    'route' => 'usuarios.edit',
                ],
                [
                    'type' => 'delete',
                    'route' => 'usuarios.destroy',
                    'confirm' => '¿Estás seguro de eliminar este usuario?',
                ],
            ]" />

        <!-- Modal Registrar Usuario -->
        @can('crear usuarios')
        <x-ui.create-modal id="crud-modal" title="Registrar Nuevo Usuario" :action="route('usuarios.store')"
            submitText="Guardar Usuario" :columns="2" :fields="[
                [
                    'type' => 'text',
                    'name' => 'name',
                    'label' => 'Nombre Completo',
                    'placeholder' => 'Ej. Juan Pérez',
                    'required' => true,
                ],
                [
                    'type' => 'email',
                    'name' => 'email',
                    'label' => 'Correo Electrónico',
                    'placeholder' => 'ejemplo@correo.com',
                    'required' => true,
                ],
                [
                    'type' => 'select',
                    'name' => 'branche_id',
                    'label' => 'Sucursal',
                    'options' => $branches->pluck('name', 'id')->toArray(),
                    'required' => true,
                    'fullWidth' => true,
                ],
                [
                    'type' => 'password',
                    'name' => 'password',
                    'label' => 'Contraseña Temporal',
                    'placeholder' => 'Mínimo 8 caracteres',
                    'required' => true,
                    'fullWidth' => true,
                ],
                [
                    'type' => 'checkbox-grid',
                    'name' => 'roles[]',
                    'label' => 'Asignar Roles',
                    'options' => $roles->pluck('name', 'name')->toArray(),
                    'fullWidth' => true,
                ],
            ]" />
        @endcan

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
