<?php

namespace App\Models;

use App\Support\Bc;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Facades\DB;

/**
 * Stock por producto y almacén. `stock` y `avg_cost` son DERIVADOS de stock_batches
 * (inventario por lotes, salida FIFO): solo se recalculan con refreshTotals().
 * Lo único que se edita a mano aquí es `min_stock`.
 */
class ProductWarehouse extends Pivot
{
    public $incrementing = false;

    /**
     * Los modelos Pivot resuelven la tabla por defecto en singular
     * ("product_warehouse"); la migración real la crea en plural.
     */
    protected $table = 'product_warehouses';

    protected $fillable = [
        'product_id',
        'warehouse_id',
        'stock',
        'min_stock',
        'avg_cost',
    ];

    protected $casts = [
        'stock' => 'integer',
        'min_stock' => 'integer',
        'avg_cost' => 'decimal:2',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function hasLowStock(): bool
    {
        return $this->stock <= $this->min_stock;
    }

    /**
     * Lotes con stock disponible de este producto en este almacén, en orden FIFO.
     */
    public function availableBatches()
    {
        return StockBatch::where('product_id', $this->product_id)
            ->where('warehouse_id', $this->warehouse_id)
            ->where('qty_remaining', '>', 0)
            ->orderBy('received_at')
            ->orderBy('id')
            ->get();
    }

    /**
     * Identificador combinado para usarlo como rowKey en el componente x-ui.data-table
     * (esta tabla no tiene "id" propio, la clave real es compuesta).
     */
    protected function getCompositeKeyAttribute(): string
    {
        return "{$this->product_id}-{$this->warehouse_id}";
    }

    /**
     * Entrada de stock: crea un lote nuevo con su propio costo y fecha (no se promedia
     * nada). $itemId enlaza el lote con el ítem del movimiento para poder revertirlo;
     * null se usa para stock inicial/apertura.
     */
    public static function receiveStock(
        int $productId,
        int $warehouseId,
        int|float|string $quantity,
        int|float|string $unitCost,
        ?int $itemId = null,
        $receivedAt = null
    ): StockBatch {
        return DB::transaction(function () use ($productId, $warehouseId, $quantity, $unitCost, $itemId, $receivedAt) {
            self::lockOrCreateRow($productId, $warehouseId);

            $timestamp = $receivedAt ? \Carbon\Carbon::parse($receivedAt) : now();
            if ($timestamp->format('H:i:s') === '00:00:00') {
                $timestamp->setTimeFrom(now());
            }

            $batch = StockBatch::create([
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
                'stock_movement_item_id' => $itemId,
                'received_at' => $timestamp,
                'unit_cost' => $unitCost,
                'qty_initial' => $quantity,
                'qty_remaining' => $quantity,
            ]);

            self::refreshTotals($productId, $warehouseId);

            return $batch;
        });
    }

    /**
     * Salida de stock por FIFO: consume primero el lote más antiguo, luego el siguiente.
     * Registra qué lotes (y a qué costo) se consumieron para poder revertir exacto.
     * Devuelve el costo total de lo consumido. Lanza excepción si no alcanza el stock.
     */
    public static function removeStock(int $productId, int $warehouseId, int|float|string $quantity, ?int $itemId = null): string
    {
        return DB::transaction(function () use ($productId, $warehouseId, $quantity, $itemId) {
            self::lockOrCreateRow($productId, $warehouseId);

            $batches = StockBatch::where('product_id', $productId)
                ->where('warehouse_id', $warehouseId)
                ->where('qty_remaining', '>', 0)
                ->orderBy('received_at')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $available = Bc::sum($batches->pluck('qty_remaining'));

            if (Bc::lt($available, $quantity)) {
                throw new \RuntimeException("Stock insuficiente para esta salida (disponible: ".Bc::trim($available).", solicitado: ".Bc::trim($quantity).").");
            }

            $pending = Bc::num($quantity);
            $totalCost = '0';

            foreach ($batches as $batch) {
                if (Bc::lte($pending, 0)) {
                    break;
                }

                $take = Bc::min($pending, $batch->qty_remaining);

                $batch->update(['qty_remaining' => Bc::round(Bc::sub($batch->qty_remaining, $take), 2)]);

                StockBatchConsumption::create([
                    'stock_batch_id' => $batch->id,
                    'stock_movement_item_id' => $itemId,
                    'quantity' => $take,
                    'unit_cost' => $batch->unit_cost,
                ]);

                $totalCost = Bc::add($totalCost, Bc::mul($take, $batch->unit_cost));
                $pending = Bc::round(Bc::sub($pending, $take), 2);
            }

            self::refreshTotals($productId, $warehouseId);

            return Bc::round($totalCost, 2);
        });
    }

    /**
     * Revierte una entrada: elimina el lote que creó, siempre que nadie haya
     * consumido nada de él todavía.
     */
    public static function reverseReceiveStock(int $itemId): void
    {
        DB::transaction(function () use ($itemId) {
            $batch = StockBatch::where('stock_movement_item_id', $itemId)->lockForUpdate()->first();

            if (! $batch) {
                throw new \RuntimeException('No se puede eliminar esta entrada: no tiene un lote asociado (es anterior al sistema de lotes).');
            }

            if (Bc::lt($batch->qty_remaining, $batch->qty_initial)) {
                throw new \RuntimeException('No se puede eliminar esta entrada: parte de ese lote ya se consumió en otras salidas.');
            }

            $productId = $batch->product_id;
            $warehouseId = $batch->warehouse_id;

            $batch->delete();

            self::refreshTotals($productId, $warehouseId);
        });
    }

    /**
     * Revierte una salida: devuelve a cada lote exactamente lo que se le había quitado.
     */
    public static function reverseRemoveStock(int $itemId): void
    {
        DB::transaction(function () use ($itemId) {
            $consumptions = StockBatchConsumption::where('stock_movement_item_id', $itemId)->get();
            $touched = [];

            foreach ($consumptions as $consumption) {
                $batch = StockBatch::lockForUpdate()->find($consumption->stock_batch_id);

                if ($batch) {
                    $batch->update(['qty_remaining' => Bc::round(Bc::add($batch->qty_remaining, $consumption->quantity), 2)]);
                    $touched[$batch->product_id.'-'.$batch->warehouse_id] = [$batch->product_id, $batch->warehouse_id];
                }

                $consumption->delete();
            }

            foreach ($touched as [$productId, $warehouseId]) {
                self::refreshTotals($productId, $warehouseId);
            }
        });
    }

    /**
     * Recalcula stock y costo promedio (informativo) a partir de los lotes.
     */
    public static function refreshTotals(int $productId, int $warehouseId): void
    {
        $totals = DB::table('stock_batches')
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->selectRaw('COALESCE(SUM(qty_remaining), 0) as stock, COALESCE(SUM(qty_remaining * unit_cost), 0) as total_value')
            ->first();

        $stock = Bc::num($totals->stock);

        DB::table('product_warehouses')
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->update([
                'stock' => (int) Bc::round($stock),
                'avg_cost' => Bc::gt($stock, 0) ? Bc::round(Bc::div($totals->total_value, $stock), 2) : 0,
                'updated_at' => now(),
            ]);

        // El stock cambió: la campana de notificaciones (stock mínimo) se recalcula en la próxima pantalla
        \Illuminate\Support\Facades\Cache::forget('low-stock-alerts');
    }

    private static function lockOrCreateRow(int $productId, int $warehouseId): void
    {
        $row = DB::table('product_warehouses')
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->lockForUpdate()
            ->first();

        if (! $row) {
            DB::table('product_warehouses')->insert([
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
                'stock' => 0,
                'min_stock' => 0,
                'avg_cost' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
