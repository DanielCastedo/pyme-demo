<?php

namespace App\Exports;

use App\Models\ScanSession;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ScanSessionExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    public function __construct(protected ScanSession $session) {}

    public function collection(): Collection
    {
        return $this->session->items()->orderBy('code')->get();
    }

    public function headings(): array
    {
        return ['codigo', 'producto_encontrado', 'cantidad_contada'];
    }

    public function map($item): array
    {
        return [
            $item->code,
            $item->product_name ?? 'NO ENCONTRADO — revisar SKU',
            $item->quantity,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}
