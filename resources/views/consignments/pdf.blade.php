@php
    // El QR es un link al mismo recibo (sin sesión): el cliente lo escanea y lo ve desde su celular.
    $qrUrl = \Illuminate\Support\Facades\URL::signedRoute('public.receipts.consignment', ['consignment' => $consignment->id]);
    $sentValue = (float) $consignment->sent_value;
@endphp

<x-ui.receipt-layout
    :number="$consignment->id"
    subtitle="(Entrega de mercadería en consignación)"
    :date="$consignment->created_at ? $consignment->created_at->format('d/m/Y h:i A') : date('d/m/Y')"
    :company="$company"
    :branch="$consignment->branch"
    :warehouse="$consignment->warehouse->name ?? null"
    :customer-name="$consignment->customer->name ?? null"
    :customer-document="$consignment->customer->document ?? null"
    :customer-code="$consignment->customer?->id"
    :extra="[['Fecha límite', $consignment->due_label], ['Estado', $consignment->status_label]]"
    :total="$sentValue"
    :qr="$qrUrl"
    qr-caption="Ver recibo"
    legend="ESTE DOCUMENTO ES UN RECIBO DE LA MERCADERÍA ENTREGADA EN CONSIGNACIÓN, SUJETA A LIQUIDACIÓN O DEVOLUCIÓN EN PERFECTO ESTADO. NO DA DERECHO A CRÉDITO FISCAL.">

    {{-- Detalle --}}
    <table class="rc-items">
        <thead>
            <tr>
                <th style="width: 12%;">Código producto / servicio</th>
                <th style="width: 9%;">Entregado</th>
                <th style="width: 8%;">Vendido</th>
                <th style="width: 9%;">Pendiente</th>
                <th style="width: 11%;">Unidad de medida</th>
                <th>Descripción</th>
                <th style="width: 11%;">Precio acordado</th>
                <th style="width: 11%;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($consignment->items as $item)
                <tr>
                    <td class="rc-c">{{ $item->product->sku ?? '—' }}</td>
                    <td class="rc-c rc-b">{{ number_format((float) $item->quantity_sent, 0) }}</td>
                    <td class="rc-c">{{ number_format((float) $item->quantity_sold, 0) }}</td>
                    <td class="rc-c">{{ number_format((float) $item->pending, 0) }}</td>
                    <td class="rc-c">{{ $item->productUnit->name ?? 'Unidad' }}</td>
                    <td>{{ $item->product->name ?? '—' }}</td>
                    <td class="rc-r">{{ number_format((float) $item->unit_price, 2) }}</td>
                    <td class="rc-r rc-b">{{ number_format((float) $item->unit_price * (float) $item->quantity_sent - (float) $item->discount, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="rc-c" style="padding: 12px;">No se registraron productos en esta consignación.</td></tr>
            @endforelse
        </tbody>
    </table>

    {{-- Totales --}}
    <table class="rc-totals">
        <tr class="big"><td class="k">TOTAL ENTREGADO BS</td><td class="v">{{ $consignment->sent_value_label }}</td></tr>
        <tr><td class="k">VENDIDO BS</td><td class="v">{{ $consignment->sold_value_label }}</td></tr>
        <tr class="big"><td class="k">PENDIENTE BS</td><td class="v">{{ $consignment->pending_value_label }}</td></tr>
    </table>

    @if ($consignment->note)
        <div class="rc-notes">
            <div class="h">Condiciones y notas</div>
            {{ $consignment->note }}
        </div>
    @endif

    {{-- Firmas --}}
    <x-slot:footer>
        <x-ui.pdf-signatures
            :company="$company->name ?? config('app.name')"
            :customer="$consignment->customer->name ?? 'Cliente receptor'"
            companyLabel="Entregado por"
            customerLabel="Recibí conforme"
        />
    </x-slot:footer>

</x-ui.receipt-layout>
