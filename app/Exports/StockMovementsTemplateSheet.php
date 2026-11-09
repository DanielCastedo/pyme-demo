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
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
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
        return ['sku', 'tipo', 'cantidad', 'unidad', 'costo_unitario', 'lote', 'vence'];
    }

    public function array(): array
    {
        // Productos reales del sistema; si hay menos de tres se repiten (un código inventado no existiría al importar).
        $a = $this->samples[0] ?? ['sku' => 'MED-0002', 'unit' => 'Frasco', 'big_unit' => null];
        $b = $this->samples[1] ?? $a;
        $c = $this->samples[2] ?? $a;
        $date = fn (int $months) => ExcelDate::PHPToExcel(now()->addMonths($months)->endOfMonth()->startOfDay());

        return [
            // Entrada de 20 unidades a 22.00 c/u, con su lote y vencimiento (la unidad se puede dejar vacía: usa la base)
            [$a['sku'], 'entrada', 20, $a['unit'], 22.00, 'L2405', $date(8)],
            // Entrada por presentación: 5 cajas (o la presentación que tenga) a 25.00 cada una, sin lote ni vencimiento
            [$b['sku'], 'entrada', 5, $b['big_unit'] ?? $b['unit'], 25.00, '', ''],
            // Salida (ajuste, merma, vencido...): no lleva costo ni lote; descuenta primero lo que vence antes
            [$c['sku'], 'salida', 2, '', '', '', ''],
        ];
    }

    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_TEXT,
            'C' => '0',
            'E' => '#,##0.00',
            'F' => NumberFormat::FORMAT_TEXT,
            'G' => 'dd/mm/yyyy',
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

                $expires = (new DataValidation())
                    ->setType(DataValidation::TYPE_DATE)
                    ->setOperator(DataValidation::OPERATOR_GREATERTHAN)
                    ->setErrorStyle(DataValidation::STYLE_STOP)
                    ->setAllowBlank(true)
                    ->setShowInputMessage(true)
                    ->setShowErrorMessage(true)
                    ->setPromptTitle('vence')
                    ->setPrompt('Opcional, solo en entradas. Fecha de vencimiento del lote (dd/mm/aaaa).')
                    ->setErrorTitle('Fecha no válida')
                    ->setError('Escribe una fecha, por ejemplo 31/12/2027.')
                    ->setFormula1('1');

                for ($row = 2; $row <= $last; $row++) {
                    $sheet->getCell("B{$row}")->setDataValidation(clone $type);
                    $sheet->getCell("C{$row}")->setDataValidation(clone $quantity);
                    $sheet->getCell("E{$row}")->setDataValidation(clone $cost);
                    $sheet->getCell("G{$row}")->setDataValidation(clone $expires);
                }
            },
        ];
    }
}
