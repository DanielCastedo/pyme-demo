<?php

namespace App\Exports;

use App\Services\FinanceReportService;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Ganancias por producto (ventas, costo FIFO y compras) con los mismos filtros y el mismo
 * orden que la pantalla "Finanzas > Ganancias por producto".
 */
class FinanceProductProfitExport implements FromArray, ShouldAutoSize, WithStyles, WithTitle
{
    /** Filas de encabezado antes de la tabla, para poner el header de columnas en negrita. */
    private int $tableHeaderRow = 1;

    public function __construct(
        protected FinanceReportService $reports,
        protected array $f,
        protected string $order = 'ganancia',
    ) {}

    public function array(): array
    {
        $summary = $this->reports->summary($this->f);
        $purchasesApply = FinanceReportService::purchasesApply($this->f);
        $products = $this->reports->productProfit($this->f, $this->order, 500)->items();

        $rows = [
            ['Reporte de ganancias por producto'],
            ['Período', ($this->f['from'] ?? '').' al '.($this->f['to'] ?? '')],
            ['Total ventas', round((float) ($summary['sales'] ?? 0), 2)],
            ['Costo mercadería vendida', round((float) ($summary['cost'] ?? 0), 2)],
            ['Ganancia bruta', round((float) ($summary['gross'] ?? 0), 2), ($summary['margin'] ?? 0).'%'],
            [],
        ];

        $this->tableHeaderRow = count($rows) + 1;

        $rows[] = ['Producto', 'Código', 'Vendido (unid.)', 'Ventas', 'Costo', 'Ganancia', 'Margen', 'Comprado (unid.)', 'Compras'];

        foreach ($products as $p) {
            $profit = (float) $p->revenue - (float) $p->cost;

            $rows[] = [
                $p->name,
                $p->sku,
                round((float) $p->sold_qty, 2),
                round((float) $p->revenue, 2),
                round((float) $p->cost, 2),
                round($profit, 2),
                (float) $p->revenue > 0 ? round($profit / (float) $p->revenue * 100, 1).'%' : '—',
                $purchasesApply ? round((float) $p->bought_qty, 2) : '—',
                $purchasesApply ? round((float) $p->bought_amount, 2) : '—',
            ];
        }

        return $rows;
    }

    public function title(): string
    {
        return 'Ganancias por producto';
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $sheet->getStyle("A{$this->tableHeaderRow}:I{$this->tableHeaderRow}")->getFont()->setBold(true);

        return [];
    }
}
