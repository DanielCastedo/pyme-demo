<?php

namespace App\Services;

use App\Models\StockBatch;
use App\Support\Bc;
use App\Models\ProductUnit;
use App\Models\ProductWarehouse;
use App\Models\StockMovement;
use App\Models\Type;
use Illuminate\Support\Facades\DB;

class StockMovementService
{
    /**
     * Registra un movimiento de stock (entrada o salida) con todos sus ítems y aplica el efecto
     * sobre los lotes: las entradas crean un lote, las salidas consumen FIFO.
     *
     * @param  array<int, array{product_unit_id: int, quantity: float|string, unit_cost?: float|string|null}>  $items
     *
     * @throws \RuntimeException si una salida no tiene stock suficiente.
     */
    public function register(
        Type $type,
        int $warehouseId,
        $date,
        ?string $note,
        int $userId,
        array $items,
        ?int $orderId = null,
        ?int $consignmentId = null
    ): StockMovement {
        return DB::transaction(function () use ($type, $warehouseId, $date, $note, $userId, $items, $orderId, $consignmentId) {
            $movementDate = $date ? \Carbon\Carbon::parse($date) : now();
            if ($movementDate->format('H:i:s') === '00:00:00') {
                $movementDate->setTimeFrom(now());
            }

            $movement = StockMovement::create([
                'type_id' => $type->id,
                'date' => $movementDate,
                'note' => $note,
                'warehouse_id' => $warehouseId,
                'user_id' => $userId,
                'order_id' => $orderId,
                'consignment_id' => $consignmentId,
            ]);

            foreach ($items as $itemData) {
                $productUnit = ProductUnit::with('product.components.component.units')->findOrFail($itemData['product_unit_id']);
                $quantity = Bc::num($itemData['quantity']);
                $baseQuantity = Bc::mul($quantity, $productUnit->factor);
                $unitCost = isset($itemData['unit_cost']) ? Bc::num($itemData['unit_cost']) : null;

                // Un kit no guarda stock propio: se vende descontando cada una de sus partes.
                if ($productUnit->product->components->isNotEmpty()) {
                    if ($type->sign !== '-') {
                        throw new \RuntimeException("«{$productUnit->product->name}» es un kit: registra el stock de sus componentes, no del kit.");
                    }

                    $this->removeKitParts($movement, $productUnit, $baseQuantity, $warehouseId);

                    continue;
                }

                $item = $movement->items()->create([
                    'product_id' => $productUnit->product_id,
                    'product_unit_id' => $productUnit->id,
                    'conversion_factor' => $productUnit->factor,
                    'quantity' => $quantity,
                    'base_quantity' => $baseQuantity,
                    'unit_cost' => $unitCost,
                ]);

                if ($type->sign === '+') {
                    ProductWarehouse::receiveStock(
                        $productUnit->product_id,
                        $warehouseId,
                        $baseQuantity,
                        $unitCost ?? 0,
                        $item->id,
                        $movement->date
                    );
                } elseif ($type->sign === '-') {
                    $totalCost = ProductWarehouse::removeStock(
                        $productUnit->product_id,
                        $warehouseId,
                        $baseQuantity,
                        $item->id
                    );

                    $item->update(['unit_cost' => Bc::gt($baseQuantity, 0) ? Bc::round(Bc::div($totalCost, $baseQuantity), 2) : 0]);
                }
            }

            return $movement;
        });
    }

    /**
     * Salida de un kit: descuenta de cada componente (cantidad del kit × lo que lleva) por FIFO y deja un ítem por parte,
     * marcado con el kit al que pertenece. Si falta alguna parte no se descuenta nada (la transacción se revierte).
     *
     * @throws \RuntimeException si algún componente no alcanza o no tiene unidad de venta.
     */
    private function removeKitParts(StockMovement $movement, ProductUnit $kitUnit, string $kitBase, int $warehouseId): void
    {
        $kit = $kitUnit->product;

        foreach ($kit->components as $component) {
            $part = $component->component;
            $partUnit = $part->units->firstWhere('is_base', true) ?? $part->units->first();

            if (! $partUnit) {
                throw new \RuntimeException("El componente «{$part->name}» del kit «{$kit->name}» no tiene unidad de venta.");
            }

            $needed = Bc::mul($kitBase, $component->quantity);
            $available = Bc::sum(StockBatch::where('product_id', $part->id)->where('warehouse_id', $warehouseId)->where('qty_remaining', '>', 0)->pluck('qty_remaining'));

            if (Bc::lt($available, $needed)) {
                throw new \RuntimeException(sprintf(
                    'No alcanza para %s de «%s»: falta «%s» (hay %s, se necesitan %s).',
                    Bc::trim($kitBase), $kit->name, $part->name, Bc::trim($available), Bc::trim($needed)
                ));
            }

            $item = $movement->items()->create([
                'product_id' => $part->id,
                'product_unit_id' => $partUnit->id,
                'conversion_factor' => $partUnit->factor,
                'quantity' => Bc::div($needed, $partUnit->factor, 2),
                'base_quantity' => $needed,
                'unit_cost' => null,
                'kit_product_id' => $kit->id,
                'kit_product_unit_id' => $kitUnit->id,
            ]);

            $totalCost = ProductWarehouse::removeStock($part->id, $warehouseId, $needed, $item->id);

            $item->update(['unit_cost' => Bc::gt($needed, 0) ? Bc::round(Bc::div($totalCost, $needed), 2) : 0]);
        }
    }

    /**
     * Elimina el movimiento revirtiendo sus lotes: una entrada borra su lote (solo si nadie lo
     * consumió) y una salida devuelve a cada lote lo que le quitó.
     *
     * @throws \RuntimeException si una entrada ya fue consumida parcialmente.
     */
    public function revert(StockMovement $movement): void
    {
        DB::transaction(function () use ($movement) {
            $movement->load('items', 'type');

            foreach ($movement->items as $item) {
                if ($movement->isEntry()) {
                    ProductWarehouse::reverseReceiveStock($item->id);
                } elseif ($movement->isExit()) {
                    ProductWarehouse::reverseRemoveStock($item->id);
                }
            }

            $movement->delete();
        });
    }
}
