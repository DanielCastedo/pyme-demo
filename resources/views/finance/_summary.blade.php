{{-- Tarjetas de resumen. Recibe: $summary. Un total que el filtro no permite calcular llega como null y se muestra "—". --}}
@php
    $money = fn ($v) => $v === null ? '—' : number_format($v, 2);
    $profitColor = fn ($v) => $v === null ? 'gray' : ($v < 0 ? 'red' : 'emerald');
@endphp

<div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-3 mb-5">
    <x-ui.stat-card label="Total de ventas" :value="$money($summary['sales'])" color="sky" />
    <x-ui.stat-card label="Total de compras" :value="$money($summary['purchases'])" color="violet" />
    <x-ui.stat-card label="Total de gastos" :value="$money($summary['expenses'])" color="amber" />
    <x-ui.stat-card label="Costo de lo vendido" :value="$money($summary['cost'])" color="gray" />
    <x-ui.stat-card label="Ganancia bruta" :value="$money($summary['gross']).($summary['margin'] !== null ? '  ('.$summary['margin'].'%)' : '')" :color="$profitColor($summary['gross'])" />
    <x-ui.stat-card label="Ganancia neta" :value="$money($summary['net'])" :color="$profitColor($summary['net'])" />
</div>

<p class="-mt-2 mb-5 text-[11px] text-gray-500 dark:text-gray-400">
    {{ $summary['sales_count'] }} venta(s)
    @if ($summary['purchases_count'] !== null) · {{ $summary['purchases_count'] }} compra(s) @endif
    @if ($summary['expenses_count'] !== null) · {{ $summary['expenses_count'] }} gasto(s) @endif
    · <strong>Ganancia bruta</strong> = ventas − costo real (FIFO) de lo vendido · <strong>Ganancia neta</strong> = ganancia bruta − gastos.
    Las compras son mercadería que entró al inventario, por eso no se restan de la ganancia: su costo se descuenta cuando esa mercadería se vende.
</p>
