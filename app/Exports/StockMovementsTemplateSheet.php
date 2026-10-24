<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StockMovementsTemplateSheet implements FromArray, ShouldAutoSize, WithColumnFormatting, WithEvents, WithHeadings, WithStyles, WithTitle
{
    /** Filas con desplegable/validación (cubre de sobra una carga típica). */
    private const VALIDATED_ROWS = 1000;

    /** @param  array<int, array{sku: string, unit: string, big_unit: ?string}>  $samples */
    public function __construct(private readonly array $samples) {}

    public function title(): string
    {
        return 'Inventario';
    }

    public function headings(): array
    {
        return ['sku', 'tipo', 'cantidad', 'unidad', 'costo_unitario'];
    }

    public function array(): array
    {
        $a = $this->samples[0] ?? ['sku' => 'MAR-0001', 'unit' => 'Unidad', 'big_unit' => null];
        $b = $this->samples[1] ?? ['sku' => 'MAR-0002', 'unit' => 'Unidad', 'big_unit' => 'Caja'];
        $c = $this->samples[2] ?? $a;

        return [
            // Entrada de 100 unidades a 35.50 c/u (la unidad se puede dejar vacía: usa la base)
            [$a['sku'], 'entrada', 100, $a['unit'], 35.50],
            // Entrada por presentación: 5 cajas (o la presentación que tenga) a 480 cada una
            [$b['sku'], 'entrada', 5, $b['big_unit'] ?? $b['unit'], 480.00],
            // Salida (ajuste, merma...): no lleva costo, sale por FIFO
            [$c['sku'], 'salida', 2, '', ''],
        ];
    }

    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_TEXT,
            'C' => '0',
            'E' => '#,##0.00',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->freezePane('A2');

        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '059669']],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $last = self::VALIDATED_ROWS + 1;

                $type = (new DataValidation())
                    ->setType(DataValidation::TYPE_LIST)
                    ->setErrorStyle(DataValidation::STYLE_STOP)
                    ->setAllowBlank(true)
                    ->setShowDropDown(false)
                    ->setShowErrorMessage(true)
                    ->setErrorTitle('Tipo no válido')
                    ->setError('Usa "entrada" o "salida".')
                    ->setFormula1('"entrada,salida"');

                $quantity = (new DataValidation())
                    ->setType(DataValidation::TYPE_WHOLE)
                    ->setOperator(DataValidation::OPERATOR_GREATERTHANOREQUAL)
                    ->setErrorStyle(DataValidation::STYLE_STOP)
                    ->setAllowBlank(true)
                    ->setShowErrorMessage(true)
                    ->setErrorTitle('Cantidad no válida')
                    ->setError('La cantidad debe ser un número entero mayor o igual a 1 (sin decimales).')
                    ->setFormula1('1');

                $cost = (new DataValidation())
                    ->setType(DataValidation::TYPE_DECIMAL)
                    ->setOperator(DataValidation::OPERATOR_GREATERTHANOREQUAL)
                    ->setErrorStyle(DataValidation::STYLE_STOP)
                    ->setAllowBlank(true)
                    ->setShowErrorMessage(true)
                    ->setErrorTitle('Costo no válido')
                    ->setError('El costo debe ser un número mayor o igual a 0.')
                    ->setFormula1('0');

                for ($row = 2; $row <= $last; $row++) {
                    $sheet->getCell("B{$row}")->setDataValidation(clone $type);
                    $sheet->getCell("C{$row}")->setDataValidation(clone $quantity);
                    $sheet->getCell("E{$row}")->setDataValidation(clone $cost);
                }
            },
        ];
    }
}
