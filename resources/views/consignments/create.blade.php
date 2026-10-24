<x-app-layout>
    @include('sales.partials.pos', [
        'mode' => 'consignment',
        'action' => route('consignments.store'),
        'title' => 'Nueva Consignación',
        'description' => 'Entrega mercadería a un cliente para que la venda (con el precio y descuento que acuerden). Sale del almacén ahora y no se cobra hasta que el cliente liquide.',
        'submitLabel' => 'Registrar consignación',
    ])
</x-app-layout>
