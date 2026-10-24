@php
    $items = [
        ['label' => 'Producto', 'value' => $productUnit->product->name ?? '—'],
        ['label' => 'Nombre de la Unidad', 'value' => $productUnit->name],
        ['label' => 'Factor de Conversión', 'value' => $productUnit->factor],
        ['label' => 'Precio de Venta', 'value' => number_format((float) $productUnit->price, 2)],
        ['label' => 'Código de Barras', 'value' => $productUnit->barcode ?? '—', 'mono' => true],
        ['label' => 'Unidad Base', 'value' => $productUnit->is_base ? 'Sí' : 'No'],
    ];
@endphp

<x-ui.show-page title="Detalle de Unidad de Producto" category="Inventario" :backRoute="route('product-units.index')"
    :editRoute="auth()->user()->can('update', $productUnit) ? route('product-units.edit', $productUnit) : ''" :items="$items" :columns="2" />
