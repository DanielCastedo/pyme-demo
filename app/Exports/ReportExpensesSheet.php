<?php

namespace App\Exports;

use App\Models\Expense;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReportExpensesSheet implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    public function __construct(protected Carbon $from, protected Carbon $to) {}

    public function collection(): Collection
    {
        return Expense::with('category')
            ->where('status', 'pagado')
            ->whereBetween('expense_date', [$this->from, $this->to])
            ->orderBy('expense_date')
            ->get();
    }

    public function headings(): array
    {
        return ['N° Gasto', 'Fecha', 'Concepto', 'Categoría', 'Proveedor/Beneficiario', 'Método de Pago', 'Monto'];
    }

    public function map($expense): array
    {
        $paymentLabels = ['efectivo' => 'Efectivo', 'qr' => 'QR', 'tarjeta' => 'Tarjeta', 'transferencia' => 'Transferencia', 'otro' => 'Otro'];

        return [
            $expense->expense_number,
            $expense->expense_date->format('d/m/Y'),
            $expense->concept,
            $expense->category->name ?? '—',
            $expense->supplier_name ?? '—',
            $paymentLabels[$expense->payment_method] ?? $expense->payment_method,
            $expense->amount,
        ];
    }

    public function title(): string
    {
        return 'Gastos';
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}
