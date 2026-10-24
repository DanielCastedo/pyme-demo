<?php

namespace App\Exports;

use App\Models\Sale;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReportSalesSheet implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    public function __construct(protected Carbon $from, protected Carbon $to) {}

    public function collection(): Collection
    {
        return Sale::where('status', 'completada')
            ->whereBetween('sale_date', [$this->from, $this->to])
            ->orderBy('sale_date')
            ->get();
    }

    public function headings(): array
    {
        return ['N° Venta', 'Fecha', 'Cliente', 'Método de Pago', 'Subtotal', 'Descuento', 'Total'];
    }

    public function map($sale): array
    {
        $paymentLabels = ['efectivo' => 'Efectivo', 'qr' => 'QR', 'tarjeta' => 'Tarjeta', 'transferencia' => 'Transferencia', 'otro' => 'Otro'];

        return [
            $sale->sale_number,
            $sale->sale_date->format('d/m/Y'),
            $sale->customer_name ?? 'Consumidor final',
            $paymentLabels[$sale->payment_method] ?? $sale->payment_method,
            $sale->subtotal,
            $sale->discount,
            $sale->total,
        ];
    }

    public function title(): string
    {
        return 'Ventas';
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}
