<?php

namespace App\Exports;

use App\Models\Cashbox;
use App\Support\Bc;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Totales por tipo de transacción de una caja (solo vigentes, no anuladas), en el mismo
 * período que se esté filtrando en la pantalla de detalle de la caja.
 */
class CashboxTotalsByTypeExport implements FromArray, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    public function __construct(
        protected Cashbox $cashbox,
        protected ?string $from = null,
        protected ?string $to = null,
    ) {}

    public function array(): array
    {
        $totals = $this->cashbox->totalsByType($this->from, $this->to);

        // El signo del tipo (+/-) indica si suma o resta dinero a la caja.
        $net = (float) Bc::sum($totals->map(fn ($row) => match ($row['type']->sign) {
            '+' => $row['total'],
            '-' => Bc::sub(0, $row['total']),
            default => 0,
        }));

        $rows = $totals->map(fn ($row) => [
            $row['type']->name,
            match ($row['type']->sign) {
                '+' => 'Suma (+)',
                '-' => 'Resta (-)',
                default => '-',
            },
            $row['count'],
            round($row['total'], 2),
        ])->all();

        $rows[] = ['', '', '', ''];
        $rows[] = ['FLUJO NETO', '', '', round($net, 2)];

        return $rows;
    }

    public function headings(): array
    {
        return ['Tipo de transacción', 'Efecto', 'Movimientos', 'Total'];
    }

    public function title(): string
    {
        return 'Totales por tipo';
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = $sheet->getHighestRow();

        $sheet->getStyle("A{$lastRow}:D{$lastRow}")->getFont()->setBold(true);

        return [1 => ['font' => ['bold' => true]]];
    }
}
