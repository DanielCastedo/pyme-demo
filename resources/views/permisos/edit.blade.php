<x-ui.edit-page
    title="Editar Permiso: {{ $permiso->name }}"
    category="EDICIÓN DE SEGURIDAD"
    backRoute="{{ route('permisos.index') }}"
    :action="route('permisos.update', $permiso->id)"
    method="PUT"
    submitText="Actualizar Permiso"
    :fields="[
        [
            'type' => 'text',
            'name' => 'name',
            'label' => 'Nombre del Permiso',
            'placeholder' => 'Ej. Administrador',
            'value' => old('name', $permiso->name),
            'required' => true,
        ],
    ]"
/>

