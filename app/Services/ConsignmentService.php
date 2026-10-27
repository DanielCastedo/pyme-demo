<?php

namespace App\Services;

use App\Support\Bc;
use App\Models\Consignment;
use App\Models\ConsignmentSettlement;
use App\Models\Customer;
use App\Models\Type;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;

/**
 * Consignación de salida. Ciclo de vida:
 *  1. create():  la mercadería SALE del almacén (FIFO) hacia el cliente, sin cobrar nada. Cada línea guarda su
 *                precio acordado y el costo que tuvo al salir.
 *  2. settle():  el cliente liquida (total o por partes): lo VENDIDO se convierte en una venta (a los precios
 *                acordados, al contado o a crédito) y lo DEVUELTO vuelve al stock con su costo original.
 *  3. cancel():  solo si todavía no se liquidó nada: la mercadería vuelve exactamente a los lotes de donde salió.
 */
class ConsignmentService
{
    public function __construct(
        private readonly StockMovementService $stock,
        private readonly SaleService $sales,
        private readonly OrderLinePricer $pricer,
    ) {}

    /**
     * @param  array{customer_id: int, warehouse_id: int, due_date?: string|null, note?: string|null}  $data
     * @param  array<int, array{product_unit_id: int, warehouse_id?: int|string|null, quantity: float|string, unit_price?: float|string|null}>  $items
     *         Cada línea puede traer su propio warehouse_id (de qué almacén sale ESA línea); si no trae,
     *         se asume el warehouse_id de la consignación.
     *
     * @throws \RuntimeException si no hay stock suficiente o el usuario no puede cambiar precios.
     */
    public function create(array $data, array $items, User $user): Consignment
    {
        return DB::transaction(function () use ($data, $items, $user) {
            $customer = Customer::findOrFail($data['customer_id']);
            $warehouse = Warehouse::findOrFail($data['warehouse_id']);

            // Precio y descuento acordados de cada línea: el del catálogo o el editado, y el descuento (ambos con su permiso).
            $lines = array_map(function ($item) use ($user, $warehouse) {
                $itemWarehouseId = (int) ($item['warehouse_id'] ?? $warehouse->id);

                return [...$this->pricer->price($item, $user, $itemWarehouseId), 'warehouse_id' => $itemWarehouseId];
            }, $items);

            if ($lines === []) {
                throw new \RuntimeException('Agrega al menos un producto a la consignación.');
            }

            // Un kit no se puede consignar: su devolución tendría que reponer cada parte con su costo. Se consignan las partes.
            foreach ($lines as $line) {
                if ($line['unit']->product->isKit()) {
                    throw new \RuntimeException("«{$line['unit']->product->name}» es un kit y no se puede consignar: consigna sus componentes por separado.");
                }
            }

            $consignment = Consignment::create([
                'customer_id' => $customer->id,
                'warehouse_id' => $warehouse->id,
                'branche_id' => $warehouse->resolveBranchId($data['branche_id'] ?? null, null, $user),
                'user_id' => $user->id,
                'date' => now()->toDateString(),
                'due_date' => $data['due_date'] ?? null,
                'note' => $data['note'] ?? null,
                'status' => Consignment::STATUS_OPEN,
            ]);

            // La mercadería sale del almacén por FIFO; una línea puede salir de un almacén distinto al de
            // la consignación, así que se agrupa por almacén y sale un movimiento por cada uno.
            // Nota: Collection::groupBy() reindexa cada grupo desde 0 (no conserva el índice original de
            // $lines), así que el agrupado se arma a mano para poder emparejar cada línea con su costo real.
            $type = Type::where('code', 'SAL')->firstOrFail();
            $costByLineIndex = []; // índice original en $lines => costo real que tuvo esa línea al salir
            $indexesByWarehouse = [];

            foreach ($lines as $index => $line) {
                $indexesByWarehouse[$line['warehouse_id']][] = $index;
            }

            foreach ($indexesByWarehouse as $warehouseId => $indexes) {
                $movement = $this->stock->register(
                    $type,
                    (int) $warehouseId,
                    now(),
                    "Consignación #{$consignment->id} a {$customer->name}",
                    $user->id,
                    array_map(fn ($i) => ['product_unit_id' => $lines[$i]['unit']->id, 'quantity' => $lines[$i]['quantity']], $indexes),
                    null,
                    $consignment->id
                );

                // Los ítems del movimiento se crean en el mismo orden en que se enviaron: se emparejan por
                // posición, ordenando por id para no depender del orden en que la BD devuelva la relación.
                $movementItems = $movement->items->sortBy('id')->values();

                foreach ($indexes as $position => $originalIndex) {
                    $costByLineIndex[$originalIndex] = $movementItems[$position]->unit_cost;
                }
            }

            foreach ($lines as $index => $line) {
                $consignment->items()->create([
                    'product_id' => $line['unit']->product_id,
                    'product_unit_id' => $line['unit']->id,
                    'warehouse_id' => $line['warehouse_id'],
                    'quantity_sent' => $line['quantity'],
                    'unit_price' => $line['price'],
                    'discount' => $line['discount'],
                    'conversion_factor' => $line['unit']->factor,
                    'unit_cost' => Bc::num($costByLineIndex[$index] ?? 0),
                ]);
            }

            return $consignment->refresh();
        });
    }

