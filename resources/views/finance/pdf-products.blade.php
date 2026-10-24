<x-ui.pdf-layout
    title="REPORTE DE GANANCIAS POR PRODUCTO"
    :number="date('d/m/Y')"
    :date="($filters['from'] ?? 'Inicio') . ' al ' . ($filters['to'] ?? 'Hoy')"
    :company="$company"
    footerNote="Reporte analítico de unidades vendidas, costos FIFO y márgenes de ganancia.">

    {{-- Resumen de totales generales --}}
    <x-ui.pdf-info-grid :rows="[
        [
            ['label' => 'Total Ventas', 'value' => 'Bs. ' . number_format((float) ($summary['sales'] ?? 0), 2), 'width' => '33%', 'highlight' => true],
            ['label' => 'Costo Mercadería Vendida', 'value' => 'Bs. ' . number_format((float) ($summary['cost'] ?? 0), 2), 'width' => '33%'],
            ['label' => 'Ganancia Bruta', 'value' => 'Bs. ' . number_format((float) ($summary['gross'] ?? 0), 2) . ' (' . ($summary['margin'] ?? 0) . '%)', 'width' => '34%', 'highlight' => true],
        ],
    ]" />

    {{-- Tabla de Productos --}}
    <table class="items-table" style="margin-top: 15px;">
        <thead>
            <tr>
                <th style="width: 5%;" class="text-center">#</th>
                <th style="width: 35%;">Producto</th>
                <th style="width: 12%;" class="text-center">Código</th>
                <th style="width: 12%;" class="text-center">Cant. Vendida</th>
                <th style="width: 12%;" class="text-right">Ventas (Bs.)</th>
                <th style="width: 12%;" class="text-right">Costo (Bs.)</th>
                <th style="width: 12%;" class="text-right">Ganancia (Bs.)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($products as $idx => $p)
                @php
                    $profit = (float) $p->revenue - (float) $p->cost;
                @endphp
                <tr>
                    <td class="text-center" style="color: #4b5563;">{{ $idx + 1 }}</td>
                    <td><strong>{{ $p->name }}</strong></td>
                    <td class="text-center">{{ $p->sku ?: '—' }}</td>
                    <td class="text-center font-bold">{{ rtrim(rtrim(number_format((float) $p->sold_qty, 2), '0'), '.') }}</td>
                    <td class="text-right">{{ number_format((float) $p->revenue, 2) }}</td>
                    <td class="text-right">{{ number_format((float) $p->cost, 2) }}</td>
                    <td class="text-right font-bold">{{ number_format($profit, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center" style="padding: 15px; color: #4b5563;">
                        No se registraron ventas de productos en este período.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="total-box">
        <tr class="total-row">
            <td class="text-right">GANANCIA TOTAL:</td>
            <td class="text-right">Bs. {{ number_format((float) ($summary['gross'] ?? 0), 2) }}</td>
        </tr>
    </table>

</x-ui.pdf-layout>
