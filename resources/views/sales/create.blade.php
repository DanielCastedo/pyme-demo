<x-app-layout>
    @include('sales.partials.pos', [
        'mode' => 'sale',
        'action' => route('sales.store'),
        'title' => 'Nueva Venta',
        'description' => 'Busca o escanea los productos, ajusta precio o descuento si hace falta, y cobra.',
    ])
</x-app-layout>