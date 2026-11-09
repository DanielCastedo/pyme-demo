<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StockMovementsInstructionsSheet implements FromArray, WithColumnWidths, WithStyles, WithTitle
{
    public function title(): string
    {
        return 'Instrucciones';
    }

    public function array(): array
    {
        return [
            ['Cómo llenar la hoja "Inventario"'],
            [''],
            ['Columna', 'Obligatoria', 'Qué poner'],
            ['sku', 'Sí', 'SKU del producto (o el código de barras de una de sus unidades). El producto debe existir y tener su unidad de venta.'],
            ['tipo', 'Sí', '"entrada" suma stock (compra, inventario inicial, ajuste a favor). "salida" resta stock (merma, ajuste en contra).'],
            ['cantidad', 'Sí', 'Número ENTERO mayor a 0, sin decimales. Es la cantidad de la unidad indicada (5 cajas = 5, no 120).'],
            ['unidad', 'No', 'Nombre de la presentación tal como está en "Unidades de Producto" (Unidad, Caja...). Vacío = la unidad base del producto.'],
            ['costo_unitario', 'Solo en entradas', 'Lo que cuesta UNA unidad de la presentación indicada (si la unidad es "Caja", el costo de una caja). El sistema lo divide entre lo que trae y lo guarda por unidad base con 2 decimales. En salidas se deja vacío.'],
            ['lote', 'No (solo entradas)', 'Número de lote del fabricante (máx. 50 caracteres). Puede repetirse. En salidas se deja vacío.'],
            ['vence', 'No (solo entradas)', 'Fecha de vencimiento del lote (dd/mm/aaaa). En salidas se deja vacío.'],
            [''],
            ['Reglas importantes'],
            ['• El almacén NO va en el archivo: se elige al subirlo. Para cargar varios almacenes, sube un archivo por almacén.'],
            ['• Es "todo o nada": si una sola fila tiene un error no se guarda ninguna, y se te indica cada fila con su problema.'],
            ['• Cada entrada crea un lote nuevo con su costo (y su lote y vencimiento, si los pones). Para varios lotes del mismo producto, pon una fila de entrada por lote.'],
            ['• Cada salida descuenta primero del lote que vence antes; los lotes sin vencimiento se usan al final, del más antiguo al más nuevo.'],
            ['• Una salida no puede dejar el stock en negativo. Las entradas del mismo archivo cuentan como stock disponible para sus salidas.'],
            ['• Los productos tipo kit (con componentes) no se cargan aquí: su stock se calcula de sus componentes.'],
            ['• Máximo 5.000 filas por archivo. Las filas totalmente vacías se ignoran.'],
            ['• Las 3 filas de ejemplo de la hoja "Inventario" bórralas antes de importar.'],
            ['• Deja la columna sku en formato Texto para no perder ceros a la izquierda.'],
            ['• Todo queda registrado como movimientos de stock (uno de entrada y uno de salida), que puedes revisar o anular desde "Movimientos de Stock".'],
        ];
    }

    public function columnWidths(): array
    {
        return ['A' => 18, 'B' => 18, 'C' => 110];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
            3 => ['font' => ['bold' => true], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'D1FAE5']]],
            12 => ['font' => ['bold' => true, 'size' => 12]], // "Reglas importantes"
        ];
    }
}
