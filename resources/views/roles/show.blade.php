<x-ui.show-page
    title="Rol: {{ $role->name }}"
    category="DETALLES DE SEGURIDAD"
    :backRoute="route('roles.index')"
    :editRoute="auth()->user()->can('update', $role) ? route('roles.edit', $role->id) : ''"
    editText="Editar Rol"
    :columns="2"
    :items="[
        [
            'label' => 'ID del Rol',
            'value' => '#' . $role->id,
            'mono'  => true
        ],
        [
            'label' => 'Nombre',
            'value' => $role->name,
            'mono'  => false
        ]
    ]"
/>
