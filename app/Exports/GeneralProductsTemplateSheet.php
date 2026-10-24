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

/**
 * Plantilla de la "Importación general": Codigo, Item, Cantidad, Precio Venta U, Precio Compra U,
 * Unidad, Color, Marca, Foto, Medida, Categoria, Descripcion, Importado. Columnas A-M, en ese orden.
 */
class GeneralProductsTemplateSheet implements FromArray, ShouldAutoSize, WithColumnFormatting, WithEvents, WithHeadings, WithStyles, WithTitle
{
    private const VALIDATED_ROWS = 1000;

    private const UNIT_LIST_ROWS = 50;

    private const CATEGORY_LIST_ROWS = 500;

    public function __construct(private readonly ?string $sampleUnit, private readonly ?string $sampleCategory = null) {}

    public function title(): string
    {
        return 'Productos';
    }

    public function headings(): array
    {
        return ['Codigo', 'Item', 'Cantidad', 'Precio Venta U', 'Precio Compra U', 'Unidad', 'Color', 'Marca', 'Foto', 'Medida', 'Categoria', 'Descripcion', 'Importado'];
    }

    public function array(): array
    {
        return [
            ['MAR-0001', 'Martillo de uña 16oz', 10, 55.00, 35.00, $this->sampleUnit ?? '', '', 'Stanley', '', '16oz', $this->sampleCategory ?? '', 'Martillo con cabeza de acero forjado.', ''],
            ['MAR-0002', 'Destornillador plano', 0, 18.50, '', $this->sampleUnit ?? '', '', '', '', '', '', '', ''],
        ];
    }

    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_TEXT,
            'D' => '#,##0.00',
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
                $sheet->getColumnDimension('I')->setWidth(22);
                $sheet->getColumnDimension('L')->setWidth(40);
                $last = self::VALIDATED_ROWS + 1;

                $units = (new DataValidation())
                    ->setType(DataValidation::TYPE_LIST)
                    ->setErrorStyle(DataValidation::STYLE_STOP)
                    ->setAllowBlank(false)
                    ->setShowDropDown(false)
                    ->setShowInputMessage(true)
                    ->setShowErrorMessage(true)
                    ->setPromptTitle('Unidad')
                    ->setPrompt('Elige una unidad de la lista (hoja "Unidades").')
                    ->setErrorTitle('Unidad no válida')
                    ->setError('Elige una unidad de la lista de la hoja "Unidades".')
                    ->setFormula1('Unidades!$A$2:$A$'.(self::UNIT_LIST_ROWS + 1));

                $quantity = (new DataValidation())
                    ->setType(DataValidation::TYPE_DECIMAL)
                    ->setOperator(DataValidation::OPERATOR_GREATERTHANOREQUAL)
                    ->setErrorStyle(DataValidation::STYLE_STOP)
                    ->setAllowBlank(true)
                    ->setShowErrorMessage(true)
                    ->setErrorTitle('Cantidad no válida')
                    ->setError('La cantidad debe ser un número mayor o igual a 0.')
                    ->setFormula1('0');

                $price = (new DataValidation())
                    ->setType(DataValidation::TYPE_DECIMAL)
                    ->setOperator(DataValidation::OPERATOR_GREATERTHANOREQUAL)
                    ->setErrorStyle(DataValidation::STYLE_STOP)
                    ->setAllowBlank(true)
                    ->setShowErrorMessage(true)
                    ->setErrorTitle('Precio no válido')
                    ->setError('El precio debe ser un número mayor o igual a 0.')
                    ->setFormula1('0');

                $category = (new DataValidation())
                    ->setType(DataValidation::TYPE_LIST)
                    ->setErrorStyle(DataValidation::STYLE_INFORMATION)
                    ->setAllowBlank(true)
                    ->setShowDropDown(false)
                    ->setShowInputMessage(true)
                    ->setShowErrorMessage(false)
                    ->setPromptTitle('Categoria')
                    ->setPrompt('Opcional. Elige una de la lista (hoja "Categorias") o escribe una nueva (se crea sola). Vacía: un producto nuevo cae en "Importación General"; uno existente conserva la que ya tenía.')
                    ->setFormula1('Categorias!$A$2:$A$'.(self::CATEGORY_LIST_ROWS + 1));

                $imported = (new DataValidation())
                    ->setType(DataValidation::TYPE_LIST)
                    ->setErrorStyle(DataValidation::STYLE_INFORMATION)
                    ->setAllowBlank(true)
                    ->setShowDropDown(false)
                    ->setShowInputMessage(true)
                    ->setPromptTitle('Importado')
                    ->setPrompt('Déjala vacía si la fila todavía no se cargó. Marca "Importado" en las que ya subiste, para poder volver a usar este mismo archivo sin duplicarlas.')
                    ->setFormula1('"Importado"');

                for ($row = 2; $row <= $last; $row++) {
                    $sheet->getCell("F{$row}")->setDataValidation(clone $units);
                    $sheet->getCell("C{$row}")->setDataValidation(clone $quantity);
                    $sheet->getCell("D{$row}")->setDataValidation(clone $price);
                    $sheet->getCell("E{$row}")->setDataValidation(clone $price);
                    $sheet->getCell("K{$row}")->setDataValidation(clone $category);
                    $sheet->getCell("M{$row}")->setDataValidation(clone $imported);
                }
            },
        ];
    }
}
