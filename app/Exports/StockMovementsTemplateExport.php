<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Plantilla para cargar inventario por Excel (ver App\Imports\StockMovementsImport). Las filas de ejemplo usan
 * productos reales del sistema para que se pueda probar de inmediato.
 */
class StockMovementsTemplateExport implements WithMultipleSheets
{
    /**
     * @param  array<int, array{sku: string, unit: string, big_unit: ?string}>  $samples  productos reales para los ejemplos
     */
    public function __construct(private readonly array $samples) {}

    public function sheets(): array
    {
        return [
            new StockMovementsTemplateSheet($this->samples),
            new StockMovementsInstructionsSheet(),
        ];
    }
}
