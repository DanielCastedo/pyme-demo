@props([
    'type' => 'text',
    'name' => '',
    'label' => '',
    'value' => '',
    'placeholder' => '',
    'required' => false,
    'options' => [],
    'rows' => 3,
    'help' => '',
])

@php
    $iconList = [
        'home' => [
            'label' => 'Inicio',
            'path' =>
                '<path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />',
        ],
        'upload' => [
            'label' => 'Subir',
            'path' =>
                '<path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />',
        ],
        'book' => [
            'label' => 'Materias',
            'path' =>
                '<path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />',
        ],
        'academic-cap' => [
            'label' => 'Docentes',
            'path' =>
                '<path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422A12.083 12.083 0 0112 21.5a12.083 12.083 0 01-6.16-10.922L12 14z" />',
        ],
        'users' => [
            'label' => 'Usuarios',
            'path' =>
                '<path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />',
        ],
        'shield' => [
            'label' => 'Roles',
            'path' =>
                '<path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />',
        ],
        'key' => [
            'label' => 'Permisos',
            'path' =>
                '<path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />',
        ],
        'cog' => [
            'label' => 'Configuración',
            'path' =>
                '<path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />',
        ],
        'archive' => [
            'label' => 'Grupos',
            'path' =>
                '<path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />',
        ],
        'folder' => [
            'label' => 'Material',
            'path' =>
                '<path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />',
        ],
        'menu' => [
            'label' => 'Menús',
            'path' =>
                '<path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />',
        ],
        'briefcase' => [
            'label' => 'Gestión',
            'path' =>
                '<path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />',
        ],
        'id-card' => [
            'label' => 'Auxiliares',
            'path' =>
                '<path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" />',
        ],
        'chart-bar' => [
            'label' => 'Estadísticas',
            'path' =>
                '<path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />',
        ],
        'bell' => [
            'label' => 'Notificaciones',
            'path' =>
                '<path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />',
        ],
        'calendar' => [
            'label' => 'Calendario',
            'path' =>
                '<path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />',
        ],
        'chat' => [
            'label' => 'Mensajes',
            'path' =>
                '<path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />',
        ],
        'clipboard' => [
            'label' => 'Reportes',
            'path' =>
                '<path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />',
        ],
        'database' => [
            'label' => 'Base de Datos',
            'path' =>
                '<path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />',
        ],
        'globe' => [
            'label' => 'Web / Público',
            'path' =>
                '<path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />',
        ],
        'pencil' => [
            'label' => 'Editar',
            'path' =>
                '<path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />',
        ],
        'trash' => [
            'label' => 'Eliminar',
            'path' =>
                '<path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />',
        ],
        'search' => [
            'label' => 'Buscar',
            'path' =>
                '<path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />',
        ],
        'plus-circle' => [
            'label' => 'Agregar',
            'path' =>
                '<path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />',
        ],
        'eye' => [
            'label' => 'Ver',
            'path' =>
                '<path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S3.732 16.057 2.458 12z" />',
        ],
        'lock-closed' => [
            'label' => 'Seguridad',
            'path' =>
                '<path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />',
        ],
        'download' => [
            'label' => 'Descargar',
            'path' =>
                '<path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />',
        ],
        'link' => [
            'label' => 'Enlace',
            'path' =>
                '<path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />',
        ],
        'office-building' => [
            'label' => 'Institución',
            'path' =>
                '<path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />',
        ],
        'photograph' => [
            'label' => 'Multimedia',
            'path' =>
                '<path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />',
        ],
        'wrench' => [
            'label' => 'Herramientas',
            'path' =>
                '<path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />',
        ],
        'truck' => [
            'label' => 'Entregas / Proveedor',
            'path' =>
                '<path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zm10 0a2 2 0 11-4 0 2 2 0 014 0z"/><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10l2 2h10l2-2zM13 6l3 4h3l1 2v4h-2" />',
        ],
        'cube' => [
            'label' => 'Productos / Stock',
            'path' =>
                '<path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />',
        ],
        'currency-dollar' => [
            'label' => 'Ventas / Precios',
            'path' =>
                '<path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />',
        ],
        'shopping-cart' => [
            'label' => 'Compras',
            'path' =>
                '<path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />',
        ],
        'tag' => [
            'label' => 'Categorías',
            'path' =>
                '<path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z" />',
        ],
        'exclamation' => [
            'label' => 'Stock Bajo / Alertas',
            'path' =>
                '<path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />',
        ],
        'receipt-tax' => [
            'label' => 'Facturación',
            'path' =>
                '<path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z" />',
        ],
        'color-swatch' => [
            'label' => 'Materiales / Pinturas',
            'path' =>
                '<path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />',
        ],
        'scale' => [
            'label' => 'Medidas / Peso',
            'path' =>
                '<path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3" />',
        ],
        'qrcode' => [
            'label' => 'Código de Barras / QR',
            'path' =>
                '<path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />',
        ],
        'office-building' => [
            'label' => 'Sucursal',
            'path' =>
                '<path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />',
        ],
        'heart' => [
            'label' => 'Salud / Cardiología',
            'path' =>
                '<path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />',
        ],
        'user-group' => [
            'label' => 'Pacientes',
            'path' =>
                '<path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />',
        ],
        'clipboard-list' => [
            'label' => 'Historia Clínica',
            'path' =>
                '<path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />',
        ],
        'beaker' => [
            'label' => 'Laboratorio',
            'path' =>
                '<path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />',
        ],
        'support' => [
            'label' => 'Urgencias / Soporte',
            'path' =>
                '<path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" />',
        ],
        'medication' => [
            'label' => 'Medicamentos / Farmacia',
            'path' =>
                '<path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />',
        ],
        'chart-line' => [
            'label' => 'Signos Vitales',
            'path' =>
                '<path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />',
        ],
        'identification' => [
            'label' => 'Turnos / Admisión',
            'path' =>
                '<path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" />',
        ],
        'ban' => [
            'label' => 'Alergias / Restricciones',
            'path' =>
                '<path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />',
        ],
    ];

    // Detecta si $value es un path SVG o un key
    $currentPath = '';
    $currentLabel = 'Selecciona un ícono';

    if ($value) {
        if (isset($iconList[$value])) {
            // Es un key (ej. 'home')
            $currentPath = $iconList[$value]['path'];
            $currentLabel = $iconList[$value]['label'];
        } else {
            // Es un path SVG completo (viene de la BD)
            $currentPath = $value;
            foreach ($iconList as $ic) {
                if ($ic['path'] === $value) {
                    $currentLabel = $ic['label'];
                    break;
                }
            }
        }
    }
