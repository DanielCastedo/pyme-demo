<?php

namespace App\Imports;

use App\Support\Bc;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\ProductWarehouse;
use App\Models\Type;
use App\Services\StockMovementService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Carga de inventario desde Excel hacia UN almacén, con la regla "todo o nada": primero se valida cada fila y,
 * si alguna falla, no se guarda nada y se informa cada error con su fila. Solo cuando todo es válido se crean los
 * movimientos (uno de entrada y uno de salida), dentro de una única transacción y por el mismo camino que los
 * movimientos manuales: cada entrada crea un lote FIFO con su costo y cada salida consume lotes.
 *
 * Columnas: sku (o código de barras), tipo (entrada/salida), cantidad (entera), unidad (presentación; vacío = base)
 * y costo_unitario (obligatorio en entradas: costo de UNA unidad de la presentación indicada).
 */
class StockMovementsImport implements ToCollection, WithHeadingRow
{
    public const MAX_ROWS = 5000;

    /** @var array<int, string> */
    public array $errors = [];

    public int $entries = 0;

    public int $exits = 0;

    public function __construct(
        private readonly int $warehouseId,
        private readonly int $userId,
        private readonly ?string $note = null,
    ) {}

    public function collection(Collection $rows): void
    {
        $rows = $rows->filter(fn ($row) => $row->filter(fn ($v) => trim((string) $v) !== '')->isNotEmpty());

        if ($rows->count() > self::MAX_ROWS) {
            $this->errors[] = 'El archivo tiene '.$rows->count().' filas; el máximo por importación es '.self::MAX_ROWS.'. Divídelo en varios archivos.';

            return;
        }

        // ── Catálogo necesario, en pocas consultas ──
        $codes = $rows->map(fn ($r) => $this->text($r['sku'] ?? null))->filter()->unique()->values();
        $productsBySku = Product::with('units', 'components')->whereIn('sku', $codes)->get()->keyBy(fn ($p) => Str::lower($p->sku));
        $unitsByBarcode = ProductUnit::with('product.units', 'product.components')->whereIn('barcode', $codes)->get()->keyBy(fn ($u) => Str::lower((string) $u->barcode));

        $stock = ProductWarehouse::where('warehouse_id', $this->warehouseId)->pluck('stock', 'product_id')->map(fn ($s) => Bc::num($s))->all();

        $entries = [];
        $exits = [];
        $entryBase = [];

        // 1ª pasada: validar cada fila y separar entradas de salidas
        foreach ($rows as $index => $row) {
            $line = $index + 2; // la fila 1 es la cabecera
            $fail = function (string $message) use ($line) {
                $this->errors[] = "Fila {$line}: {$message}";
            };

            $code = $this->text($row['sku'] ?? null);
            $type = $this->type($this->text($row['tipo'] ?? null));
            $quantity = $this->number($row['cantidad'] ?? null);
            $unitName = $this->text($row['unidad'] ?? null);
            $rawCost = $this->text($row['costo_unitario'] ?? null);
            $cost = $rawCost === '' ? null : $this->number($rawCost);
            $before = count($this->errors);

            // Producto y presentación
            $unit = null;
            $product = null;

            if ($code === '') {
                $fail('el SKU es obligatorio.');
            } else {
                $product = $productsBySku[Str::lower($code)] ?? null;

                if (! $product && isset($unitsByBarcode[Str::lower($code)])) {
                    $unit = $unitsByBarcode[Str::lower($code)];
                    $product = $unit->product;
                }

                if (! $product) {
                    $fail("no existe ningún producto con el SKU o código de barras \"{$code}\".");
                }
            }

            if ($product) {
                if ($product->components->isNotEmpty()) {
                    $fail("\"{$product->name}\" es un kit: su stock se calcula de sus componentes y no se carga directamente.");
                } elseif ($product->units->isEmpty()) {
                    $fail("\"{$product->name}\" no tiene unidades de venta; créalas primero en Unidades de Producto.");
                } elseif ($unitName !== '') {
                    $unit = $product->units->first(fn ($u) => Str::lower($u->name) === Str::lower($unitName));

                    if (! $unit) {
                        $fail("\"{$product->name}\" no tiene la unidad \"{$unitName}\" (tiene: ".$product->units->pluck('name')->implode(', ').').');
                    }
                } elseif (! $unit) {
                    $unit = $product->units->firstWhere('is_base', true) ?? $product->units->first();
                }
            }

            if ($type === null) {
                $fail('el tipo debe ser "entrada" o "salida".');
            }

            if ($quantity === null || Bc::lt($quantity, 1) || ! Bc::isInteger($quantity)) {
                $fail('la cantidad debe ser un número entero mayor a 0 (sin decimales).');
            }

            if ($type === 'entrada' && ($cost === null || Bc::lt($cost, 0))) {
                $fail('en una entrada, el costo_unitario es obligatorio y debe ser un número mayor o igual a 0.');
            }

            if (count($this->errors) > $before || ! $product || ! $unit) {
                continue;
            }

            $item = ['product_unit_id' => $unit->id, 'quantity' => (int) $quantity, 'unit_cost' => null, 'line' => $line, 'product' => $product, 'base' => Bc::mul((int) $quantity, $unit->factor)];

            if ($type === 'entrada') {
                // El lote guarda el costo por unidad BASE: costo de la presentación ÷ lo que contiene.
                $item['unit_cost'] = Bc::round(Bc::div($cost, Bc::max($unit->factor, '0.0001')), 4);
                $entries[] = $item;
                $entryBase[$product->id] = Bc::add($entryBase[$product->id] ?? 0, $item['base']);
            } else {
                $exits[] = $item;
            }
        }

        // 2ª pasada: las salidas se validan contra el stock actual + las entradas del mismo archivo (que entran primero),
        // descontando lo que van sacando las filas anteriores del archivo.
        $running = $stock;
        foreach ($entryBase as $productId => $base) {
            $running[$productId] = Bc::add($running[$productId] ?? 0, $base);
        }

        foreach ($exits as $item) {
            $productId = $item['product']->id;
            $available = $running[$productId] ?? 0;

            if (Bc::gt($item['base'], Bc::add($available, '0.0001'))) {
                $this->errors[] = "Fila {$item['line']}: stock insuficiente de \"{$item['product']->name}\" (hay ".$this->fmt($available).' en unidad base y se piden '.$this->fmt($item['base']).').';

                continue;
            }

            $running[$productId] = Bc::sub($available, $item['base']);
        }

        if ($this->errors !== [] || ($entries === [] && $exits === [])) {
            return;
        }

        DB::transaction(function () use ($entries, $exits) {
            $service = app(StockMovementService::class);
            $note = $this->note ?: 'Carga de inventario desde Excel';
            $clean = fn (array $items) => array_map(fn ($i) => array_filter(
                ['product_unit_id' => $i['product_unit_id'], 'quantity' => $i['quantity'], 'unit_cost' => $i['unit_cost']],
                fn ($v) => $v !== null
            ), $items);

            if ($entries !== []) {
                $service->register(Type::where('code', 'ENT')->firstOrFail(), $this->warehouseId, now(), $note, $this->userId, $clean($entries));
                $this->entries = count($entries);
            }

            if ($exits !== []) {
                $service->register(Type::where('code', 'SAL')->firstOrFail(), $this->warehouseId, now(), $note, $this->userId, $clean($exits));
                $this->exits = count($exits);
            }
        });
    }

    private function type(string $value): ?string
    {
        return match (Str::lower(Str::ascii($value))) {
            'entrada', 'ent', 'ingreso', '+' => 'entrada',
            'salida', 'sal', 'egreso', '-' => 'salida',
            default => null,
        };
    }

    /** Número desde texto de Excel: acepta "12", "12.5" y "12,5". */
    private function number(mixed $value): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        $clean = str_replace(',', '.', trim((string) $value));

        return is_numeric($clean) ? Bc::num($clean) : null;
    }

    private function text(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        // Excel entrega los códigos numéricos como número (p. ej. 7791234567890.0): se recupera el texto sin ".0".
        if (is_float($value) && Bc::isInteger($value)) {
            return (string) (int) $value;
        }

        return trim((string) $value);
    }

    private function fmt(int|float|string $n): string
    {
        return rtrim(rtrim(number_format($n, 2, '.', ''), '0'), '.') ?: '0';
    }
}
