<?php

namespace App\Http\Controllers;

use App\Exports\GeneralProductsExport;
use App\Exports\GeneralProductsTemplateExport;
use App\Http\Requests\ImportProductsRequest;
use App\Imports\GeneralProductsImport;
use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Exportar / importar el catálogo de productos en Excel. Los permisos
 * (productos.exportar / productos.importar) se exigen desde las rutas.
 */
class ProductExcelController extends Controller
{
    /**
     * Exporta con los mismos filtros de búsqueda que tiene el listado (buscar, categoría),
     * así lo que se ve filtrado en pantalla es lo que se descarga. Las columnas son las
     * mismas y en el mismo orden que la plantilla de importación (ver GeneralProductsExport),
     * para poder editar el archivo y volver a subirlo con el mismo importador.
     */
    public function export(Request $request)
    {
        $products = QueryBuilder::for(Product::class)
            ->with('category', 'units')
            ->allowedFilters([
                AllowedFilter::scope('buscar', 'search'),
                AllowedFilter::exact('currentCategory', 'category_id'),
            ])
            ->orderBy('name')
            ->getEloquentBuilder(); // FromQuery lee por bloques desde el Eloquent Builder

        return Excel::download(new GeneralProductsExport($products), 'productos_'.now()->format('Y-m-d_His').'.xlsx');
    }

    /**
     * Plantilla generada al momento: incluye las unidades de medida reales del sistema en un desplegable.
     */
    public function template()
    {
        $units = Unit::where('is_active', true)->orderBy('name')->pluck('name');
        $categories = Category::orderBy('name')->pluck('name');

        return Excel::download(new GeneralProductsTemplateExport($units, $categories), 'plantilla_productos.xlsx');
    }

    public function import(ImportProductsRequest $request)
    {
        $import = app(GeneralProductsImport::class, [
            'warehouseId' => (int) $request->input('warehouse_id'),
            'userId' => (int) $request->user()->id,
        ]);

        try {
            $import->import($request->file('file')->getRealPath());
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'No se pudo leer el archivo. Verifica que sea un Excel válido y que use la plantilla.');
        }

        if ($import->errors !== []) {
            $shown = array_slice($import->errors, 0, 50);
            $extra = count($import->errors) - count($shown);

            return back()
                ->with('error', 'No se importó nada: el archivo tiene '.count($import->errors).' error(es). Corrígelos y vuelve a subirlo.')
                ->with('import_errors', $extra > 0 ? [...$shown, "…y {$extra} error(es) más."] : $shown);
        }

        if ($import->created + $import->updated === 0) {
            return back()->with('error', 'El archivo no tiene filas con datos para importar (revisa también la columna "Importado").');
        }

        $message = "Importación completada: {$import->created} producto(s) creado(s) y {$import->updated} actualizado(s).";

        if ($import->stockLoaded > 0) {
            $message .= " Se cargó stock inicial en {$import->stockLoaded} producto(s).";
        }

        if ($import->skipped > 0) {
            $message .= " Se saltaron {$import->skipped} fila(s) ya marcadas como \"Importado\".";
        }

        return redirect()->route('products.index')->with('success', $message);
    }
}
