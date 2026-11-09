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
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Plantilla de la "Importación general": Codigo, Item, Cantidad, Precio Venta U, Precio Compra U, Unidad, Color,
 * Marca, Foto, Medida, Categoria, Descripcion, Importado (A-M) y, al final para no mover las anteriores, Lote,
 * Vence, Presentacion, Contiene y Precio Presentacion (N-R).
 */
class GeneralProductsTemplateSheet implements FromArray, ShouldAutoSize, WithColumnFormatting, WithEvents, WithHeadings, WithStyles, WithTitle
{
    private const VALIDATED_ROWS = 1000;

    private const UNIT_LIST_ROWS = 50;

    private const CATEGORY_LIST_ROWS = 500;

    /**
     * @param  Collection<int, string>  $units  unidades de medida activas (los ejemplos usan las que existan)
     * @param  Collection<int, string>  $categories  categorías existentes
     */
    public function __construct(private readonly Collection $units, private readonly Collection $categories) {}

    public function title(): string
    {
        return 'Productos';
    }

    public function headings(): array
    {
        return ['Codigo', 'Item', 'Cantidad', 'Precio Venta U', 'Precio Compra U', 'Unidad', 'Color', 'Marca', 'Foto', 'Medida', 'Categoria', 'Descripcion', 'Importado',
            'Lote', 'Vence', 'Presentacion', 'Contiene', 'Precio Presentacion'];
    }

    /**
     * Ejemplos de farmacia: un medicamento con DOS lotes (dos filas con el mismo Codigo, cada una con su costo y
     * vencimiento) y su presentación Caja x100, otro con un lote y uno sin stock.
     */
    public function array(): array
    {
        $unit = fn (string ...$preferred) => collect($preferred)->first(fn ($u) => $this->units->contains($u)) ?? $this->units->first() ?? '';
        $category = fn (string $preferred) => $this->categories->contains($preferred) ? $preferred : '';
        $date = fn (int $months) => ExcelDate::PHPToExcel(now()->addMonths($months)->endOfMonth()->startOfDay());
        $box = $this->units->contains('Caja') ? 'Caja' : '';

        return [
            ['MED-0001', 'Paracetamol 500mg', 300, 0.50, 0.25, $unit('Tableta', 'Unidad'), '', 'Laboratorios Bagó', '', '500mg', $category('Analgésicos y Antipiréticos'), 'Se vende por tableta o en caja de 100.', '',
                'L2405', $date(10), $box, $box ? 100 : '', $box ? 40.00 : ''],
            ['MED-0001', '', 200, '', 0.27, '', '', '', '', '', '', '', '', 'L2411', $date(18), '', '', ''],
            ['MED-0002', 'Ambroxol jarabe adulto 120ml', 10, 35.00, 22.00, $unit('Frasco', 'Unidad'), '', 'Laboratorios INTI', '', '120ml', $category('Respiratorios y Antigripales'), '', '',
                'AMB-2405', $date(6), '', '', ''],
            ['CUR-0001', 'Gasa estéril 10x10cm', 0, 3.00, '', $unit('Sobre', 'Unidad'), '', '', '', '10x10cm', $category('Material de Curación'), '', '', '', '', '', '', ''],
        ];
    }

    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_TEXT,
            'D' => '#,##0.00',
            'E' => '#,##0.00',
            'N' => NumberFormat::FORMAT_TEXT,
            'O' => 'dd/mm/yyyy',
            'R' => '#,##0.00',
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

                $expires = (new DataValidation())
                    ->setType(DataValidation::TYPE_DATE)
                    ->setOperator(DataValidation::OPERATOR_GREATERTHAN)
                    ->setErrorStyle(DataValidation::STYLE_STOP)
                    ->setAllowBlank(true)
                    ->setShowInputMessage(true)
                    ->setShowErrorMessage(true)
                    ->setPromptTitle('Vence')
                    ->setPrompt('Opcional. Fecha de vencimiento del lote (dd/mm/aaaa). Solo si la fila trae Cantidad.')
                    ->setErrorTitle('Fecha no válida')
                    ->setError('Escribe una fecha, por ejemplo 31/12/2027.')
                    ->setFormula1('1');

                $presentation = (clone $units)
                    ->setAllowBlank(true)
                    ->setPromptTitle('Presentacion')
                    ->setPrompt('Opcional. Presentación además de la Unidad (ej. Caja). Completa también Contiene y Precio Presentacion.');

                $contains = (new DataValidation())
                    ->setType(DataValidation::TYPE_WHOLE)
                    ->setOperator(DataValidation::OPERATOR_GREATERTHANOREQUAL)
                    ->setErrorStyle(DataValidation::STYLE_STOP)
                    ->setAllowBlank(true)
                    ->setShowErrorMessage(true)
                    ->setErrorTitle('Contiene no válido')
                    ->setError('Cuántas unidades base trae la presentación: un número entero mayor a 1 (ej. 100).')
                    ->setFormula1('2');

                for ($row = 2; $row <= $last; $row++) {
                    $sheet->getCell("F{$row}")->setDataValidation(clone $units);
                    $sheet->getCell("C{$row}")->setDataValidation(clone $quantity);
                    $sheet->getCell("D{$row}")->setDataValidation(clone $price);
                    $sheet->getCell("E{$row}")->setDataValidation(clone $price);
                    $sheet->getCell("K{$row}")->setDataValidation(clone $category);
                    $sheet->getCell("M{$row}")->setDataValidation(clone $imported);
                    $sheet->getCell("O{$row}")->setDataValidation(clone $expires);
                    $sheet->getCell("P{$row}")->setDataValidation(clone $presentation);
                    $sheet->getCell("Q{$row}")->setDataValidation(clone $contains);
                    $sheet->getCell("R{$row}")->setDataValidation(clone $price);
                }
            },
        ];
    }
}
