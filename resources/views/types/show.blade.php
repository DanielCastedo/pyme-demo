@php
    $items = [
        ['label' => 'Nombre', 'value' => $type->name],
        ['label' => 'Código', 'value' => $type->code, 'mono' => true],
        ['label' => 'Signo', 'value' => $type->sign ?? '—'],
    ];
@endphp

<x-ui.show-page title="Detalle de Tipo" category="Configuración" :backRoute="route('types.index')"
    :editRoute="auth()->user()->can('update', $type) ? route('types.edit', $type) : ''" :items="$items" :columns="2" />
