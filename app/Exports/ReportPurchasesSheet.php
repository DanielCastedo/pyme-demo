<?php

namespace App\Exports;

use App\Models\Purchase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReportPurchasesSheet implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    public function __construct(protected Carbon $from, protected Carbon $to) {}

    public function collection(): Collection
    {
        return Purchase::with('supplier')
            ->where('status', 'recibida')
            ->whereBetween('purchase_date', [$this->from, $this->to])
            ->orderBy('purchase_date')
            ->get();
    }

    public function headings(): array
    {
        return ['N° Compra', 'Fecha', 'Proveedor', 'N° Factura', 'Método de Pago', 'Subtotal', 'Descuento', 'Total'];
    }

    public function map($purchase): array
    {
        $paymentLabels = ['efectivo' => 'Efectivo', 'qr' => 'QR', 'tarjeta' => 'Tarjeta', 'transferencia' => 'Transferencia', 'credito' => 'Crédito', 'otro' => 'Otro'];

        return [
            $purchase->purchase_number,
            $purchase->purchase_date->format('d/m/Y'),
            $purchase->supplier_name ?? '—',
            $purchase->invoice_number ?? '—',
            $paymentLabels[$purchase->payment_method] ?? $purchase->payment_method,
            $purchase->subtotal,
            $purchase->discount,
            $purchase->total,
        ];
    }

    public function title(): string
    {
        return 'Compras';
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}
