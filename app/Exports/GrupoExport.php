<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class GrupoExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    /**
     * Obtenemos todos los grupos con sus relaciones
     */
    public function collection() {}

    /**
     * Definimos los encabezados (títulos) de las columnas en el Excel
     */
    public function headings(): array
    {
        return [
        ];
    }

    /**
     * Mapeamos cómo se llenará cada fila con los datos
     */
    public function map($grupo): array
    {
        return [
        ];
    }
}
