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
 * Lista de categorías vigentes; alimenta el desplegable (opcional) de la hoja "Productos".
 */
class GeneralProductsCategoriesSheet implements FromCollection, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    /**
     * @param  Collection<int, string>  $categories
     */
    public function __construct(private readonly Collection $categories) {}

    public function title(): string
    {
        return 'Categorias';
    }

    public function headings(): array
    {
        return ['Categorías disponibles'];
    }

    public function collection(): Collection
    {
        return $this->categories->map(fn ($name) => [$name]);
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}
