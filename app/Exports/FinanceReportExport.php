<?php

namespace App\Exports;

use App\Services\FinanceReportService;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Reporte financiero (ventas, compras, gastos y ganancia) con los mismos filtros que la
 * pantalla "Finanzas > Reportes": una hoja de resumen, una de ventas por usuario y una con
 * todos los movimientos del período.
 */
class FinanceReportExport implements WithMultipleSheets
{
    public function __construct(
        protected FinanceReportService $reports,
        protected array $f,
    ) {}

    public function sheets(): array
    {
        $summary = $this->reports->summary($this->f);
        $byUser = $this->reports->salesByUser($this->f, 500)->items();
        $movements = $this->reports->movements($this->f, 2000)->items();

        return [
            'Resumen' => $this->summarySheet($summary),
            'Ventas por usuario' => $this->usersSheet($byUser),
            'Movimientos' => $this->movementsSheet($movements),
        ];
    }

    private function summarySheet(array $summary): FromArray
    {
        return new class($this->f, $summary) implements FromArray, ShouldAutoSize, WithStyles, WithTitle
        {
            public function __construct(private array $f, private array $summary) {}

            public function array(): array
            {
                return [
                    ['Período', ($this->f['from'] ?? '').' al '.($this->f['to'] ?? '')],
                    [],
                    ['Total ventas', round((float) ($this->summary['sales'] ?? 0), 2)],
                    ['Total compras', round((float) ($this->summary['purchases'] ?? 0), 2)],
                    ['Total gastos', round((float) ($this->summary['expenses'] ?? 0), 2)],
                    ['Costo mercadería vendida (FIFO)', round((float) ($this->summary['cost'] ?? 0), 2)],
                    ['Utilidad bruta', round((float) ($this->summary['gross'] ?? 0), 2), ($this->summary['margin'] ?? 0).'%'],
                    ['Utilidad neta', round((float) ($this->summary['net'] ?? 0), 2)],
                ];
            }

            public function title(): string
            {
                return 'Resumen';
            }

            public function styles(Worksheet $sheet): array
            {
                $sheet->getStyle('A1:A8')->getFont()->setBold(true);

                return [];
            }
        };
    }

    private function usersSheet(array $byUser): FromArray
    {
        return new class($byUser) implements FromArray, ShouldAutoSize, WithStyles, WithTitle
        {
            public function __construct(private array $byUser) {}

            public function array(): array
            {
                $rows = [['Usuario', 'N° de ventas', 'Total vendido', 'Costo', 'Ganancia']];

                foreach ($this->byUser as $u) {
                    $rows[] = [
                        $u->name,
                        (int) $u->orders,
                        round((float) $u->revenue, 2),
                        round((float) $u->cost, 2),
                        round((float) $u->revenue - (float) $u->cost, 2),
                    ];
                }

                return $rows;
            }

            public function title(): string
            {
                return 'Ventas por usuario';
            }

            public function styles(Worksheet $sheet): array
            {
                return [1 => ['font' => ['bold' => true]]];
            }
        };
    }

    private function movementsSheet(array $movements): FromArray
    {
        return new class($movements) implements FromArray, ShouldAutoSize, WithStyles, WithTitle
        {
            public function __construct(private array $movements) {}

            public function array(): array
            {
                $rows = [['Fecha', 'Tipo', 'N°', 'Detalle', 'Sucursal', 'Usuario', 'Monto']];

                foreach ($this->movements as $m) {
                    $rows[] = [
                        $m->date_label,
                        $m->type_label,
                        $m->number,
                        $m->detail,
                        $m->branch,
                        $m->user_name,
                        round((float) $m->amount, 2),
                    ];
                }

                return $rows;
            }

            public function title(): string
            {
                return 'Movimientos';
            }

            public function styles(Worksheet $sheet): array
            {
                return [1 => ['font' => ['bold' => true]]];
            }
        };
    }
}
