<?php

// Definición Completa de Columnas
$columns = [
    // Texto Simple / ID (Por defecto si no se pasa 'type')
    [
        'key' => 'id',
        'label' => 'ID',
    ],

    // Relación Anidada Estándar (Usa data_get internamente)
    [
        'key' => 'group.name',
        'label' => 'Grupo',
    ],

    // Tipo: Lista de Badges (badge-list)
    [
        'key' => 'roles', // Debe ser un Array o Colección
        'label' => 'Roles de Usuario',
        'type' => 'badge-list',
        'labelKey' => 'name', // Propiedad a mostrar si el item es un objeto/modelo
        'color' => 'sky', // Opciones: emerald, sky, violet, amber, rose
        'emptyText' => 'Sin roles asignados', // Texto si la colección está vacía
    ],

    // Tipo: Avatar con Subtexto (avatar-text)
    [
        'label' => 'Profesor',
        'type' => 'avatar-text',
        'nameKey' => 'teacher.name', // Llave para el título principal
        'subKey' => 'teacher.email', // Llave para el subtexto inferior
        'color' => 'violet', // Color del círculo del avatar
    ],

    // Tipo: Texto con Icono Predefinido (icon-text)
    [
        'key' => 'status_label',
        'label' => 'Seguridad',
        'type' => 'icon-text',
        'icon' => 'shield', // Presets internos: shield, user, tag, key
    ],

    // Tipo: Texto con Icono SVG Personalizado (icon-text con iconSvg)
    [
        'key' => 'room_number',
        'label' => 'Aula',
        'type' => 'icon-text',
        'iconSvg' => '<svg class="w-3.5 h-3.5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>',
    ],
];

// Definición Completa de Acciones ($actions)
$actions = [
    // Acciones basadas en presets (toman clases e iconos automáticos)
    ['type' => 'view', 'route' => 'records.show'],
    ['type' => 'edit', 'route' => 'records.edit'],
    ['type' => 'manage', 'route' => 'records.manage'],

    // Acción con método destructivo y confirmación
    [
        'type' => 'delete',
        'route' => 'records.destroy',
        'confirm' => '¿Estás completamente seguro de eliminar este registro?',
    ],

    // Acción totalmente personalizada (Sobrescribiendo valores por defecto)
    [
        'type' => 'view', // Usa el base 'view'
        'label' => 'Descargar', // Cambias el texto "Ver" por "Descargar"
        'route' => 'records.download',
        'class' => 'inline-flex items-center gap-1 px-2 py-1 rounded border text-emerald-700 border-emerald-300 hover:bg-emerald-50 text-[11px] font-medium', // Clases personalizadas
        'icon' => '<svg>...</svg>', // Cambias el icono de visualización
    ],
];

// Invocación del Componente en Blade
<x-ui.data-table
    :rows="$records"
    :columns="$columns"
    :actions="$actions"
    :paginator="$records"
    rowKey="id"
    emptyText="No se encontraron asignaciones en el sistema"
/>
