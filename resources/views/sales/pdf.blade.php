@php
    // Cobros vigentes de la venta y las transacciones de caja que generaron (se muestran junto al N° de recibo)
    $payments = $sale->payments->where('canceled', false);
    $transactions = $payments->map(fn ($p) => $p->transaction?->number)->filter()->unique()->values();
    // El QR es un link al mismo recibo (sin sesión): el cliente lo escanea y lo ve desde su celular.
    $qrUrl = \Illuminate\Support\Facades\URL::signedRoute('public.receipts.sale', ['sale' => $sale->id]);
    $gross = (float) $sale->items->sum(fn ($i) => (float) $i->unit_price * (float) $i->quantity);
@endphp

<x-ui.receipt-layout
    :number="$sale->id"
    :subtitle="$sale->isCredit() ? '(Venta a crédito)' : '(Venta al contado)'"
    :date="$sale->created_at ? $sale->created_at->format('d/m/Y h:i A') : date('d/m/Y')"
    :company="$company"
    :branch="$sale->branch"
    :warehouse="$sale->warehouse->name ?? null"
    :customer-name="$sale->customer_label"
    :customer-document="$sale->customer?->document"
    :customer-code="$sale->customer?->id"
    :reference="$transactions->isNotEmpty() ? ['label' => 'TRANSACCIÓN', 'value' => $transactions->implode(' · ')] : null"
    :extra="[['Vendedor', $sale->user->name ?? 'Ventas'], ['Forma de pago', $sale->isCredit() ? 'Crédito' : 'Contado']]"
    :total="$sale->amount"
    :qr="$qrUrl"
    qr-caption="Ver recibo">

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
            @forelse ($sale->items as $item)
                <tr>
                    <td class="rc-c">{{ $item->product->sku ?? '—' }}</td>
                    <td class="rc-c">{{ number_format((float) $item->quantity, 0) }}</td>
                    <td class="rc-c">{{ $item->productUnit->name ?? 'Unidad' }}</td>
                    <td>
                        {{ $item->product->name ?? '—' }}
                        @if ($item->warehouse_id && $item->warehouse_id !== $sale->warehouse_id)
                            <span style="font-size: 7pt; color: #666;">({{ $item->warehouse->name ?? 'otro almacén' }})</span>
                        @endif
                    </td>
                    <td class="rc-r">{{ number_format((float) $item->unit_price, 2) }}</td>
                    <td class="rc-r">{{ number_format((float) $item->discount, 2) }}</td>
                    <td class="rc-r rc-b">{{ number_format((float) $item->subtotal, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="rc-c" style="padding: 12px;">No se registraron productos en esta venta.</td></tr>
            @endforelse
        </tbody>
    </table>

    {{-- Totales --}}
    <table class="rc-totals">
        @if ((float) $sale->discount_total > 0)
            <tr><td class="k">SUBTOTAL BS</td><td class="v">{{ number_format($gross, 2) }}</td></tr>
            <tr><td class="k">DESCUENTO BS</td><td class="v">{{ number_format((float) $sale->discount_total, 2) }}</td></tr>
        @endif
        <tr class="big"><td class="k">TOTAL BS</td><td class="v">{{ number_format((float) $sale->amount, 2) }}</td></tr>
        @if ($sale->isCredit())
            <tr><td class="k">A CUENTA BS</td><td class="v">{{ number_format((float) $sale->paid_amount, 2) }}</td></tr>
            <tr class="big"><td class="k">SALDO BS</td><td class="v">{{ number_format((float) $sale->balance, 2) }}</td></tr>
        @endif
    </table>

    @if ($sale->isCredit() && $sale->due_date)
        <div style="text-align: right; font-size: 8pt; margin-bottom: 4px;">Vencimiento: <strong>{{ $sale->due_date->format('d/m/Y') }}</strong></div>
    @endif

    {{-- Cobros registrados --}}
    @if ($payments->isNotEmpty())
        <div style="font-size: 7.5pt; font-weight: bold; text-transform: uppercase; margin: 8px 0 3px;">Cobros registrados</div>
        <table class="rc-items">
            <thead>
                <tr>
                    <th style="width: 20%;">Fecha</th>
                    <th style="width: 20%;">Transacción</th>
                    <th>Método de pago</th>
                    <th style="width: 20%;">Caja</th>
                    <th style="width: 14%;">Monto (Bs.)</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($payments as $p)
                    <tr>
                        <td class="rc-c">{{ $p->created_at->format('d/m/Y H:i') }}</td>
                        <td class="rc-c">{{ $p->transaction->number ?? '—' }}</td>
                        <td>{{ $p->transaction->paymentMethod->name ?? '—' }}</td>
                        <td>{{ $p->transaction->cashbox->name ?? '—' }}</td>
                        <td class="rc-r rc-b">{{ number_format((float) $p->amount, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if ($sale->memo)
        <div class="rc-notes">
            <div class="h">Observaciones</div>
            {{ $sale->memo }}
        </div>
    @endif

    {{-- Firmas: solo en ventas a crédito (queda constancia de la deuda) --}}
    @if ($sale->isCredit())
        <x-slot:footer>
            <x-ui.pdf-signatures
                :company="$company->name ?? config('app.name')"
                :customer="$sale->customer_label"
            />
        </x-slot:footer>
    @endif

</x-ui.receipt-layout>
