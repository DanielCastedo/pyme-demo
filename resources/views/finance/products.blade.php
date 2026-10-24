<x-app-layout>
    <div class="max-w-10xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        @php
            $buttons = [
                [
                    'route' => route('finance.products.pdf', request()->query()),
                    'can' => 'reportes.exportar',
                    'label' => 'Imprimir / Exportar PDF',
                    'variant' => 'danger',
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />',
                    'target' => '_blank',
                ],
                [
                    'route' => route('finance.products.excel', request()->query()),
                    'can' => 'reportes.exportar',
                    'label' => 'Exportar Excel',
                    'variant' => 'success',
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />',
                    'target' => '_blank',
                ],
            ];
        @endphp

        <x-ui.page-header category="Finanzas" title="Ganancias por producto"
            description="Cuánto vendiste de cada producto, cuánto te costó esa mercadería (FIFO) y cuánto compraste en el mismo período." :buttons="$buttons" />

        @include('finance._filters', ['action' => route('finance.products'), 'withOrder' => true])

        @include('finance._summary')

        @php
            $purchasesApply = \App\Services\FinanceReportService::purchasesApply($f);
            $rows = $products->through(function ($r) use ($purchasesApply) {
                $profit = (float) $r->revenue - (float) $r->cost;

                return (object) [
                    'name' => $r->name,
                    'sku' => $r->sku,
                    'sold_label' => rtrim(rtrim(number_format((float) $r->sold_qty, 2), '0'), '.'),
                    'revenue_label' => number_format((float) $r->revenue, 2),
                    'cost_label' => number_format((float) $r->cost, 2),
                    'profit_label' => number_format($profit, 2),
                    'margin_label' => (float) $r->revenue > 0 ? number_format($profit / (float) $r->revenue * 100, 1).'%' : '—',
                    'bought_label' => $purchasesApply ? rtrim(rtrim(number_format((float) $r->bought_qty, 2), '0'), '.') : '—',
                    'bought_amount_label' => $purchasesApply ? number_format((float) $r->bought_amount, 2) : '—',
                    'id' => $r->id,
                ];
            });
        @endphp

        <x-ui.data-table :rows="$rows" :paginator="$rows"
            :columns="[
                ['key' => 'name', 'label' => 'Producto'],
                ['key' => 'sku', 'label' => 'Código'],
                ['key' => 'sold_label', 'label' => 'Vendido (unid.)'],
                ['key' => 'revenue_label', 'label' => 'Ventas'],
                ['key' => 'cost_label', 'label' => 'Costo'],
                ['key' => 'profit_label', 'label' => 'Ganancia'],
                ['key' => 'margin_label', 'label' => 'Margen'],
                ['key' => 'bought_label', 'label' => 'Comprado (unid.)'],
                ['key' => 'bought_amount_label', 'label' => 'Compras'],
            ]"
            emptyText="No hay ventas ni compras de productos con estos filtros." />

        <p class="mt-3 text-[11px] text-gray-500 dark:text-gray-400">
            Las unidades están en la unidad base del producto (una caja de 24 cuenta como 24). El costo es lo que realmente costó lo vendido, lote por lote;
            "Comprado" es lo que entró por compras en el período, así puedes ver si compraste más o menos de lo que vendiste.
            @unless ($purchasesApply) Con un cliente elegido no se muestran compras. @endunless
        </p>
    </div>
</x-app-layout>
