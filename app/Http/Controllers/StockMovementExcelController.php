<?php

namespace App\Http\Controllers;

use App\Exports\StockMovementsTemplateExport;
use App\Http\Requests\ImportStockMovementsRequest;
use App\Imports\StockMovementsImport;
use App\Models\Product;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Carga de inventario por Excel: plantilla de ejemplo e importación (ver App\Imports\StockMovementsImport).
 */
class StockMovementExcelController extends Controller
{
    /**
     * Plantilla generada al momento, con productos reales del sistema en las filas de ejemplo.
     */
    public function template()
    {
        $samples = Product::with('units')
            ->whereHas('units')
            ->whereDoesntHave('components')
            ->orderBy('id')
            ->limit(3)
            ->get()
            ->map(function ($product) {
                $base = $product->units->firstWhere('is_base', true) ?? $product->units->first();
                $big = $product->units->where('id', '!=', $base->id)->sortByDesc('factor')->first();

                return ['sku' => $product->sku, 'unit' => $base->name, 'big_unit' => $big?->name];
            })
            ->all();

        return Excel::download(new StockMovementsTemplateExport($samples), 'plantilla_inventario.xlsx');
    }

    public function import(ImportStockMovementsRequest $request)
    {
        $import = new StockMovementsImport((int) $request->input('warehouse_id'), (int) $request->user()->id, $request->input('note'));

        try {
            Excel::import($import, $request->file('file'));
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'No se pudo procesar el archivo: '.$e->getMessage());
        }

        if ($import->errors !== []) {
            $shown = array_slice($import->errors, 0, 50);
            $extra = count($import->errors) - count($shown);

            return back()
                ->with('error', 'No se cargó nada: el archivo tiene '.count($import->errors).' error(es). Corrígelos y vuelve a subirlo.')
                ->with('import_errors', $extra > 0 ? [...$shown, "…y {$extra} error(es) más."] : $shown);
        }

        if ($import->entries + $import->exits === 0) {
            return back()->with('error', 'El archivo no tiene filas con datos para cargar.');
        }

        $parts = [];
        if ($import->entries > 0) {
            $parts[] = "{$import->entries} entrada(s)";
        }
        if ($import->exits > 0) {
            $parts[] = "{$import->exits} salida(s)";
        }

        return redirect()->route('stock-movements.index')
            ->with('success', 'Inventario cargado: '.implode(' y ', $parts).' registradas en el almacén.');
    }
}
