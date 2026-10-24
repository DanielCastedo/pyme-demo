<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Plantilla de la "Importación general" de productos, generada al momento con las unidades
 * de medida y categorías reales del sistema en sendos desplegables.
 */
class GeneralProductsTemplateExport implements WithMultipleSheets
{
    /**
     * @param  Collection<int, string>  $units  nombres de unidades de medida activas
     * @param  Collection<int, string>  $categories  nombres de categorías existentes
     */
    public function __construct(private readonly Collection $units, private readonly Collection $categories) {}

    public function sheets(): array
    {
        return [
            new GeneralProductsTemplateSheet($this->units->first(), $this->categories->first()),
            new GeneralProductsInstructionsSheet(),
            new GeneralProductsUnitsSheet($this->units),
            new GeneralProductsCategoriesSheet($this->categories),
        ];
    }
}
