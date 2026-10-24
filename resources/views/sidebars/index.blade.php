<x-app-layout>
    <div class="max-w-10xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

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
                    'can' => 'crear sidebars',
                    'label' => 'Nuevo Menú',
                    'variant' => 'primary',
                    'icon' =>
                        '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />',
                    'iconPosition' => 'left',
                ],
            ];
        @endphp

        <x-ui.page-header category="Configuración" title="Menús del Sidebar"
            description="Gestiona los enlaces y accesos de navegación del sistema." :buttons="$buttons" />

        <!-- Buscador -->
        <div class="mb-5">
            <x-ui.search-box placeholder="Buscar menú por nombre..." filterName="buscar" />
        </div>

        <!-- Tabla -->
        @php
            $columns = [
                ['key' => 'orden', 'label' => 'Orden'],
                ['key' => 'nombre', 'label' => 'Nombre'],
                ['key' => 'ruta', 'label' => 'Ruta (Route)'],
                ['key' => 'parent_name', 'label' => 'Dependencia'],
                ['key' => 'access_badges', 'label' => 'Acceso Requerido'],
            ];

            $rows = $menus->through(function ($menu) {
                $menu->parent_name = $menu->parent ? 'Submenú de ' . $menu->parent->nombre : 'Menú Principal';
                $menu->access_badges = trim(
                    ($menu->rol_requerido ? 'Rol: ' . $menu->rol_requerido : '') .
                        ($menu->rol_requerido && $menu->permiso_requerido ? ' | ' : '') .
                        ($menu->permiso_requerido ? 'Permiso: ' . $menu->permiso_requerido : ''),
                );

                if (!$menu->rol_requerido && !$menu->permiso_requerido) {
                    $menu->access_badges = 'Público / Todos';
                }

                $menu->ruta = $menu->ruta ?: 'N/A';
                return $menu;
            });

            $actions = [
                ['type' => 'edit', 'route' => 'sidebars.edit'],
                [
                    'type' => 'delete',
                    'route' => 'sidebars.destroy',
                    'confirm' => '¿Seguro que deseas borrar este menú?',
                    'label' => 'Borrar',
                ],
            ];
        @endphp

        <x-ui.data-table :rows="$rows" :columns="$columns" :actions="$actions" :paginator="$menus"
            emptyText="No hay menús registrados." rowKey="id" />

        <!-- Modal Create -->
        <x-ui.create-modal-sidebar id="crud-modal" title="Registrar nuevo menú" :action="route('sidebars.store')" submitText="Guardar"
            :fields="[
                [
                    'type' => 'text',
                    'name' => 'nombre',
                    'label' => 'Nombre Mostrar',
                    'placeholder' => 'Ej. Panel de Control',
                    'required' => true,
                ],
                [
                    'type' => 'text',
                    'name' => 'ruta',
                    'label' => 'Ruta',
                    'placeholder' => 'Ej. dashboard',
                ],
                [
                    'type' => 'icon-picker',
                    'name' => 'icono',
                    'label' => 'Ícono del Menú',
                    'value' => '',
                    'fullWidth' => true,
                ],
                [
                    'type' => 'number',
                    'name' => 'orden',
                    'label' => 'Orden',
                    'placeholder' => 'Ej. 1',
                    'value' => 1,
                    'required' => true,
                ],
                [
                    'type' => 'select',
                    'name' => 'parent_id',
                    'label' => 'Menú Padre',
                    'options' => $menusPadre->pluck('nombre', 'id')->toArray(),
                    'emptyText' => '-- Ninguno (Menú Principal) --',
                ],
                [
                    'type' => 'select',
                    'name' => 'rol_requerido',
                    'label' => 'Rol Requerido (Opcional)',
                    'options' => $roles->pluck('name', 'name')->toArray(),
                    'emptyText' => '-- Todos / Público --',
                ],
                [
                    'type' => 'select',
                    'name' => 'permiso_requerido',
                    'label' => 'Permiso Requerido (Opcional)',
                    'options' => $permisos->pluck('name', 'name')->toArray(),
                    'emptyText' => '-- Sin restricción de permiso --',
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
