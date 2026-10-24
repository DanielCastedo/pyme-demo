<?php

use App\Exports\GeneralProductsExport;
use App\Exports\GeneralProductsTemplateExport;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Unit;
use App\Models\Warehouse;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

beforeEach(function () {
    testBranch();
});

// =========================================================================
// 1. AUTENTICACIÓN Y PERMISOS
// =========================================================================

describe('permisos de exportación e importación', function () {
    it('redirige a login si el usuario no está autenticado', function () {
        $this->get(route('products.export'))->assertRedirect(route('login'));
        $this->get(route('products.template'))->assertRedirect(route('login'));
        $this->post(route('products.import'))->assertRedirect(route('login'));
    });

    it('deniega con 403 si el usuario carece de permisos específicos', function () {
        $user = userWith('ver products'); // Solo tiene ver, pero no exportar ni importar

        $this->actingAs($user)->get(route('products.export'))->assertForbidden();
        $this->actingAs($user)->get(route('products.template'))->assertForbidden();
        $this->actingAs($user)->post(route('products.import'))->assertForbidden();
    });
});

// =========================================================================
// 2. EXPORTACIÓN Y PLANTILLA
// =========================================================================

describe('exportación y descarga de plantilla', function () {
    it('descarga el archivo Excel con los productos del catálogo', function () {
        Excel::fake();
        $user = userWith('ver products', 'productos.exportar');
        Product::factory()->count(3)->create();

        $response = $this->actingAs($user)->get(route('products.export'));

        $response->assertOk();
        Excel::assertDownloaded('productos_'.now()->format('Y-m-d_His').'.xlsx', function (GeneralProductsExport $export) {
            return true;
        });
    });

    it('incluye la Descripcion del producto en el archivo exportado', function () {
        Excel::fake();
        $user = userWith('ver products', 'productos.exportar');
        $product = Product::factory()->create(['description' => 'Descripción de prueba']);

        $this->actingAs($user)->get(route('products.export'))->assertOk();

        Excel::assertDownloaded('productos_'.now()->format('Y-m-d_His').'.xlsx', function (GeneralProductsExport $export) use ($product) {
            expect($export->headings())->toContain('Descripcion');
            $row = $export->map($product->load('units', 'category'));
            expect($row[array_search('Descripcion', $export->headings(), true)])->toBe('Descripción de prueba');

            return true;
        });
    });

    it('descarga la plantilla Excel con las unidades de medida predefinidas', function () {
        Excel::fake();
        $user = userWith('ver products', 'productos.importar');

        $response = $this->actingAs($user)->get(route('products.template'));

        $response->assertOk();
        Excel::assertDownloaded('plantilla_productos.xlsx', function (GeneralProductsTemplateExport $export) {
            return true;
        });
    });
});

// =========================================================================
// 3. IMPORTACIÓN
// =========================================================================

describe('validaciones de importación', function () {
    it('falla si no se adjunta ningún archivo', function () {
        $user = userWith('ver products', 'productos.importar');

        $response = $this->actingAs($user)->post(route('products.import'), []);

        $response->assertSessionHasErrors(['file']);
    });

    it('falla si el archivo no tiene formato Excel válido', function () {
        $user = userWith('ver products', 'productos.importar');
        $invalidFile = UploadedFile::fake()->create('documento.pdf', 100, 'application/pdf');

        $response = $this->actingAs($user)->post(route('products.import'), [
            'file' => $invalidFile,
        ]);

        $response->assertSessionHasErrors(['file']);
    });

    it('falla si no se elige el almacén', function () {
        $user = userWith('ver products', 'productos.importar');
        $file = buildGeneralImportFile([
            ['MAR-0001', 'Martillo', 5, 55, 30, 'Unidad', 'Rojo', 'Stanley', '', '16oz', '', '', ''],
        ]);

        $response = $this->actingAs($user)->post(route('products.import'), ['file' => $file]);

        $response->assertSessionHasErrors(['warehouse_id']);
    });
});

// =========================================================================
// 4. IMPORTACIÓN GENERAL: FLUJO COMPLETO
// =========================================================================

