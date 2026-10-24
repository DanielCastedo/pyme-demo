<?php

namespace App\Exports;

use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class FinancialReportExport implements WithMultipleSheets
{
    public function __construct(protected Carbon $from, protected Carbon $to) {}

    public function sheets(): array
    {
        return [
            'Resumen' => new ReportSummarySheet($this->from, $this->to),
            'Ventas' => new ReportSalesSheet($this->from, $this->to),
            // 'Compras'  => new ReportPurchasesSheet($this->from, $this->to),
            // 'Gastos'   => new ReportExpensesSheet($this->from, $this->to),
        ];
    }
}
