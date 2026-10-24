{{-- Acciones de una venta. Recibe: $order y $cancelMessage (texto de confirmación al anular) --}}
<div class="flex flex-wrap items-center gap-2">
    @can('create', \App\Models\Order::class)
        <a href="{{ route('sales.create') }}">
            <x-ui.button type="button" variant="primary">Nueva venta</x-ui.button>
        </a>
    @endcan

    @can('cancel', $order)
        <form action="{{ route('sales.cancel', $order) }}" method="POST"
            onsubmit="return confirm(@js($cancelMessage))">
            @csrf
            <x-ui.button type="submit" variant="outline">Anular venta</x-ui.button>
        </form>
    @endcan
</div>
