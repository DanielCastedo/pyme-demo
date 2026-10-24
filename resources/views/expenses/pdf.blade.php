<x-ui.pdf-layout
    title="COMPROBANTE DE EGRESO / GASTO"
    :number="'#'.$expense->id"
    :date="$expense->date ? $expense->date->format('d/m/Y') : date('d/m/Y')"
    :company="$company"
    footerNote="Comprobante interno de egreso y salida de fondos de caja.">

    {{-- Datos del Gasto --}}
    <x-ui.pdf-info-grid :rows="[
        [
            ['label' => 'Concepto', 'value' => $expense->concept, 'width' => '50%', 'highlight' => true],
            ['label' => 'Categoría de Gasto', 'value' => $expense->category_label, 'width' => '25%'],
            ['label' => 'Estado', 'value' => $expense->status_label, 'width' => '25%'],
        ],
        [
            ['label' => 'Beneficiario / Proveedor', 'value' => $expense->supplier?->name ?? '—', 'width' => '50%'],
            ['label' => 'N° Recibo / Factura', 'value' => $expense->receipt_number ?: 'S/N', 'width' => '25%'],
            ['label' => 'Sucursal', 'value' => $expense->branch->name ?? '—', 'width' => '25%'],
        ],
        [
            ['label' => 'Caja de Origen', 'value' => $expense->transaction->cashbox->name ?? '—', 'width' => '33%'],
            ['label' => 'Método de Pago', 'value' => $expense->transaction->paymentMethod->name ?? 'Efectivo', 'width' => '33%'],
            ['label' => 'Registrado Por', 'value' => $expense->user->name ?? '—', 'width' => '34%'],
        ],
    ]" />

    {{-- Cuadro de Monto --}}
    <table class="items-table" style="margin-top: 15px;">
        <thead>
            <tr>
                <th style="width: 70%;" class="text-left">Descripción del Egreso</th>
                <th style="width: 30%;" class="text-right">Importe Pagado (Bs.)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="padding: 12px 8px;">
                    <strong>{{ $expense->concept }}</strong>
                    @if($expense->description)
                        <div style="margin-top: 4px; color: #4b5563; font-size: 8pt;">{{ $expense->description }}</div>
                    @endif
                </td>
                <td class="text-right font-bold" style="font-size: 11pt; padding: 12px 8px;">
                    Bs. {{ number_format((float) $expense->amount, 2) }}
                </td>
            </tr>
        </tbody>
    </table>

    <table class="total-box">
        <tr class="total-row">
            <td class="text-right">TOTAL EGRESO:</td>
            <td class="text-right">Bs. {{ number_format((float) $expense->amount, 2) }}</td>
        </tr>
    </table>

</x-ui.pdf-layout>
