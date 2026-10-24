<?php

namespace App\Imports;

use App\Models\Category;
use App\Models\Product;
use App\Models\Type;
use App\Models\Unit;
use App\Services\ImageOptimizer;
use App\Services\StockMovementService;
use App\Support\Bc;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * "Importación general" de productos: un solo Excel con Código (SKU), Item (nombre), Cantidad,
 * Precio Venta U, Precio Compra U, Unidad, Color, Marca, Foto (imagen pegada en la celda), Medida,
 * Categoria y Descripcion. Por SKU: crea o actualiza el producto y su unidad de venta base (factor 1,
 * código de barras = SKU); si trae Cantidad, además carga ese stock inicial en el almacén elegido (con
 * su costo), por el mismo camino que un movimiento de entrada manual (App\Services\StockMovementService).
 *
 * Categoria es opcional: si la fila trae una, se usa (se crea si no existe) tanto al crear como al
 * actualizar el producto. Si viene vacía, un producto NUEVO cae en una categoría fija ("Importación
 * General"); uno YA EXISTENTE conserva la que ya tenía (igual que color/marca/medida/descripcion: vacío = no tocar).
 *
 * "Importado" es control del propio usuario: una fila con algo escrito ahí se salta (para poder volver a
 * subir el mismo archivo maestro sin duplicar lo ya cargado); vacía = se procesa.
 *
 * Regla "todo o nada": si una fila (de las no salteadas) falla, no se guarda nada.
 */
class GeneralProductsImport
{
    public const MAX_ROWS = 5000;

    public const DEFAULT_CATEGORY = 'Importación General';

    /** Encabezados esperados (normalizados: minúsculas, sin tildes) por cada dato. */
    private const HEADINGS = [
        'sku' => ['codigo'],
        'name' => ['item'],
        'quantity' => ['cantidad'],
        'sale_price' => ['precio venta u', 'precio venta'],
        'purchase_price' => ['precio compra u', 'precio compra'],
        'unit_name' => ['unidad'],
        'color' => ['color'],
        'marca' => ['marca'],
        'photo' => ['foto'],
        'medida' => ['medida'],
        'category' => ['categoria'],
        'description' => ['descripcion'],
        'imported' => ['importado'],
    ];

    /** @var array<int, string> */
    public array $errors = [];

    public int $created = 0;

    public int $updated = 0;

    public int $skipped = 0;

    public int $stockLoaded = 0;

    public function __construct(
        private readonly int $warehouseId,
        private readonly int $userId,
        private readonly ImageOptimizer $images,
    ) {}

    public function import(string $filePath): void
    {
        $this->allowLargeFiles();

        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();

        $columns = $this->mapHeadings($sheet);

        foreach (['sku', 'name', 'sale_price', 'unit_name'] as $required) {
            if (! isset($columns[$required])) {
                $this->errors[] = 'El archivo no tiene la columna "'.self::HEADINGS[$required][0].'".';
            }
        }

        if ($this->errors !== []) {
            return;
        }

        $photosByRow = $this->extractPhotosByRow($sheet, $columns['photo'] ?? null);
        $rows = $this->readRows($sheet, $columns, $photosByRow);

        if (count($rows) > self::MAX_ROWS) {
            $this->errors[] = 'El archivo tiene '.count($rows).' filas; el máximo por importación es '.self::MAX_ROWS.'.';

            return;
        }

        $units = Unit::where('is_active', true)->get();
        $prepared = [];
        $seenSkus = [];

        foreach ($rows as $row) {
            $line = $row['line'];
            $fail = function (string $message) use ($line) {
                $this->errors[] = "Fila {$line}: {$message}";
            };

            if ($this->isImported($row['imported'])) {
                $this->skipped++;

                continue;
            }

            $before = count($this->errors);

            if ($row['sku'] === '') {
                $fail('el Código es obligatorio.');
            } elseif (mb_strlen($row['sku']) > 100) {
                $fail('el Código supera los 100 caracteres.');
            } elseif (isset($seenSkus[Str::lower($row['sku'])])) {
                $fail('el Código "'.$row['sku'].'" está repetido (ya aparece en la fila '.$seenSkus[Str::lower($row['sku'])].').');
            } else {
                $seenSkus[Str::lower($row['sku'])] = $line;
            }

            if ($row['name'] === '') {
                $fail('el Item es obligatorio.');
            } elseif (mb_strlen($row['name']) > 255) {
                $fail('el Item supera los 255 caracteres.');
            }

            $salePrice = $this->number($row['sale_price']);
            if ($salePrice === null || Bc::lt($salePrice, 0)) {
                $fail('el Precio Venta U es obligatorio y debe ser un número mayor o igual a 0.');
            }

            $quantity = $row['quantity'] === '' ? '0' : $this->number($row['quantity']);
            if ($quantity === null || Bc::lt($quantity, 0)) {
                $fail('la Cantidad debe ser un número mayor o igual a 0.');
            }

            $purchasePrice = $row['purchase_price'] === '' ? null : $this->number($row['purchase_price']);
            if ($quantity !== null && Bc::gt($quantity, 0) && ($purchasePrice === null || Bc::lt($purchasePrice, 0))) {
                $fail('si trae Cantidad, el Precio Compra U es obligatorio y debe ser un número mayor o igual a 0.');
            }

            $unit = null;
            if ($row['unit_name'] === '') {
                $fail('la Unidad es obligatoria.');
            } else {
                $unit = $units->first(fn ($u) => Str::lower(Str::ascii($u->name)) === Str::lower(Str::ascii($row['unit_name'])));

                if (! $unit) {
                    $fail('la Unidad "'.$row['unit_name'].'" no existe (usa una del catálogo de unidades de medida).');
                }
            }

            if (mb_strlen($row['marca']) > 100) {
                $fail('la Marca supera los 100 caracteres.');
            }

            if (mb_strlen($row['color']) > 100) {
                $fail('el Color supera los 100 caracteres.');
            }

            if (mb_strlen($row['medida']) > 100) {
                $fail('la Medida supera los 100 caracteres.');
            }

            if (mb_strlen($row['category']) > 255) {
                $fail('la Categoria supera los 255 caracteres.');
            }

            if (count($this->errors) === $before) {
                $prepared[] = [...$row, 'sale_price' => $salePrice, 'quantity' => $quantity, 'purchase_price' => $purchasePrice, 'unit' => $unit];
            }
        }

        $this->checkBarcodeConflicts($prepared);

        if ($this->errors !== [] || $prepared === []) {
            return;
        }

        DB::transaction(fn () => $this->persist($prepared));
    }

    /**
     * Ubica, por el texto de la fila 1, en qué columna (letra) está cada dato esperado.
     *
     * @return array<string, string>
     */
    private function mapHeadings(Worksheet $sheet): array
    {
        $columns = [];
        $highestIndex = Coordinate::columnIndexFromString($sheet->getHighestColumn());

        for ($i = 1; $i <= $highestIndex; $i++) {
            $letter = Coordinate::stringFromColumnIndex($i);
            $text = $this->normalize((string) $sheet->getCell($letter.'1')->getValue());

            foreach (self::HEADINGS as $key => $aliases) {
                if (isset($columns[$key])) {
                    continue;
                }

                if (in_array($text, $aliases, true)) {
                    $columns[$key] = $letter;
                }
            }
        }

        return $columns;
    }

    /**
     * Imágenes pegadas en la columna "Foto", indexadas por la fila donde empiezan.
     *
     * @return array<int, array{bytes: string, extension: string}>
     */
    private function extractPhotosByRow(Worksheet $sheet, ?string $column): array
    {
        if (! $column) {
            return [];
        }

        $photos = [];

        foreach ($sheet->getDrawingCollection() as $drawing) {
            [$col, $row] = Coordinate::coordinateFromString($drawing->getCoordinates());

            if ($col !== $column) {
                continue;
            }

            $extracted = $this->readDrawing($drawing);

            if ($extracted) {
                $photos[$row] = $extracted;
            }
        }

        return $photos;
    }

    /**
     * @return array{bytes: string, extension: string}|null
     */
    private function readDrawing(mixed $drawing): ?array
    {
        if ($drawing instanceof Drawing) {
            $path = $drawing->getPath();
            $bytes = @file_get_contents($path);

            if (! $bytes) {
                return null;
            }

            $extension = strtolower($drawing->getExtension() ?: (pathinfo($path, PATHINFO_EXTENSION) ?: 'png'));

            return ['bytes' => $bytes, 'extension' => $extension];
        }

        if ($drawing instanceof MemoryDrawing) {
            ob_start();
            call_user_func($drawing->getRenderingFunction(), $drawing->getImageResource());
            $bytes = ob_get_clean();

            if (! $bytes) {
                return null;
            }

            $extension = match ($drawing->getMimeType()) {
                MemoryDrawing::MIMETYPE_PNG => 'png',
                MemoryDrawing::MIMETYPE_GIF => 'gif',
                default => 'jpg',
            };

            return ['bytes' => $bytes, 'extension' => $extension];
        }

        return null;
    }

    /**
     * @param  array<string, string>  $columns
     * @param  array<int, array{bytes: string, extension: string}>  $photosByRow
     * @return array<int, array<string, mixed>>
     */
    private function readRows(Worksheet $sheet, array $columns, array $photosByRow): array
    {
        $highestRow = $sheet->getHighestDataRow();
        $rows = [];

        for ($r = 2; $r <= $highestRow; $r++) {
            $get = function (string $key) use ($sheet, $columns, $r): string {
                if (! isset($columns[$key])) {
                    return '';
                }

                return $this->text($sheet->getCell($columns[$key].$r)->getCalculatedValue());
            };

            $sku = $get('sku');
            $name = $get('name');
            $quantity = $get('quantity');
            $salePrice = $get('sale_price');
            $hasImage = isset($photosByRow[$r]);

            if ($sku === '' && $name === '' && $quantity === '' && $salePrice === '' && ! $hasImage) {
                continue; // fila totalmente vacía
            }

            $rows[] = [
                'line' => $r,
                'sku' => $sku,
                'name' => $name,
                'quantity' => $quantity,
                'sale_price' => $salePrice,
                'purchase_price' => $get('purchase_price'),
                'unit_name' => $get('unit_name'),
                'color' => $get('color'),
                'marca' => $get('marca'),
                'medida' => $get('medida'),
                'category' => $get('category'),
                'description' => $get('description'),
                'imported' => $get('imported'),
                'photo' => $photosByRow[$r] ?? null,
            ];
        }

        return $rows;
    }

    /**
     * Un código de barras ya usado por la unidad de OTRO producto haría fallar el índice único
     * (aquí el código de barras de la unidad nueva es el propio SKU).
     *
     * @param  array<int, array<string, mixed>>  $prepared
     */
    private function checkBarcodeConflicts(array $prepared): void
    {
        $skus = collect($prepared)->pluck('sku')->unique()->values();

        if ($skus->isEmpty()) {
            return;
        }

        $owners = \App\Models\ProductUnit::whereIn('barcode', $skus)->with('product:id,sku')->get()->keyBy('barcode');

        foreach ($prepared as $row) {
            $unit = $owners[$row['sku']] ?? null;

            if ($unit && Str::lower($unit->product->sku) !== Str::lower($row['sku'])) {
                $this->errors[] = "Fila {$row['line']}: el código \"{$row['sku']}\" ya lo usa como código de barras el producto \"{$unit->product->sku}\".";
            }
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $prepared
     */
    private function persist(array $prepared): void
    {
        $defaultCategoryId = Category::firstOrCreate(
            ['name' => self::DEFAULT_CATEGORY],
            ['description' => 'Productos cargados por la Importación General sin categoría indicada en el Excel.']
        )->id;

        /** @var array<string, int> nombre de categoría (en minúsculas) => id, cacheado para no repetir consultas */
        $categoryIds = [];
        $resolveCategory = function (string $name) use (&$categoryIds): int {
            $key = Str::lower($name);

            return $categoryIds[$key] ??= Category::firstOrCreate(['name' => $name])->id;
        };

        $existing = Product::whereIn('sku', collect($prepared)->pluck('sku'))
            ->with('units')
            ->get()
            ->keyBy(fn ($product) => Str::lower($product->sku));

        $stockItems = [];

        foreach ($prepared as $row) {
            $product = $existing[Str::lower($row['sku'])] ?? null;
            $categoryId = $row['category'] !== '' ? $resolveCategory($row['category']) : null;

            if ($product) {
                $product->update([
                    'name' => $row['name'],
                    'category_id' => $categoryId ?? $product->category_id,
                    'color' => $row['color'] !== '' ? $row['color'] : $product->color,
                    'marca' => $row['marca'] !== '' ? $row['marca'] : $product->marca,
                    'medida' => $row['medida'] !== '' ? $row['medida'] : $product->medida,
                    'description' => $row['description'] !== '' ? $row['description'] : $product->description,
                ]);
                $this->updated++;
            } else {
                $product = Product::create([
                    'sku' => $row['sku'],
                    'name' => $row['name'],
                    'category_id' => $categoryId ?? $defaultCategoryId,
                    'color' => $row['color'] !== '' ? $row['color'] : null,
                    'marca' => $row['marca'] !== '' ? $row['marca'] : null,
                    'medida' => $row['medida'] !== '' ? $row['medida'] : null,
                    'description' => $row['description'] !== '' ? $row['description'] : null,
                    'is_active' => true,
                ]);
                $this->created++;
            }

            $unit = $this->syncBaseUnit($product, $row);

            if ($row['photo']) {
                $this->savePhoto($product, $row['photo']);
            }

            if (Bc::gt($row['quantity'], 0)) {
                $stockItems[] = [
                    'product_unit_id' => $unit->id,
                    'quantity' => $row['quantity'],
                    'unit_cost' => $row['purchase_price'] ?? 0,
                ];
            }
        }

        if ($stockItems !== []) {
            app(StockMovementService::class)->register(
                Type::where('code', 'ENT')->firstOrFail(),
                $this->warehouseId,
                now(),
                'Importación general de productos',
                $this->userId,
                $stockItems
            );
            $this->stockLoaded = count($stockItems);
        }
    }

    /**
     * La unidad base (factor 1) trae el precio de venta; si el producto ya tenía una, solo se
     * actualiza el precio. El código de barras de una unidad NUEVA es el SKU del producto.
     */
    private function syncBaseUnit(Product $product, array $row): \App\Models\ProductUnit
    {
        $product->loadMissing('units');
        $existingUnit = $product->units->firstWhere('is_base', true);

        if ($existingUnit) {
            $existingUnit->update(['price' => $row['sale_price']]);

            return $existingUnit;
        }

        return $product->units()->create([
            'unit_id' => $row['unit']->id,
            'name' => $row['unit']->name,
            'factor' => 1,
            'price' => $row['sale_price'],
            'is_base' => true,
            'barcode' => $row['sku'],
        ]);
    }

    /**
     * @param  array{bytes: string, extension: string}  $photo
     */
    private function savePhoto(Product $product, array $photo): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'gpi');

        try {
            file_put_contents($tmp, $photo['bytes']);
            [$bytes, $extension] = $this->images->optimizeFile($tmp, 'photo', 'photo', $photo['extension']);

            // Igual que al crear/editar un producto desde el CRUD: el nombre del archivo es el ID del producto.
            $path = 'products/'.$product->id.'.'.$extension;
            Storage::disk('uploads')->put($path, $bytes);

            if ($product->photo) {
                Storage::disk('uploads')->delete($product->photo);
            }

            $product->update(['photo' => $path]);
        } finally {
            @unlink($tmp);
        }
    }

    /**
     * Un catálogo grande con varias fotos incrustadas (cada una se decodifica/recomprime con GD) puede
     * tardar y pesar más que un request normal: se amplía el margen para que no se corte a la mitad.
     * Nunca se achica lo que ya venga configurado, solo se sube el piso si hace falta.
     */
    private function allowLargeFiles(): void
    {
        @set_time_limit(300);

        $limit = trim((string) ini_get('memory_limit'));

        if ($limit === '' || $limit === '-1') {
            return;
        }

        $bytes = (int) $limit;
        $bytes *= match (strtolower(substr($limit, -1))) {
            'g' => 1024 ** 3,
            'm' => 1024 ** 2,
            'k' => 1024,
            default => 1,
        };

        if ($bytes < 512 * 1024 ** 2) {
            @ini_set('memory_limit', '512M');
        }
    }

    private function isImported(string $value): bool
    {
        return trim($value) !== '';
    }

    private function normalize(string $value): string
    {
        return Str::lower(Str::ascii(trim($value)));
    }

    private function text(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        // Excel entrega los códigos numéricos largos como float (7.7E+12); se pasan a texto sin notación científica.
        if (is_float($value) && Bc::isInteger($value)) {
            return sprintf('%.0f', $value);
        }

        return trim((string) $value);
    }

    private function number(mixed $value): ?string
    {
        if (is_int($value) || is_float($value)) {
            return Bc::num($value);
        }

        $clean = str_replace([' ', "\u{00a0}"], '', (string) $value);

        if ($clean === '') {
            return null;
        }

        if (str_contains($clean, ',') && str_contains($clean, '.')) {
            $clean = str_replace(',', '', $clean); // 1,234.50
        } elseif (str_contains($clean, ',')) {
            $clean = str_replace(',', '.', $clean); // 12,50
        }

        return is_numeric($clean) ? Bc::num($clean) : null;
    }
}
