<x-ui.pdf-layout
    title="REPORTE FINANCIERO"
    :number="date('d/m/Y')"
    :date="($filters['from'] ?? 'Inicio') . ' al ' . ($filters['to'] ?? 'Hoy')"
    :company="$company"
    footerNote="Reporte consolidado generado para control gerencial y auditoría.">

    {{-- Resumen Ejecutivo en Grid --}}
    <x-ui.pdf-info-grid :rows="[
        [
            ['label' => 'Total Ventas', 'value' => 'Bs. ' . number_format((float) ($summary['sales'] ?? 0), 2), 'width' => '33%', 'highlight' => true],
            ['label' => 'Total Compras', 'value' => 'Bs. ' . number_format((float) ($summary['purchases'] ?? 0), 2), 'width' => '33%'],
            ['label' => 'Total Gastos', 'value' => 'Bs. ' . number_format((float) ($summary['expenses'] ?? 0), 2), 'width' => '34%'],
        ],
        [
            ['label' => 'Costo Mercadería Vendida (FIFO)', 'value' => 'Bs. ' . number_format((float) ($summary['cost'] ?? 0), 2), 'width' => '33%'],
            ['label' => 'Utilidad Bruta', 'value' => 'Bs. ' . number_format((float) ($summary['gross'] ?? 0), 2) . ' (' . ($summary['margin'] ?? 0) . '%)', 'width' => '33%'],
            ['label' => 'Utilidad Neta', 'value' => 'Bs. ' . number_format((float) ($summary['net'] ?? 0), 2), 'width' => '34%', 'highlight' => true],
        ],
    ]" />

    {{-- Ventas por Usuario --}}
    @if(isset($byUser) && count($byUser) > 0)
        <div style="margin-top: 15px; margin-bottom: 5px; font-size: 8.5pt; font-weight: bold; text-transform: uppercase; color: #111827;">
            Desglose de Ventas por Usuario:
        </div>
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 35%;" class="text-left">Usuario</th>
                    <th style="width: 15%;" class="text-center">N° Ventas</th>
                    <th style="width: 25%;" class="text-right">Total Vendido (Bs.)</th>
                    <th style="width: 25%;" class="text-right">Ganancia Generada (Bs.)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($byUser as $u)
                    <tr>
                        <td>{{ $u->name }}</td>
                        <td class="text-center">{{ $u->orders }}</td>
                        <td class="text-right font-bold">{{ number_format((float) $u->revenue, 2) }}</td>
                        <td class="text-right font-bold">{{ number_format((float) $u->revenue - (float) $u->cost, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    {{-- Movimientos del Período --}}
    @if(isset($movements) && count($movements) > 0)
        <div style="margin-top: 15px; margin-bottom: 5px; font-size: 8.5pt; font-weight: bold; text-transform: uppercase; color: #111827;">
            Detalle de Movimientos Registrados:
        </div>
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 15%;" class="text-left">Fecha</th>
                    <th style="width: 15%;" class="text-left">Tipo</th>
                    <th style="width: 12%;" class="text-left">N°</th>
                    <th style="width: 38%;" class="text-left">Detalle</th>
                    <th style="width: 20%;" class="text-right">Monto (Bs.)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($movements as $m)
                    <tr>
                        <td>{{ $m->date_label }}</td>
                        <td>{{ $m->type_label }}</td>
                        <td>{{ $m->number }}</td>
                        <td>{{ $m->detail }}</td>
                        <td class="text-right font-bold">{{ $m->amount_label }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

</x-ui.pdf-layout>
