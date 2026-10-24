@php
    $items = [
        ['label' => 'Producto (Kit)', 'value' => $productComponent->product->name ?? '—'],
        ['label' => 'Componente', 'value' => $productComponent->component->name ?? '—'],
        ['label' => 'Cantidad', 'value' => $productComponent->quantity],
    ];
@endphp

<x-ui.show-page title="Detalle de Componente" category="Inventario" :backRoute="route('product-components.index')"
    :editRoute="route('product-components.edit', $productComponent)" :items="$items" :columns="2" />
