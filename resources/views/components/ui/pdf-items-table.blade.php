@props([
    'items' => [],
    'amount' => 0,
    'discount' => 0,
])

{{-- Tabla de Productos --}}
<table class="items-table">
    <thead>
        <tr>
            <th style="width: 6%;" class="text-center">#</th>
            <th style="width: 44%;">Descripción del Producto</th>
            <th style="width: 15%;" class="text-center">Presentación</th>
            <th style="width: 10%;" class="text-center">Cant.</th>
            <th style="width: 12%;" class="text-right">P. Unit. (Bs.)</th>
            <th style="width: 13%;" class="text-right">Subtotal (Bs.)</th>
        </tr>
    </thead>
    <tbody>
        @forelse($items as $idx => $item)
            <tr>
                <td class="text-center" style="color: #6b7280;">{{ $idx + 1 }}</td>
                <td>
                    <strong>{{ $item->product->name ?? '—' }}</strong>
                    @if($item->product?->sku)
                        <div style="font-size: 7.5pt; color: #6b7280;">SKU: {{ $item->product->sku }}</div>
                    @endif
                </td>
                <td class="text-center">{{ $item->productUnit->name ?? 'Unidad' }}</td>
                <td class="text-center font-bold">{{ number_format((float) $item->quantity, 0) }}</td>
                <td class="text-right">{{ number_format((float) $item->unit_price, 2) }}</td>
                <td class="text-right font-bold">{{ number_format((float) $item->subtotal, 2) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="text-center" style="padding: 15px; color: #6b7280;">
                    No se registraron productos en este documento.
                </td>
            </tr>
        @endforelse
    </tbody>
</table>

{{-- Bloque de Totales --}}
<table class="total-box">
    <tr>
        <td class="text-right" style="color: #4b5563;">Subtotal:</td>
        <td class="text-right font-bold">Bs. {{ number_format((float) $amount, 2) }}</td>
    </tr>
    <tr>
        <td class="text-right" style="color: #4b5563;">Descuento:</td>
        <td class="text-right">Bs. {{ number_format((float) $discount, 2) }}</td>
    </tr>
    <tr class="total-row">
        <td class="text-right">TOTAL:</td>
        <td class="text-right">Bs. {{ number_format((float) $amount, 2) }}</td>
    </tr>
</table>
