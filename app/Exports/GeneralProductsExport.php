<?php

namespace App\Exports;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Exporta el catálogo con las mismas columnas y el mismo orden que la plantilla de la
 * "Importación general" (Codigo, Item, Cantidad, Precio Venta U, Precio Compra U, Unidad,
 * Color, Marca, Foto, Medida, Categoria, Descripcion, Importado): ver App\Exports\GeneralProductsTemplateSheet
 * y App\Imports\GeneralProductsImport. Así el archivo exportado se puede editar y volver a subir
 * por el mismo importador.
 *
 * "Cantidad" y "Precio Compra U" salen vacíos a propósito: son los campos que la importación usa
 * para CARGAR stock nuevo (registra una entrada de almacén), así que repetir aquí el stock actual
 * duplicaría el inventario si el archivo se reimporta tal cual. "Foto" también sale vacía: la
 * importación solo lee imágenes pegadas en la celda, no se pueden volcar desde una consulta.
 * "Importado" queda vacía para que, si se reimporta, el producto se actualice en vez de saltarse.
 */
class GeneralProductsExport implements FromQuery, WithColumnFormatting, WithHeadings, WithMapping, WithStyles
{
    /**
     * @param  Builder  $query  productos con sus relaciones (category, units) y ordenados
     */
    public function __construct(private readonly Builder $query) {}

    public function query(): Builder
    {
        return $this->query;
    }

    public function headings(): array
    {
        return ['Codigo', 'Item', 'Cantidad', 'Precio Venta U', 'Precio Compra U', 'Unidad', 'Color', 'Marca', 'Foto', 'Medida', 'Categoria', 'Descripcion', 'Importado'];
    }

    public function map($product): array
    {
        $baseUnit = $product->units->firstWhere('is_base', true);

        return [
            $product->sku,
            $product->name,
            '',
            $baseUnit ? (float) $baseUnit->price : '',
            '',
            $baseUnit->name ?? '',
            $product->color,
            $product->marca,
            '',
            $product->medida,
            $product->category->name ?? '',
            $product->description,
            '',
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
}
