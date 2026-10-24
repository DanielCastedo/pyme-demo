<x-ui.edit-page title="Editar Tipo" category="Configuración" :backRoute="route('types.index')"
    :action="route('types.update', $type)" method="PUT" submitText="Guardar Cambios" :columns="2" :fields="[
        [
            'type' => 'text',
            'name' => 'name',
            'label' => 'Nombre',
            'value' => $type->name,
            'placeholder' => 'Ej. Venta',
            'required' => true,
        ],
        [
            'type' => 'text',
            'name' => 'code',
            'label' => 'Código',
            'value' => $type->code,
            'placeholder' => 'Ej. VTA',
            'required' => true,
        ],
        [
            'type' => 'text',
            'name' => 'sign',
            'label' => 'Signo',
            'value' => $type->sign,
            'placeholder' => 'Ej. + / -',
            'help' => 'Opcional. Indica si el tipo suma o resta (+, -), déjalo vacío si no aplica.',
        ],
    ]" />
