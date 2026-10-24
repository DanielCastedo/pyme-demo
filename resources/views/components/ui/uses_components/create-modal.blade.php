<?php

// Definición Completa de los Campos ($fields)
$fields = [
    // 1. Input de Texto Estándar (Ocupa 1 columna)
    [
        'name'        => 'name',
        'label'       => 'Nombre del Grupo',
        'type'        => 'text',
        'placeholder' => 'Ej. Grupo 3º A',
        'required'    => true,
    ],

    // 2. Input numérico / Id o similar
    [
        'name'        => 'code',
        'label'       => 'Código de Carpeta',
        'type'        => 'number',
        'placeholder' => '1001',
        'help'        => 'Debe ser un código numérico único.', // Texto de ayuda abajo del input
    ],

    // 3. Menú Desplegable / Select (Ocupa ambas columnas usando fullWidth)
    [
        'name'      => 'subject_id',
        'label'     => 'Materia Asignada',
        'type'      => 'select',
        'fullWidth' => true, // Fuerza a que tome todo el ancho del modal
        'required'  => true,
        'options'   => [     // Opciones llave => valor para el select
            ''  => 'Selecciona una materia...',
            '1' => 'Matemáticas',
            '2' => 'Programación Web',
        ],
    ],

    // 4. Área de Texto / Textarea (Ocupa ambas columnas y define renglones)
    [
        'name'      => 'management',
        'label'     => 'Detalles de la Gestión',
        'type'      => 'textarea',
        'fullWidth' => true,
        'rows'      => 4, // Altura inicial del textarea
        'value'     => 'Gestión académica iniciada para el ciclo actual.', // Valor precargado (Ideal para edición)
    ],

    // 5. Input de Archivo (Requiere activar hasFile en el modal)
    [
        'name'  => 'attached_file',
        'label' => 'Documento Adjunto (PDF o ZIP)',
        'type'  => 'file',
    ],
];

// Invocación del Componente en Blade
<x-ui.crud-modal
    id="modal-create-record"
    title="Nueva Asignación"
    subtitle="Ingresa los datos correspondientes para la materia, grupo y gestión."
    :action="route('records.store')"
    :fields="$fields"
    submitText="Registrar Asignación"
    :columns="2"
    :hasFile="true"
/>
