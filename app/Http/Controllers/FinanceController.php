<?php

namespace App\Http\Controllers;

use App\Exports\FinanceProductProfitExport;
use App\Exports\FinanceReportExport;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\FinanceReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Finanzas: resumen de ventas, compras, gastos y ganancia, con todos sus movimientos, y la ganancia por producto.
 * Acceso con el permiso "reportes.ver" (ver rutas).
 */
class FinanceController extends Controller
{
    public function __construct(private FinanceReportService $reports) {}

    public function report(Request $request)
    {
        $f = FinanceReportService::filtersFrom($request);

        return view('finance.report', [
            'f' => $f,
            'summary' => $this->reports->summary($f),
            'movements' => $this->reports->movements($f)->withQueryString(),
            'byUser' => $this->reports->salesByUser($f)->withQueryString(),
            'filterData' => $this->filterData(),
        ]);
    }

    public function products(Request $request)
    {
        $f = FinanceReportService::filtersFrom($request);
        $order = in_array($request->input('order'), ['ganancia', 'ventas', 'cantidad', 'compras'], true) ? $request->input('order') : 'ganancia';

        return view('finance.products', [
            'f' => $f,
            'order' => $order,
            'summary' => $this->reports->summary($f),
            'products' => $this->reports->productProfit($f, $order)->withQueryString(),
            'filterData' => $this->filterData(),
        ]);
    }

    public function reportPdf(Request $request)
    {
        $f = FinanceReportService::filtersFrom($request);
        $summary = $this->reports->summary($f);
        $movements = $this->reports->movements($f, 500)->items();
        $byUser = $this->reports->salesByUser($f, 100)->items();
        $company = \App\Models\Company::first();
        $filters = $f;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('finance.pdf-report', compact('f', 'summary', 'movements', 'byUser', 'company', 'filters'));
        $pdf->setPaper('letter', 'portrait');

        return $pdf->stream("Reporte-Financiero-".date('Ymd').".pdf");
    }

    public function reportExcel(Request $request)
    {
        $f = FinanceReportService::filtersFrom($request);

        return Excel::download(
            new FinanceReportExport($this->reports, $f),
            'Reporte-Financiero-'.date('Ymd').'.xlsx'
        );
    }

    public function productsPdf(Request $request)
    {
        $f = FinanceReportService::filtersFrom($request);
        $order = in_array($request->input('order'), ['ganancia', 'ventas', 'cantidad', 'compras'], true) ? $request->input('order') : 'ganancia';
        $summary = $this->reports->summary($f);
        $products = $this->reports->productProfit($f, $order, 500)->items();
        $company = \App\Models\Company::first();
        $filters = $f;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('finance.pdf-products', compact('f', 'summary', 'products', 'company', 'filters'));
        $pdf->setPaper('letter', 'portrait');

        return $pdf->stream("Ganancias-Por-Producto-".date('Ymd').".pdf");
    }

    public function productsExcel(Request $request)
    {
        $f = FinanceReportService::filtersFrom($request);
        $order = in_array($request->input('order'), ['ganancia', 'ventas', 'cantidad', 'compras'], true) ? $request->input('order') : 'ganancia';

        return Excel::download(
            new FinanceProductProfitExport($this->reports, $f, $order),
            'Ganancias-Por-Producto-'.date('Ymd').'.xlsx'
        );
    }

    /**
     * Listas para los filtros (sucursal, almacén, producto, cliente y usuario).
     */
    private function filterData(): array
    {
        return [
            'branches' => Branch::orderBy('name')->pluck('name', 'id'),
            'warehouses' => Warehouse::orderBy('name')->get(['id', 'name', 'branche_id']),
            'products' => DB::table('products')->orderBy('name')->get(['id', 'name', 'sku']),
            'customers' => Customer::orderBy('name')->pluck('name', 'id'),
            'users' => User::orderBy('name')->pluck('name', 'id'),
        ];
    }
}
