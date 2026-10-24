@php
    // El QR lleva el número de la proforma (COT-0001): no hay transacción de caja en una cotización
    $qrText = $quotation->number;
@endphp

<x-ui.receipt-layout
    title="PROFORMA"
    document-label="PROFORMA"
    subtitle="(Cotización de venta)"
    :number="$quotation->number"
    :date="$quotation->formatted_date"
    :company="$company"
    :branch="$quotation->user?->branch"
    :customer-name="$quotation->customer->name ?? 'Cliente Mostrador'"
    :customer-document="$quotation->customer->document ?? null"
    :customer-code="$quotation->customer?->id"
    :extra="[['Vendedor', $quotation->user->name ?? 'Ventas'], ['Vigencia hasta', $quotation->formatted_expires_at], ['Teléfono', $quotation->customer->phone ?? 'S/N'], ['Estado', $quotation->status_label]]"
    :total="$quotation->amount"
    :qr="$qrText"
    :qr-caption="$qrText"
    legend="ESTA PROFORMA ES UNA COTIZACIÓN REFERENCIAL: LOS PRECIOS TIENEN VALIDEZ HASTA LA FECHA INDICADA Y ESTÁN SUJETOS A DISPONIBILIDAD DE INVENTARIO. NO DA DERECHO A CRÉDITO FISCAL.">

    {{-- Detalle --}}
    <table class="rc-items">
        <thead>
            <tr>
                <th style="width: 14%;">Código producto / servicio</th>
                <th style="width: 10%;">Cantidad</th>
                <th style="width: 13%;">Unidad de medida</th>
                <th>Descripción</th>
                <th style="width: 14%;">Precio unitario</th>
                <th style="width: 14%;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($quotation->items as $item)
                <tr>
                    <td class="rc-c">{{ $item->product->sku ?? '—' }}</td>
                    <td class="rc-c">{{ number_format((float) $item->quantity, 0) }}</td>
                    <td class="rc-c">{{ $item->productUnit->name ?? 'Unidad' }}</td>
                    <td>{{ $item->product->name ?? '—' }}</td>
                    <td class="rc-r">{{ number_format((float) $item->unit_price, 2) }}</td>
                    <td class="rc-r rc-b">{{ number_format((float) $item->subtotal, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="rc-c" style="padding: 12px;">No se registraron productos en esta proforma.</td></tr>
            @endforelse
        </tbody>
    </table>

    {{-- Total --}}
    <table class="rc-totals">
        <tr class="big"><td class="k">TOTAL BS</td><td class="v">{{ number_format((float) $quotation->amount, 2) }}</td></tr>
    </table>

    <div class="rc-notes">
        <div class="h">Términos y condiciones de la cotización</div>
        {{ $quotation->notes ?: 'Los precios están expresados en Bolivianos (Bs.) y tienen validez hasta la fecha de vigencia indicada. Los productos están sujetos a disponibilidad de inventario al momento de concretar la compra.' }}
    </div>

    {{-- Firmas --}}
    <x-slot:footer>
        <x-ui.pdf-signatures
            :company="$company->name ?? config('app.name')"
            :customer="$quotation->customer->name ?? 'Cliente'"
            company-label="Emitido por"
            customer-label="Aceptación del cliente"
        />
    </x-slot:footer>

</x-ui.receipt-layout>
