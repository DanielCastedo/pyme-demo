<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Lista de unidades de medida vigentes; alimenta el desplegable de la hoja "Productos".
 */
class GeneralProductsUnitsSheet implements FromCollection, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    /**
     * @param  Collection<int, string>  $units
     */
    public function __construct(private readonly Collection $units) {}

    public function title(): string
    {
        return 'Unidades';
    }

    public function headings(): array
    {
        return ['Unidades disponibles'];
    }

    public function collection(): Collection
    {
        return $this->units->map(fn ($name) => [$name]);
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}
