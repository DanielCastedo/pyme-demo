<x-ui.pdf-layout
    title="BITÁCORA DE AUDITORÍA Y ACTIVIDADES"
    :number="date('d/m/Y H:i')"
    :date="($f['from'] ?? 'Inicio') . ' al ' . ($f['to'] ?? 'Hoy')"
    :company="$company"
    footerNote="Reporte oficial de auditoría de seguridad del sistema. Confidencial.">

    {{-- Filtros aplicados y métricas del reporte --}}
    <x-ui.pdf-info-grid :rows="[
        [
            ['label' => 'Usuario Auditado', 'value' => $userName, 'width' => '33%', 'highlight' => true],
            ['label' => 'Módulo Filtrado', 'value' => !empty($f['log_name']) ? ucfirst($f['log_name']) : 'Todos', 'width' => '33%'],
            ['label' => 'Evento Filtrado', 'value' => !empty($f['event']) ? ucfirst($f['event']) : 'Todos', 'width' => '34%'],
        ],
        [
            ['label' => 'Período', 'value' => ($f['from'] ?? 'Inicio') . ' al ' . ($f['to'] ?? 'Hoy'), 'width' => '40%'],
            ['label' => 'Total Eventos Emitidos', 'value' => count($activities) . ' registros', 'width' => '30%'],
            ['label' => 'Eliminaciones Críticas', 'value' => ($summary['deleted'] ?? 0) . ' registro(s)', 'width' => '30%'],
        ],
    ]" />

    {{-- Tabla de Auditoría Vertical --}}
    <table class="items-table" style="margin-top: 15px;">
        <thead>
            <tr>
                <th style="width: 6%;" class="text-center">#</th>
                <th style="width: 17%;">Fecha y Hora</th>
                <th style="width: 20%;">Usuario</th>
                <th style="width: 13%;" class="text-center">Módulo</th>
                <th style="width: 12%;" class="text-center">Evento</th>
                <th style="width: 32%;">Descripción / Detalle</th>
            </tr>
        </thead>
        <tbody>
            @forelse($activities as $act)
                <tr>
                    <td class="text-center" style="color: #4b5563; font-size: 7.5pt;">{{ $act->id }}</td>
                    <td style="font-size: 7.5pt; font-family: monospace;">{{ $act->created_at->format('d/m/Y H:i') }}</td>
                    <td style="font-size: 8pt;"><strong>{{ $act->causer->name ?? 'Sistema / Anónimo' }}</strong></td>
                    <td class="text-center" style="font-size: 7.5pt;">{{ ucfirst($act->log_name ?: 'General') }}</td>
                    <td class="text-center" style="text-transform: uppercase; font-size: 7pt; font-weight: bold;">
                        {{ $act->event ?: 'acción' }}
                    </td>
                    <td style="font-size: 8pt;">
                        <div>{{ $act->description }}</div>
                        @if(!empty($act->properties) && count($act->properties) > 0)
                            <div style="font-size: 6.5pt; color: #4b5563; font-family: monospace; margin-top: 2px;">
                                @php
                                    $props = $act->properties->toArray();
                                    $preview = isset($props['attributes']) 
                                        ? implode(' | ', array_map(fn($k, $v) => "$k: $v", array_keys(array_filter($props['attributes'], 'is_scalar')), array_filter($props['attributes'], 'is_scalar')))
                                        : (isset($props['ip']) ? 'IP: '.$props['ip'] : json_encode($props, JSON_UNESCAPED_UNICODE));
                                @endphp
                                {{ \Illuminate\Support\Str::limit($preview, 90) }}
                            </div>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center" style="padding: 15px; color: #4b5563;">
                        No se registraron actividades con los filtros seleccionados.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

</x-ui.pdf-layout>
