<x-ui.edit-page
    title="Editar Rol: {{ $role->name }}"
    category="EDICIÓN DE SEGURIDAD"
    backRoute="{{ route('roles.index') }}"
    :action="route('roles.update', $role->id)"
    method="PUT"
    submitText="Actualizar Rol"
    :fields="[
        [
            'type' => 'text',
            'name' => 'name',
            'label' => 'Nombre del Rol',
            'placeholder' => 'Ej. Administrador',
            'value' => old('name', $role->name),
            'required' => true,
        ],
    ]"
/>
