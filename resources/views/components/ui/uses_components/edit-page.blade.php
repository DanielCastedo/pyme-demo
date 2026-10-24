<?php

$fields = [
    // 1. Entrada de texto / email / password / number
    [
        'name'        => 'email',
        'label'       => 'Correo Corporativo',
        'type'        => 'email',
        'placeholder' => 'usuario@empresa.com',
        'required'    => true,
        'help'        => 'Utiliza el correo asignado por la institución.',
    ],

    // 2. Textarea
    [
        'name'        => 'biography',
        'label'       => 'Reseña Profesional',
        'type'        => 'textarea',
        'rows'        => 4,
        'placeholder' => 'Escribe una breve descripción...',
    ],

    // 3. Select con Autocomplete Integrado (Buscador JS Nativo)
    [
        'name'      => 'country_id',
        'label'     => 'País de Residencia',
        'type'      => 'select',
        'emptyText' => 'Buscar país...',
        'options'   => [
            '1' => 'Argentina',
            '2' => 'Bolivia',
            '3' => 'Chile',
            '4' => 'Colombia',
        ],
    ],

    // 4. Grupo de Checkboxes con Grid Dinámico
    [
        'name'      => 'permissions',
        'label'     => 'Permisos de Usuario',
        'type'      => 'checkbox_group',
        'grid_cols' => 'grid-cols-2 md:grid-cols-4', // Controla el diseño de las casillas
        'value'     => ['create', 'edit'],          // Valores marcados por defecto (Old/Edición)
        'options'   => [
            'create' => 'Crear Registros',
            'edit'   => 'Editar Registros',
            'delete' => 'Eliminar Registros',
            'export' => 'Exportar Reportes',
        ],
    ],

    // 5. Carga de Archivos con Render e Imagen en Tiempo Real
    [
        'name'    => 'avatar',
        'label'   => 'Foto de Perfil',
        'type'    => 'file',
        'preview' => $user->avatar_url ?? null, // Ej: 'https://tuservidor.com/storage/avatars/1.png'
        'help'    => 'Formatos admitidos: JPG, PNG o WEBP.',
    ],
];

<x-ui.form-page
    title="Modificar Datos de Usuario"
    category="Seguridad & Accesos"
    :backRoute="route('users.index')"
    :action="route('users.update', $user->id)"
    method="PUT"
    :fields="$fields"
    submitText="Actualizar Datos"
    :hasFile="true"
>
    <div class="p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg text-xs text-amber-800 dark:text-amber-300">
        ⚠️ Guardar estos cambios forzará el cierre de sesión del usuario modificado.
    </div>
</x-ui.form-page>
