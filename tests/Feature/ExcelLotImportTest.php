<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\StockBatch;
use App\Models\Unit;
use App\Models\Warehouse;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/*
|--------------------------------------------------------------------------
| Importación desde Excel con lotes, vencimientos y presentaciones
|--------------------------------------------------------------------------
| Carga masiva del inventario que la farmacia lleva en hojas de cálculo:
| varios lotes por medicamento (una fila por lote), cada uno con su costo y
| vencimiento, y su presentación (Caja x100) en la importación de productos.
*/

beforeEach(function () {
    $this->seed(Database\Seeders\TypeSeeder::class);
    foreach (['Unidad', 'Tableta', 'Frasco', 'Sobre', 'Caja'] as $name) {
        Unit::firstOrCreate(['name' => $name], ['abbreviation' => mb_substr($name, 0, 3), 'is_active' => true]);
    }
    $this->warehouse = Warehouse::factory()->create(['branche_id' => testBranch()->id]);
});

/** Guarda una descarga (BinaryFileResponse) como archivo subible. */
function downloadedFile($response, string $name): UploadedFile
{
    $copy = tempnam(sys_get_temp_dir(), 'tpl').'.xlsx';
    copy($response->getFile()->getPathname(), $copy);

    return new UploadedFile($copy, $name, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
}

/** .xlsx con la cabecera de la plantilla de productos (incluidas las columnas nuevas) y las filas dadas. */
function productLotFile(array $rows): UploadedFile
{
    $spreadsheet = new Spreadsheet();
    $spreadsheet->getActiveSheet()->fromArray(
        ['Codigo', 'Item', 'Cantidad', 'Precio Venta U', 'Precio Compra U', 'Unidad', 'Color', 'Marca', 'Foto', 'Medida', 'Categoria', 'Descripcion', 'Importado',
            'Lote', 'Vence', 'Presentacion', 'Contiene', 'Precio Presentacion'],
        null, 'A1'
    );
    foreach ($rows as $i => $row) {
        $spreadsheet->getActiveSheet()->fromArray($row, null, 'A'.($i + 2));
    }
    $path = tempnam(sys_get_temp_dir(), 'lot').'.xlsx';
    (new Xlsx($spreadsheet))->save($path);

    return new UploadedFile($path, 'productos.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
}

describe('plantilla de productos', function () {
    it('trae las columnas de lote, vencimiento y presentación con ejemplos de farmacia, y sus ejemplos se importan tal cual', function () {
        $user = userWith('ver products', 'productos.importar');

        $response = $this->actingAs($user)->get(route('products.template'))->assertOk();
        $file = downloadedFile($response, 'plantilla_productos.xlsx');

        $sheet = IOFactory::load($file->getPathname())->getSheetByName('Productos');
        expect($sheet->rangeToArray('N1:R1')[0])->toBe(['Lote', 'Vence', 'Presentacion', 'Contiene', 'Precio Presentacion'])
            ->and($sheet->getCell('B2')->getValue())->toBe('Paracetamol 500mg')
            ->and($sheet->getCell('A3')->getValue())->toBe('MED-0001'); // el segundo lote del mismo medicamento

        $this->actingAs($user)->post(route('products.import'), ['file' => $file, 'warehouse_id' => $this->warehouse->id])
            ->assertRedirect(route('products.index'))
            ->assertSessionHas('success');

        $paracetamol = Product::where('sku', 'MED-0001')->firstOrFail();
        $lots = StockBatch::where('product_id', $paracetamol->id)->orderBy('expires_at')->get();
        $box = $paracetamol->units()->where('name', 'Caja')->firstOrFail();

        expect($lots->pluck('lot_number')->all())->toBe(['L2405', 'L2411'])
            ->and($lots->map(fn ($l) => (float) $l->qty_initial)->all())->toBe([300.0, 200.0])
            ->and($lots->map(fn ($l) => (float) $l->unit_cost)->all())->toBe([0.25, 0.27])
            ->and($lots->every(fn ($l) => $l->expires_at !== null))->toBeTrue()
            ->and($box->factor)->toBe(100)
            ->and((float) $box->price)->toBe(40.0)
            ->and($box->barcode)->toBe('MED-0001-CAJA')
            ->and(Product::where('sku', 'CUR-0001')->firstOrFail()->batches()->count())->toBe(0); // sin stock: sin lote
    });
});

describe('importación de productos con lotes', function () {
    it('acepta el vencimiento escrito como texto dd/mm/aaaa y redondea el costo a 2 decimales', function () {
        $user = userWith('ver products', 'productos.importar');

        $this->actingAs($user)->post(route('products.import'), ['file' => productLotFile([
            ['AMX-01', 'Amoxicilina 500mg', 30, 2.5, 0.8333, 'Unidad', '', '', '', '', '', '', '', 'AMX-77', '31/12/2027', '', '', ''],
        ]), 'warehouse_id' => $this->warehouse->id])->assertSessionHas('success');

        $lot = StockBatch::sole();
        expect($lot->lot_number)->toBe('AMX-77')
            ->and($lot->expires_at->toDateString())->toBe('2027-12-31')
            ->and((float) $lot->unit_cost)->toBe(0.83);
    });

    it('rechaza todo si una fila repetida cambia el Item, si hay Lote sin Cantidad o si la fecha no es válida', function (array $badRow, string $message) {
        $user = userWith('ver products', 'productos.importar');

        $this->actingAs($user)->post(route('products.import'), ['file' => productLotFile([
            ['AMX-01', 'Amoxicilina 500mg', 30, 2.5, 1, 'Unidad', '', '', '', '', '', '', '', '', '', '', '', ''],
            $badRow,
        ]), 'warehouse_id' => $this->warehouse->id])
            ->assertSessionHas('error')
            ->assertSessionHas('import_errors', fn ($errors) => collect($errors)->contains(fn ($e) => str_contains($e, $message)));

        expect(Product::count())->toBe(0)->and(StockBatch::count())->toBe(0);
    })->with([
        'otro Item en la fila repetida' => [['AMX-01', 'Otro nombre', 10, '', 1, '', '', '', '', '', '', '', '', 'L1', '', '', '', ''], 'con otro Item'],
        'fila repetida sin Cantidad' => [['AMX-01', '', '', '', '', '', '', '', '', '', '', '', '', 'L1', '', '', '', ''], 'necesita Cantidad'],
        'fecha inválida' => [['IBU-01', 'Ibuprofeno', 5, 1, 0.5, 'Unidad', '', '', '', '', '', '', '', '', '32/13/2027', '', '', ''], 'no es válida'],
        'presentación incompleta' => [['IBU-01', 'Ibuprofeno', 0, 1, '', 'Unidad', '', '', '', '', '', '', '', '', '', 'Caja', '', ''], 'completa los tres datos'],
    ]);
});

describe('plantilla e importación de movimientos de stock', function () {
    it('la plantilla trae lote y vence, y sus ejemplos se importan con su lote y vencimiento', function () {
        $user = userWith('ver stock-movements', 'kardex.importar');
        $product = Product::factory()->create(['sku' => 'MED-0002', 'category_id' => Category::factory()->create()->id]);
        ProductUnit::factory()->create(['product_id' => $product->id, 'name' => 'Frasco', 'factor' => 1, 'is_base' => true]);

        $response = $this->actingAs($user)->get(route('stock-movements.template'))->assertOk();
        $file = downloadedFile($response, 'plantilla_inventario.xlsx');
        expect(IOFactory::load($file->getPathname())->getActiveSheet()->rangeToArray('A1:G1')[0])
            ->toBe(['sku', 'tipo', 'cantidad', 'unidad', 'costo_unitario', 'lote', 'vence']);

        $this->actingAs($user)->post(route('stock-movements.import'), ['file' => $file, 'warehouse_id' => $this->warehouse->id])
            ->assertSessionHasNoErrors()
            ->assertSessionMissing('import_errors')
            ->assertSessionHas('success');

        // Con un solo producto de muestra, los ejemplos usan ese producto: 20 + 5 entran y 2 salen.
        $lot = StockBatch::where('product_id', $product->id)->where('lot_number', 'L2405')->firstOrFail();
        expect((float) $lot->unit_cost)->toBe(22.0)
            ->and($lot->expires_at)->not->toBeNull()
            ->and((float) \App\Models\ProductWarehouse::where('product_id', $product->id)->value('stock'))->toBe(23.0);
    });

    it('no acepta lote ni vencimiento en una salida', function () {
        $user = userWith('ver stock-movements', 'kardex.importar');
        $product = Product::factory()->create(['sku' => 'IBU-01', 'category_id' => Category::factory()->create()->id]);
        ProductUnit::factory()->create(['product_id' => $product->id, 'name' => 'Unidad', 'factor' => 1, 'is_base' => true]);

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray([
            ['sku', 'tipo', 'cantidad', 'unidad', 'costo_unitario', 'lote', 'vence'],
            ['IBU-01', 'entrada', 10, '', 1, '', ''],
            ['IBU-01', 'salida', 2, '', '', 'L1', ''],
        ]);
        $path = tempnam(sys_get_temp_dir(), 'inv').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        $this->actingAs($user)->post(route('stock-movements.import'), [
            'file' => new UploadedFile($path, 'inventario.xlsx', null, null, true),
            'warehouse_id' => $this->warehouse->id,
        ])->assertSessionHas('import_errors', fn ($errors) => collect($errors)->contains(fn ($e) => str_contains($e, 'Fila 3') && str_contains($e, 'solo para entradas')));

        expect(StockBatch::count())->toBe(0);
    });
});
