@php
    // El QR es un link al mismo recibo (sin sesión): el cliente lo escanea y lo ve desde su celular.
    $qrUrl = \Illuminate\Support\Facades\URL::signedRoute('public.receipts.pedido', ['pedido' => $pedido->id]);
    $gross = (float) $pedido->items->sum(fn ($i) => (float) $i->unit_price * (float) $i->quantity);
@endphp

<x-ui.receipt-layout
    :number="$pedido->id"
    subtitle="(Pedido en espera de entrega)"
    :date="$pedido->created_at ? $pedido->created_at->format('d/m/Y h:i A') : date('d/m/Y')"
    :company="$company"
    :branch="$pedido->branch"
    :warehouse="$pedido->warehouse->name ?? null"
    :customer-name="$pedido->customer_label"
    :customer-document="$pedido->customer?->document"
    :customer-code="$pedido->customer?->id"
    :extra="[['Fecha de entrega', $pedido->delivery_label], ['Vendedor', $pedido->user->name ?? 'Ventas']]"
    :total="$pedido->amount"
    :qr="$qrUrl"
    qr-caption="Ver recibo"
    legend="ESTE DOCUMENTO ES UN RECIBO DEL PEDIDO REGISTRADO: LA VENTA SE CONFIRMA Y SE COBRA AL MOMENTO DE LA ENTREGA. NO DA DERECHO A CRÉDITO FISCAL.">

    {{-- Detalle --}}
    <table class="rc-items">
        <thead>
            <tr>
                <th style="width: 13%;">Código producto / servicio</th>
                <th style="width: 9%;">Cantidad</th>
                <th style="width: 12%;">Unidad de medida</th>
                <th>Descripción</th>
                <th style="width: 12%;">Precio unitario</th>
                <th style="width: 10%;">Descuento</th>
                <th style="width: 12%;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($pedido->items as $item)
                <tr>
                    <td class="rc-c">{{ $item->product->sku ?? '—' }}</td>
                    <td class="rc-c">{{ number_format((float) $item->quantity, 0) }}</td>
                    <td class="rc-c">{{ $item->productUnit->name ?? 'Unidad' }}</td>
                    <td>{{ $item->product->name ?? '—' }}</td>
                    <td class="rc-r">{{ number_format((float) $item->unit_price, 2) }}</td>
                    <td class="rc-r">{{ number_format((float) $item->discount, 2) }}</td>
                    <td class="rc-r rc-b">{{ number_format((float) $item->subtotal, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="rc-c" style="padding: 12px;">No se registraron productos en este pedido.</td></tr>
            @endforelse
        </tbody>
    </table>

    {{-- Totales --}}
    <table class="rc-totals">
        @if ((float) $pedido->discount_total > 0)
            <tr><td class="k">SUBTOTAL BS</td><td class="v">{{ number_format($gross, 2) }}</td></tr>
            <tr><td class="k">DESCUENTO BS</td><td class="v">{{ number_format((float) $pedido->discount_total, 2) }}</td></tr>
        @endif
        <tr class="big"><td class="k">TOTAL BS</td><td class="v">{{ number_format((float) $pedido->amount, 2) }}</td></tr>
    </table>

    @if ($pedido->memo)
        <div class="rc-notes">
            <div class="h">Observaciones</div>
            {{ $pedido->memo }}
        </div>
    @endif

</x-ui.receipt-layout>
