<x-app-layout>
    @include('sales.partials.pos', [
        'mode' => 'order',
        'action' => route('pedidos.store'),
        'title' => 'Nuevo Pedido',
        'description' => 'Deja armado el pedido del cliente con su fecha de entrega. No descuenta stock ni cobra: eso pasa cuando lo confirmes.',
        'submitLabel' => 'Guardar pedido',
    ])
</x-app-layout>