    /**
     * Registra una liquidación: cuánto vendió y cuánto devuelve el cliente de cada línea.
     *
     * @param  array<int, array{consignment_item_id: int, sold?: float|string|null, returned?: float|string|null}>  $lines
     * @param  array{note?: string|null, payment_type?: string, due_date?: string|null, initial_payment?: float|string|null, payment_id?: int|null, cashbox_id?: int|null}  $terms  cómo se cobra lo vendido
     *
     * @throws \RuntimeException si la consignación no está abierta, se excede lo pendiente o la venta no se puede registrar.
     */
    public function settle(Consignment $consignment, array $lines, array $terms, User $user): ConsignmentSettlement
    {
        return DB::transaction(function () use ($consignment, $lines, $terms, $user) {
            $consignment = Consignment::with('customer', 'warehouse')->lockForUpdate()->findOrFail($consignment->id);

            if (! $consignment->isOpen()) {
                throw new \RuntimeException('Solo se puede liquidar una consignación que esté en consignación (no liquidada ni anulada).');
            }

            $items = $consignment->items()->with('productUnit')->lockForUpdate()->get()->keyBy('id');
            $sold = [];
            $returned = [];
            $settlementLines = [];

            foreach ($lines as $line) {
                $item = $items[(int) $line['consignment_item_id']] ?? null;

                if (! $item) {
                    throw new \RuntimeException('Una de las líneas no pertenece a esta consignación.');
                }

                $qtySold = Bc::round($line['sold'] ?? 0, 2);
                $qtyReturned = Bc::round($line['returned'] ?? 0, 2);

                if (Bc::lt($qtySold, 0) || Bc::lt($qtyReturned, 0)) {
                    throw new \RuntimeException('Las cantidades no pueden ser negativas.');
                }

                if (Bc::lte(Bc::add($qtySold, $qtyReturned), 0)) {
                    continue;
                }

                if (Bc::gt(Bc::add($qtySold, $qtyReturned), Bc::add($item->pending, '0.0001'))) {
                    throw new \RuntimeException(sprintf(
                        'De "%s" solo quedan %s por liquidar (pediste %s vendidas + %s devueltas).',
                        $item->productUnit->product->name ?? $item->productUnit->name,
                        rtrim(rtrim(number_format($item->pending, 2, '.', ''), '0'), '.'),
                        $qtySold,
                        $qtyReturned
                    ));
                }

                $settlementLines[] = ['item' => $item, 'sold' => $qtySold, 'returned' => $qtyReturned];

                if (Bc::gt($qtySold, 0)) {
                    // Descuento de esta liquidación = descuento acumulado tras lo vendido ahora − el acumulado antes.
                    $discount = Bc::round(Bc::sub($item->discountForSold(Bc::add($item->quantity_sold, $qtySold)), $item->discountForSold($item->quantity_sold)), 2);
                    $sold[] = ['unit' => $item->productUnit, 'quantity' => $qtySold, 'price' => Bc::num($item->unit_price), 'discount' => $discount, 'unit_cost' => Bc::num($item->unit_cost), 'warehouse_id' => $item->warehouse_id];
                }

                if (Bc::gt($qtyReturned, 0)) {
                    $returned[] = ['product_unit_id' => $item->product_unit_id, 'quantity' => $qtyReturned, 'unit_cost' => Bc::num($item->unit_cost), 'warehouse_id' => $item->warehouse_id];
                }
            }

            if ($settlementLines === []) {
                throw new \RuntimeException('Indica cuánto vendió o cuánto devuelve el cliente.');
            }

            // Lo vendido → una venta al cliente (a los precios acordados). No mueve stock: ya salió al despachar.
            $order = $sold === [] ? null : $this->sales->registerFromConsignment(
                $consignment->customer,
                $consignment->warehouse,
                ['note' => "Consignación #{$consignment->id}", 'branche_id' => $consignment->branche_id] + $terms,
                $sold,
                $user
            );

            // Lo devuelto → vuelve al stock, con el costo con el que salió y al mismo almacén de donde salió
            // cada línea (una devolución puede tocar más de un almacén si la consignación salió de varios).
            $firstReturnMovement = null;

            if ($returned !== []) {
                foreach (collect($returned)->groupBy('warehouse_id') as $warehouseId => $groupReturns) {
                    $movement = $this->stock->register(
                        Type::where('code', 'ENT')->firstOrFail(),
                        (int) $warehouseId,
                        now(),
                        "Devolución de la consignación #{$consignment->id}",
                        $user->id,
                        $groupReturns->values()->all(),
                        null,
                        $consignment->id
                    );

                    $firstReturnMovement ??= $movement;
                }
            }

            $settlement = $consignment->settlements()->create([
                'user_id' => $user->id,
                'order_id' => $order?->id,
                'return_movement_id' => $firstReturnMovement?->id,
                'date' => now()->toDateString(),
                'note' => $terms['note'] ?? null,
                'sold_amount' => $order?->amount ?? 0,
            ]);

            foreach ($settlementLines as $l) {
                $settlement->items()->create([
                    'consignment_item_id' => $l['item']->id,
                    'quantity_sold' => $l['sold'],
                    'quantity_returned' => $l['returned'],
                ]);

                $l['item']->update([
                    'quantity_sold' => Bc::round(Bc::add($l['item']->quantity_sold, $l['sold']), 2),
                    'quantity_returned' => Bc::round(Bc::add($l['item']->quantity_returned, $l['returned']), 2),
                ]);
            }

            if ($consignment->items()->get()->every(fn ($i) => Bc::lte($i->pending, 0))) {
                $consignment->update(['status' => Consignment::STATUS_SETTLED]);
            }

            return $settlement;
        });
    }

    /**
     * Anula una consignación que todavía no tuvo ninguna liquidación: la mercadería vuelve a los mismos lotes
     * de donde salió.
     *
     * @throws \RuntimeException
     */
    public function cancel(Consignment $consignment): void
    {
        DB::transaction(function () use ($consignment) {
            $consignment = Consignment::with('dispatchMovements')->lockForUpdate()->findOrFail($consignment->id);

            if (! $consignment->isOpen()) {
                throw new \RuntimeException('Solo se puede anular una consignación abierta.');
            }

            if ($consignment->settlements()->exists()) {
                throw new \RuntimeException('No se puede anular: ya tiene liquidaciones (ventas o devoluciones registradas).');
            }

            foreach ($consignment->dispatchMovements as $movement) {
                $this->stock->revert($movement);
            }

            $consignment->update(['status' => Consignment::STATUS_CANCELED]);
        });
    }
}
