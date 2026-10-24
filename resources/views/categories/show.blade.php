@php
    $items = [
        ['label' => 'Nombre', 'value' => $category->name],
        ['label' => 'Descripción', 'value' => $category->description ?? '—', 'fullWidth' => true],
    ];
@endphp

<x-ui.show-page title="Detalle de Categoría" category="Inventario" :backRoute="route('categories.index')"
    :editRoute="auth()->user()->can('update', $category) ? route('categories.edit', $category) : ''" :items="$items" :columns="2" />
