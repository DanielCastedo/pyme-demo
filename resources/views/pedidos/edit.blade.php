<x-app-layout>
    @include('sales.partials.pos', [
        'mode' => 'order',
        'method' => 'PUT',
        'action' => route('pedidos.update', $pedido),
        'title' => 'Editar Pedido #'.$pedido->id,
        'description' => 'Mientras esté en espera puedes cambiar productos, cantidades, precios, descuentos y la fecha de entrega.',
        'submitLabel' => 'Guardar cambios',
        'initialCustomerId' => $pedido->customer_id,
        'initialWarehouseId' => $pedido->warehouse_id,
        'initialBranchId' => $pedido->branche_id,
        'deliveryDate' => optional($pedido->delivery_date)->format('Y-m-d'),
        'note' => $pedido->memo,
    ])
</x-app-layout>