@endphp

<div>
    @if ($label)
        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">
            {{ $label }}
            @if ($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif

    @switch($type)
        @case('text')
        @case('email')

        @case('password')
        @case('number')
            <input type="{{ $type }}" name="{{ $name }}" value="{{ $value }}"
                placeholder="{{ $placeholder }}" @if ($required) required @endif
                @if ($type === 'number') @php $intMin = ['quantity' => 1, 'min_quantity' => 1, 'stock' => 0, 'factor' => 1][$name] ?? null; @endphp @if ($intMin !== null) step="1" min="{{ $intMin }}" inputmode="numeric" onkeydown="if (['.', ',', 'e', 'E', '-', '+'].includes(event.key)) event.preventDefault()" @else step="any" @endif @endif
                class="w-full px-3 h-10 text-sm rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 @error($name) border-red-500 @enderror">
        @break

        @case('textarea')
            <textarea name="{{ $name }}" placeholder="{{ $placeholder }}" rows="{{ $rows }}"
                @if ($required) required @endif
                class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 @error($name) border-red-500 @enderror">{{ $value }}</textarea>
        @break

        @case('select')
            <select name="{{ $name }}" @if ($required) required @endif
                class="w-full px-3 h-10 text-sm rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 @error($name) border-red-500 @enderror">
                <option value="">-- Selecciona una opción --</option>
                @foreach ($options as $optionValue => $optionLabel)
                    <option value="{{ $optionValue }}" @if ($value == $optionValue) selected @endif>
                        {{ $optionLabel }}
                    </option>
                @endforeach
            </select>
        @break

        @case('checkbox-grid')
            <div
                class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3 max-h-48 overflow-y-auto p-2 border border-gray-200 dark:border-gray-700 rounded-lg">
                @foreach ($options as $optionValue => $optionLabel)
                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input type="checkbox" name="{{ $name }}[]" value="{{ $optionValue }}"
                            class="rounded border-gray-300 text-emerald-600 shadow-sm focus:border-emerald-300 focus:ring focus:ring-emerald-200 focus:ring-opacity-50">
                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ $optionLabel }}</span>
                    </label>
                @endforeach
            </div>
        @break

        @case('checkbox')
            <input type="checkbox" name="{{ $name }}" value="1" @if ($value) checked @endif
                class="w-4 h-4 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 cursor-pointer">
        @break

        @case('radio')
            <div class="space-y-2">
                @foreach ($options as $optionValue => $optionLabel)
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="{{ $name }}" value="{{ $optionValue }}"
                            @if ($value == $optionValue) checked @endif
                            class="w-4 h-4 border-gray-300 text-emerald-600 focus:ring-emerald-500">
                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ $optionLabel }}</span>
                    </label>
                @endforeach
            </div>
        @break

        @case('date')
        @case('datetime-local')

        @case('time')
            <input type="{{ $type }}" name="{{ $name }}" value="{{ $value }}"
                @if ($required) required @endif
                class="w-full px-3 h-10 text-sm rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 @error($name) border-red-500 @enderror">
        @break

        @case('file')
            <input type="file" name="{{ $name }}" @if ($required) required @endif
                class="w-full px-3 h-10 text-sm rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 @error($name) border-red-500 @enderror">
        @break

        {{-- ── ICON PICKER ─────────────────────────────────────────────── --}}
        @case('icon-picker')
            {{-- Input hidden: guarda el PATH SVG completo --}}
            <input type="hidden" name="{{ $name }}" id="icon-picker-value-{{ $name }}"
                value="{{ $currentPath }}">

            {{-- Preview del ícono seleccionado --}}
            <div
                class="flex items-center gap-2 mb-2 px-3 py-2 rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-700 dark:text-gray-300 text-xs">
                <svg class="w-5 h-5 text-emerald-500 shrink-0" id="icon-picker-svg-{{ $name }}" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor">
                    @if ($currentPath)
                        {!! $currentPath !!}
                    @else
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    @endif
                </svg>
                <span id="icon-picker-label-{{ $name }}" class="font-medium">
                    {{ $currentLabel }}
                </span>
            </div>

            {{-- Grilla de íconos --}}
            <div
                class="grid grid-cols-5 sm:grid-cols-8 md:grid-cols-10 gap-1.5 p-2 border border-gray-200 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-slate-800/50 max-h-48 overflow-y-auto">
                @foreach ($iconList as $iconKey => $icon)
                    @php
                        $isSelected = $currentPath === $icon['path'];
                    @endphp
                    <button type="button" data-icon-label="{{ $icon['label'] }}" data-icon-path="{{ $icon['path'] }}"
                        data-target="{{ $name }}" title="{{ $icon['label'] }}"
                        class="icon-picker-btn flex items-center justify-center w-9 h-9 rounded-lg border transition-all
                            {{ $isSelected
                                ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400'
                                : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-slate-800 text-gray-500 dark:text-gray-400 hover:border-emerald-400 hover:text-emerald-500 hover:bg-emerald-50 dark:hover:bg-emerald-900/20' }}">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            {!! $icon['path'] !!}
                        </svg>
                    </button>
                @endforeach
            </div>

            <p class="text-[10px] text-gray-400 dark:text-gray-500 mt-1">
                Hacé clic en un ícono para seleccionarlo.
            </p>
        @break

    @endswitch

    @if ($help)
        <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-1">{{ $help }}</p>
    @endif

    @error($name)
        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
    @enderror
</div>

{{-- Script del icon-picker --}}
@if ($type === 'icon-picker')
    @once
        @push('scripts')
            <script>
                document.addEventListener('click', function(e) {
                    const btn = e.target.closest('.icon-picker-btn');
                    if (!btn) return;

                    const target = btn.dataset.target;
                    const iconLabel = btn.dataset.iconLabel;
                    const iconPath = btn.dataset.iconPath; // PATH SVG completo

                    // Guarda el PATH completo en el input hidden
                    document.getElementById('icon-picker-value-' + target).value = iconPath;

                    // Actualiza el SVG del preview
                    document.getElementById('icon-picker-svg-' + target).innerHTML = iconPath;

                    // Actualiza el label del preview
                    document.getElementById('icon-picker-label-' + target).textContent = iconLabel;

                    // Quita selección de todos los botones del mismo grupo
                    document.querySelectorAll(`.icon-picker-btn[data-target="${target}"]`).forEach(b => {
                        b.classList.remove(
                            'border-emerald-500', 'bg-emerald-50', 'dark:bg-emerald-900/30',
                            'text-emerald-600', 'dark:text-emerald-400'
                        );
                        b.classList.add(
                            'border-gray-200', 'dark:border-gray-700',
                            'bg-white', 'dark:bg-slate-800',
                            'text-gray-500', 'dark:text-gray-400'
                        );
                    });

                    // Marca el botón clickeado
                    btn.classList.remove(
                        'border-gray-200', 'dark:border-gray-700',
                        'bg-white', 'dark:bg-slate-800',
                        'text-gray-500', 'dark:text-gray-400'
                    );
                    btn.classList.add(
                        'border-emerald-500', 'bg-emerald-50', 'dark:bg-emerald-900/30',
                        'text-emerald-600', 'dark:text-emerald-400'
                    );
                });
            </script>
        @endpush
    @endonce
@endif