describe('importación general: flujo completo', function () {
    it('crea productos nuevos con marca/color/medida, su unidad base, la foto pegada en la celda y carga el stock inicial en el almacén elegido', function () {
        $user = userWith('ver products', 'productos.importar');
        Unit::firstOrCreate(['name' => 'Unidad'], ['abbreviation' => 'und', 'is_active' => true]);
        \App\Models\Type::firstOrCreate(['code' => 'ENT'], ['name' => 'Entrada de Stock', 'sign' => '+']);
        $warehouse = Warehouse::factory()->create(['branche_id' => testBranch()->id]);

        $file = buildGeneralImportFile([
            ['MAR-0001', 'Martillo de uña 16oz', 10, 55.50, 35, 'Unidad', 'Rojo', 'Stanley', '', '16oz', '', 'Martillo con cabeza de acero forjado.', ''],
        ], photoRow: 2);

        $response = $this->actingAs($user)->post(route('products.import'), [
            'file' => $file,
            'warehouse_id' => $warehouse->id,
        ]);

        $response->assertRedirect(route('products.index'));
        $response->assertSessionHas('success');

        $product = Product::where('sku', 'MAR-0001')->firstOrFail();
        expect($product->name)->toBe('Martillo de uña 16oz');
        expect($product->color)->toBe('Rojo');
        expect($product->marca)->toBe('Stanley');
        expect($product->medida)->toBe('16oz');
        expect($product->description)->toBe('Martillo con cabeza de acero forjado.');
        expect($product->category->name)->toBe('Importación General');
        expect($product->photo)->not->toBeNull();
        expect(\Illuminate\Support\Facades\Storage::disk('uploads')->exists($product->photo))->toBeTrue();
        // El nombre del archivo es el ID del producto (igual que al crear/editar desde el CRUD), no uno al azar.
        expect($product->photo)->toBe("products/{$product->id}.webp");

        $unit = $product->units()->where('is_base', true)->firstOrFail();
        expect((float) $unit->price)->toBe(55.50);
        expect($unit->barcode)->toBe('MAR-0001');

        $stock = \App\Models\ProductWarehouse::where('product_id', $product->id)->where('warehouse_id', $warehouse->id)->firstOrFail();
        expect((float) $stock->stock)->toBe(10.0);
        expect((float) $stock->avg_cost)->toBe(35.0);
    });

    it('actualiza un producto existente por Código sin tocar su categoría si la fila no trae Categoria, y no carga stock si la Cantidad viene vacía', function () {
        $user = userWith('ver products', 'productos.importar');
        Unit::firstOrCreate(['name' => 'Unidad'], ['abbreviation' => 'und', 'is_active' => true]);
        $warehouse = Warehouse::factory()->create(['branche_id' => testBranch()->id]);
        $original = \App\Models\Category::factory()->create(['name' => 'Ferretería']);
        $product = Product::factory()->create(['sku' => 'FG-0009', 'name' => 'Nombre viejo', 'category_id' => $original->id, 'description' => 'Descripción original']);
        ProductUnit::factory()->create(['product_id' => $product->id, 'is_base' => true, 'name' => 'Unidad', 'factor' => 1, 'price' => 10]);

        $file = buildGeneralImportFile([
            ['FG-0009', 'Nombre actualizado', '', 25, '', 'Unidad', 'Azul', '', '', '', '', '', ''],
        ]);

        $this->actingAs($user)->post(route('products.import'), [
            'file' => $file,
            'warehouse_id' => $warehouse->id,
        ])->assertRedirect(route('products.index'));

        $product->refresh();
        expect($product->name)->toBe('Nombre actualizado');
        expect($product->color)->toBe('Azul');
        expect($product->category_id)->toBe($original->id); // no se toca
        expect($product->description)->toBe('Descripción original'); // vacía en el Excel: tampoco se toca
        expect((float) $product->units()->where('is_base', true)->firstOrFail()->price)->toBe(25.0);
        expect(\App\Models\ProductWarehouse::where('product_id', $product->id)->exists())->toBeFalse();
    });

    it('usa la Categoria del Excel para un producto nuevo, creándola si no existe', function () {
        $user = userWith('ver products', 'productos.importar');
        Unit::firstOrCreate(['name' => 'Unidad'], ['abbreviation' => 'und', 'is_active' => true]);
        $warehouse = Warehouse::factory()->create(['branche_id' => testBranch()->id]);

        expect(\App\Models\Category::where('name', 'Herramientas Eléctricas')->exists())->toBeFalse();

        $file = buildGeneralImportFile([
            ['HE-0001', 'Amoladora', '', 300, '', 'Unidad', '', '', '', '', 'Herramientas Eléctricas', '', ''],
        ]);

        $this->actingAs($user)->post(route('products.import'), [
            'file' => $file,
            'warehouse_id' => $warehouse->id,
        ])->assertRedirect(route('products.index'));

        $product = Product::where('sku', 'HE-0001')->firstOrFail();
        expect($product->category->name)->toBe('Herramientas Eléctricas');
    });

    it('cambia la categoría de un producto existente cuando la fila sí trae Categoria', function () {
        $user = userWith('ver products', 'productos.importar');
        Unit::firstOrCreate(['name' => 'Unidad'], ['abbreviation' => 'und', 'is_active' => true]);
        $warehouse = Warehouse::factory()->create(['branche_id' => testBranch()->id]);
        $original = \App\Models\Category::factory()->create(['name' => 'Ferretería']);
        $product = Product::factory()->create(['sku' => 'FG-0010', 'category_id' => $original->id]);
        ProductUnit::factory()->create(['product_id' => $product->id, 'is_base' => true, 'name' => 'Unidad', 'factor' => 1, 'price' => 10]);

        $file = buildGeneralImportFile([
            ['FG-0010', 'Producto reclasificado', '', 15, '', 'Unidad', '', '', '', '', 'Pinturas y Acabados', '', ''],
        ]);

        $this->actingAs($user)->post(route('products.import'), [
            'file' => $file,
            'warehouse_id' => $warehouse->id,
        ])->assertRedirect(route('products.index'));

        $product->refresh();
        expect($product->category->name)->toBe('Pinturas y Acabados');
        expect($product->category_id)->not->toBe($original->id);
    });

    it('salta las filas marcadas "Importado" sin validarlas ni tocarlas', function () {
        $user = userWith('ver products', 'productos.importar');
        Unit::firstOrCreate(['name' => 'Unidad'], ['abbreviation' => 'und', 'is_active' => true]);
        $warehouse = Warehouse::factory()->create(['branche_id' => testBranch()->id]);

        $file = buildGeneralImportFile([
            ['', '', '', '', '', '', '', '', '', '', '', '', 'Importado'], // fila inválida, pero ya marcada -> se salta
            ['MAR-0002', 'Destornillador', '', 18, '', 'Unidad', '', '', '', '', '', '', ''],
        ]);

        $response = $this->actingAs($user)->post(route('products.import'), [
            'file' => $file,
            'warehouse_id' => $warehouse->id,
        ]);

        $response->assertRedirect(route('products.index'));
        $response->assertSessionHasNoErrors();
        expect(Product::where('sku', 'MAR-0002')->exists())->toBeTrue();
    });

    it('no guarda nada si una fila (no marcada "Importado") tiene errores', function () {
        $user = userWith('ver products', 'productos.importar');
        Unit::firstOrCreate(['name' => 'Unidad'], ['abbreviation' => 'und', 'is_active' => true]);
        $warehouse = Warehouse::factory()->create(['branche_id' => testBranch()->id]);

        $file = buildGeneralImportFile([
            ['MAR-0003', 'Producto válido', '', 20, '', 'Unidad', '', '', '', '', '', '', ''],
            ['', 'Sin código', '', 10, '', 'Unidad', '', '', '', '', '', '', ''], // falta el Código
        ]);

        $response = $this->actingAs($user)->post(route('products.import'), [
            'file' => $file,
            'warehouse_id' => $warehouse->id,
        ]);

        $response->assertSessionHas('error');
        expect(Product::where('sku', 'MAR-0003')->exists())->toBeFalse();
    });
});

