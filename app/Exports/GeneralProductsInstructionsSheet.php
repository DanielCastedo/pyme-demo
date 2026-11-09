<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class GeneralProductsInstructionsSheet implements FromArray, WithColumnWidths, WithStyles, WithTitle
{
    public function title(): string
    {
        return 'Instrucciones';
    }

    public function array(): array
    {
        return [
            ['Cómo llenar la hoja "Productos" (Importación General)'],
            [''],
            ['Columna', 'Obligatoria', 'Qué poner'],
            ['Codigo', 'Sí', 'El SKU del producto. Si ya existe en el sistema, se ACTUALIZA ese producto; si no existe, se CREA. Puede repetirse en varias filas: cada fila repetida es OTRO LOTE del mismo producto (ver "Varios lotes" abajo).'],
            ['Item', 'Sí', 'Nombre del producto (máx. 255 caracteres). En las filas repetidas de otros lotes puede ir vacío.'],
            ['Cantidad', 'No', 'Stock a cargar de este lote, en la Unidad base (ej. tabletas, no cajas). Si tiene cantidad, el Precio Compra U es obligatorio. Vacío o 0 = no carga stock.'],
            ['Precio Venta U', 'Sí', 'Precio de venta de una unidad base (número mayor o igual a 0, por ejemplo 0.50 o 35). En las filas repetidas de otros lotes puede ir vacío.'],
            ['Precio Compra U', 'No (sí si hay Cantidad)', 'Costo de UNA unidad base de este lote, con 2 decimales (si la caja de 100 costó 25, son 0.25). Con este dato se arma el lote con su costo real.'],
            ['Unidad', 'Sí', 'Elige una de la lista desplegable (hoja "Unidades"). Es la unidad de venta base del producto (factor 1).'],
            ['Color', 'No', 'Texto libre (máx. 100 caracteres).'],
            ['Marca', 'No', 'Texto libre (máx. 100 caracteres).'],
            ['Foto', 'No', 'Pega la imagen DENTRO de la celda de esa fila (Insertar → Imagen, y ubícala sobre la celda). No la arrastres para que tape la fila siguiente.'],
            ['Medida', 'No', 'Texto libre (máx. 100 caracteres), por ejemplo 16oz o 1/2 pulgada.'],
            ['Categoria', 'No', 'Opcional. Si la escribes (elige de la lista de la hoja "Categorias" o escribe una nueva, se crea sola), se usa esa categoría al crear o al actualizar el producto. Vacía: un producto NUEVO cae en "Importación General"; uno YA EXISTENTE conserva la categoría que ya tenía.'],
            ['Descripcion', 'No', 'Texto libre, sin límite de caracteres. Igual que Color/Marca/Medida: si la dejas vacía en un producto YA EXISTENTE, no se toca la que ya tenía.'],
            ['Importado', 'No', 'Déjala vacía si la fila todavía no se cargó. Escribe "Importado" en las filas que ya subiste antes, así puedes reutilizar el mismo archivo maestro sin duplicar lo ya cargado.'],
            ['Lote', 'No', 'Número de lote del fabricante (máx. 50 caracteres). Solo si la fila trae Cantidad. Puede repetirse.'],
            ['Vence', 'No', 'Fecha de vencimiento del lote (dd/mm/aaaa). Solo si la fila trae Cantidad. Las ventas descuentan primero el lote que vence antes.'],
            ['Presentacion', 'No', 'Otra forma de vender el producto además de la Unidad, ej. Caja (elige de la hoja "Unidades"). Va solo en la primera fila del Codigo.'],
            ['Contiene', 'Con Presentacion', 'Cuántas unidades base trae la presentación: número entero mayor a 1 (ej. 100 tabletas por caja).'],
            ['Precio Presentacion', 'Con Presentacion', 'Precio de venta de la presentación completa (ej. 40.00 la caja).'],
            [''],
            ['Reglas importantes'],
            ['• Varios lotes: para cargar dos lotes del mismo medicamento con distinto costo o vencimiento, pon dos filas con el mismo Codigo. La primera define el producto (Item, precio, unidad, presentación...); las siguientes solo necesitan Codigo, Cantidad, Precio Compra U y, si quieres, Lote y Vence. Mira el ejemplo MED-0001 de la hoja "Productos".'],
            ['• Antes de subir el archivo, en el formulario eliges a qué ALMACÉN entra todo el stock (Cantidad) de este archivo.'],
            ['• La importación es "todo o nada": si una sola fila (de las que no dicen "Importado") tiene un error, no se guarda ninguna, y se te indica cada fila con su problema.'],
            ['• El archivo debe ser .xlsx (no .xls ni .csv), porque la foto se lee como imagen incrustada en la celda.'],
            ['• Máximo 5.000 filas por archivo. Las filas totalmente vacías se ignoran.'],
            ['• Las filas de ejemplo de la hoja "Productos" bórralas antes de importar.'],
            ['• Deja la columna Codigo en formato Texto para no perder ceros a la izquierda.'],
        ];
    }

    public function columnWidths(): array
    {
        return ['A' => 18, 'B' => 22, 'C' => 100];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
            3 => ['font' => ['bold' => true], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'D1FAE5']]],
            23 => ['font' => ['bold' => true, 'size' => 12]], // "Reglas importantes"
        ];
    }
}
