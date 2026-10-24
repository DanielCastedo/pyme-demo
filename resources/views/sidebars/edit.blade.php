<x-ui.edit-page
    title="Editar Menú: {{ $sidebar->nombre }}"
    category="Configuración"
    backRoute="{{ route('sidebars.index') }}"
    :action="route('sidebars.update', $sidebar->id)"
    method="PUT"
    submitText="Guardar Cambios"
    :fields="[
        [
            'type' => 'text',
            'name' => 'nombre',
            'label' => 'Nombre Mostrar',
            'placeholder' => 'Ej. Panel de Control',
            'value' => $sidebar->nombre,
            'required' => true,
        ],
        [
            'type' => 'text',
            'name' => 'ruta',
            'label' => 'Ruta de Laravel (Route Name)',
            'placeholder' => 'Ej. dashboard',
            'value' => $sidebar->ruta,
        ],
        [
            'type' => 'number',
            'name' => 'orden',
            'label' => 'Orden de aparición',
            'placeholder' => 'Ej. 1',
            'value' => $sidebar->orden,
            'required' => true,
        ],
        [
            'type' => 'select',
            'name' => 'parent_id',
            'label' => 'Menú Padre (Para submenús)',
            'options' => $menusPadre->pluck('nombre', 'id')->toArray(),
            'value' => $sidebar->parent_id,
            'emptyText' => '-- Ninguno (Es un menú principal) --',
        ],
        [
            'type' => 'select',
            'name' => 'permiso_requerido',
            'label' => 'Permiso Requerido (Opcional)',
            'options' => $permisos->pluck('name', 'name')->toArray(),
            'value' => $sidebar->permiso_requerido,
            'emptyText' => '-- Público / Sin restricción --',
            'help' => 'Si seleccionas un permiso, solo los usuarios con este permiso verán el menú.',
        ],
        [
            'type' => 'select',
            'name' => 'rol_requerido',
            'label' => 'Rol Requerido (Opcional)',
            'options' => $roles->pluck('name', 'name')->toArray(),
            'value' => $sidebar->rol_requerido,
            'emptyText' => '-- Sin restricción de rol --',
        ],
    ]"
>
    {{-- Este contenido cae en {{ $slot }} de edit-page: dentro del <form>, después
         del grid de campos y antes de los botones de Guardar/Cancelar. --}}
    <x-ui.icon-picker
        name="icono"
        label="Ícono del Menú"
        :value="$sidebar->icono"
        help="Este ícono se mostrará junto al nombre del menú en el sidebar."
    />
</x-ui.edit-page>