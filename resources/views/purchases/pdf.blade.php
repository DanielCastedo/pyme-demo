@php
    // El QR es un link al mismo comprobante (sin sesión): se escanea y se ve desde el celular.
    $qrUrl = \Illuminate\Support\Facades\URL::signedRoute('public.receipts.purchase', ['purchase' => $purchase->id]);
    $destinos = $purchase->isDistributed()
        ? $purchase->receipts->map(fn ($r) => $r->warehouse->name)->implode(', ')
        : $purchase->warehouse->name;
@endphp

<x-ui.receipt-layout
    title="COMPROBANTE DE COMPRA"
    document-label="COMPRA"
    :subtitle="$purchase->isCredit() ? '(Compra a crédito)' : '(Compra al contado)'"
    :number="$purchase->id"
    :date="$purchase->date ? $purchase->date->format('d/m/Y') : date('d/m/Y')"
    :company="$company"
    :branch="$purchase->branch"
    :warehouse="$destinos"
    :customer-name="$purchase->supplier_label"
    :customer-document="$purchase->supplier?->document"
    :customer-code="$purchase->supplier?->id"
    party-label="Proveedor"
    party-code-label="Cod. Proveedor"
    :reference="$purchase->invoice_number ? ['label' => 'FACTURA PROV.', 'value' => $purchase->invoice_number] : null"
    :extra="[['Registrado por', $purchase->user->name ?? 'Sistema'], ['Forma de pago', $purchase->isCredit() ? 'Crédito' : 'Contado']]"
    :total="$purchase->amount"
    :qr="$qrUrl"
    qr-caption="Ver comprobante"
    legend="ESTE DOCUMENTO ES UN COMPROBANTE INTERNO DE INGRESO DE MERCADERÍA AL INVENTARIO Y NO ES UNA FACTURA NI DA DERECHO A CRÉDITO FISCAL.">

    {{-- Detalle --}}
    <table class="rc-items">
        <thead>
            <tr>
                <th style="width: 12%;">Código producto</th>
                <th style="width: 9%;">Cantidad</th>
                <th style="width: 12%;">Presentación</th>
                <th>Descripción</th>
                <th style="width: 16%;">Destino</th>
                <th style="width: 12%;">Costo unitario</th>
                <th style="width: 12%;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($purchase->items as $item)
                <tr>
                    <td class="rc-c">{{ $item->product->sku ?? '—' }}</td>
                    <td class="rc-c">{{ number_format((float) $item->quantity, 0) }}</td>
                    <td class="rc-c">{{ $item->productUnit->name ?? 'Unidad' }}</td>
                    <td>
                        {{ $item->product->name ?? '—' }}
                        @if ($item->created_at->gt($purchase->created_at->copy()->addMinute()))
                            <br><span style="font-size: 6.8pt; color: #059669;">agregado el {{ $item->created_at->format('d/m/Y') }}</span>
                        @endif
                    </td>
                    <td class="rc-c">
                        @forelse ($item->distributions as $d)
                            {{ $d->warehouse->name }}: {{ (float) $d->quantity }}@if (! $loop->last)<br>@endif
                        @empty
                            {{ $purchase->warehouse->name }}
                        @endforelse
                    </td>
                    <td class="rc-r">{{ number_format((float) $item->unit_cost, 2) }}</td>
                    <td class="rc-r rc-b">{{ number_format((float) $item->subtotal, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="rc-c" style="padding: 12px;">No se registraron productos en esta compra.</td></tr>
            @endforelse
        </tbody>
    </table>

    {{-- Totales --}}
    <table class="rc-totals">
        <tr class="big"><td class="k">TOTAL BS</td><td class="v">{{ number_format((float) $purchase->amount, 2) }}</td></tr>
        @if ($purchase->isCredit())
            <tr><td class="k">PAGADO BS</td><td class="v">{{ $purchase->paid_label }}</td></tr>
            <tr class="big"><td class="k">SALDO BS</td><td class="v">{{ $purchase->balance_label }}</td></tr>
        @endif
    </table>

    @if ($purchase->isCredit() && $purchase->due_date)
        <div style="text-align: right; font-size: 8pt; margin-bottom: 4px;">Vencimiento: <strong>{{ $purchase->due_date->format('d/m/Y') }}</strong></div>
    @endif

    @if (! $purchase->isCredit())
        <div style="font-size: 8pt; margin: 4px 0 8px;">
            @if ($purchase->transaction)
                <strong>Pago:</strong> {{ $purchase->transaction->paymentMethod->name ?? '—' }} · {{ $purchase->transaction->cashbox->name }} · {{ $purchase->transaction->number }}{{ $purchase->transaction->canceled ? ' (anulada)' : '' }}
            @else
                <strong>Pago:</strong> No se registró salida de caja.
            @endif
        </div>
    @endif

    @if ($purchase->note)
        <div class="rc-notes">
            <div class="h">Observaciones</div>
            {{ $purchase->note }}
        </div>
    @endif

</x-ui.receipt-layout>
