<?php

namespace App\Exports;

use App\Models\Expense;
use App\Models\Purchase;
use App\Models\Sale;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReportSummarySheet implements FromCollection, WithStyles, WithTitle
{
    public function __construct(protected Carbon $from, protected Carbon $to) {}

    public function collection(): Collection
    {
        $totalSales = Sale::where('status', 'completada')->whereBetween('sale_date', [$this->from, $this->to])->sum('total');
        // $totalPurchases = Purchase::where('status', 'recibida')->whereBetween('purchase_date', [$this->from, $this->to])->sum('total');
        // $totalExpenses = Expense::where('status', 'pagado')->whereBetween('expense_date', [$this->from, $this->to])->sum('amount');
        // $grossProfit = $totalSales - $totalPurchases;
        // $netProfit = $totalSales - $totalPurchases - $totalExpenses;

        return collect([
            ['Reporte Financiero', ''],
            ['Período', $this->from->format('d/m/Y').' al '.$this->to->format('d/m/Y')],
            ['', ''],
            ['Concepto', 'Monto'],
            ['Ventas Totales', number_format($totalSales, 2)],
            // ['Compras Totales', number_format($totalPurchases, 2)],
            // ['Gastos Totales', number_format($totalExpenses, 2)],
            // ['Utilidad Bruta (Ventas - Compras)', number_format($grossProfit, 2)],
            // ['Utilidad Neta (Ventas - Compras - Gastos)', number_format($netProfit, 2)],
        ]);
    }

    public function title(): string
    {
        return 'Resumen';
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->getColumnDimension('A')->setWidth(40);
        $sheet->getColumnDimension('B')->setWidth(20);

        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
            4 => ['font' => ['bold' => true]],
            9 => ['font' => ['bold' => true]],
        ];
    }
}