/**
 * Arma un .xlsx real (encabezados + filas) para las pruebas de la Importación General,
 * en el mismo orden de columnas de la plantilla: Codigo, Item, Cantidad, Precio Venta U,
 * Precio Compra U, Unidad, Color, Marca, Foto, Medida, Categoria, Descripcion, Importado. Si se
 * pasa $photoRow, pega una imagen mínima (PNG generado en memoria) en la columna "Foto" de esa fila.
 */
function buildGeneralImportFile(array $rows, ?int $photoRow = null): UploadedFile
{
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    $sheet->fromArray(['Codigo', 'Item', 'Cantidad', 'Precio Venta U', 'Precio Compra U', 'Unidad', 'Color', 'Marca', 'Foto', 'Medida', 'Categoria', 'Descripcion', 'Importado'], null, 'A1');

    foreach ($rows as $i => $row) {
        $sheet->fromArray($row, null, 'A'.($i + 2));
    }

    if ($photoRow !== null) {
        $image = imagecreatetruecolor(10, 10);
        imagefill($image, 0, 0, imagecolorallocate($image, 200, 50, 50));

        $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing();
        $drawing->setImageResource($image);
        $drawing->setRenderingFunction(\PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing::RENDERING_PNG);
        $drawing->setMimeType(\PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing::MIMETYPE_PNG);
        $drawing->setCoordinates('I'.$photoRow);
        $drawing->setWorksheet($sheet);
    }

    $path = tempnam(sys_get_temp_dir(), 'gpi').'.xlsx';
    (new Xlsx($spreadsheet))->save($path);

    return new UploadedFile($path, 'importacion.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
}
