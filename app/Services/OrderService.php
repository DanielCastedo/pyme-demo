<?php

namespace App\Services;

use App\Support\Bc;
use App\Models\Order;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;

/**
 * Pedidos: órdenes en espera con fecha de entrega. NO tocan stock ni caja; eso ocurre recién
 * cuando se confirman y pasan a ser venta (ver SaleService::confirm()).
 */
class OrderService
{
    public function __construct(private readonly OrderLinePricer $pricer) {}

    /**
     * @param  array{customer_id?: int|null, warehouse_id: int, delivery_date: string, note?: string|null}  $data
     * @param  array<int, array{product_unit_id: int, warehouse_id?: int|string|null, quantity: float|string, unit_price?: float|string|null, discount_type?: string|null, discount_value?: float|string|null}>  $items
     *
     * @throws \RuntimeException
     */
    public function create(array $data, array $items, User $user): Order
    {
        return DB::transaction(function () use ($data, $items, $user) {
            $warehouse = Warehouse::findOrFail($data['warehouse_id']);
            $lines = $this->priceLines($items, $user, $warehouse);

            $order = Order::create([
                'user_id' => $user->id,
                'customer_id' => $data['customer_id'] ?? null,
                'warehouse_id' => $warehouse->id,
                'branche_id' => $warehouse->branche_id,
                'date' => now()->toDateString(),
                'delivery_date' => $data['delivery_date'],
                'memo' => $data['note'] ?? null,
                'amount' => Bc::round(Bc::sum(array_column($lines, 'subtotal')), 2),
                'status' => Order::STATUS_PENDING,
            ]);

            $this->writeItems($order, $lines);

            return $order;
        });
    }

    /**
     * Reemplaza los datos y las líneas de un pedido en espera (no tiene efectos que revertir).
     *
     * @throws \RuntimeException si el pedido ya no está en espera.
     */
    public function update(Order $order, array $data, array $items, User $user): Order
    {
        return DB::transaction(function () use ($order, $data, $items, $user) {
            $order = Order::lockForUpdate()->findOrFail($order->id);

            if (! $order->isPedido() || ! $order->isPending()) {
                throw new \RuntimeException('Solo se puede editar un pedido que esté en espera.');
            }

            $warehouse = Warehouse::findOrFail($data['warehouse_id']);
            $lines = $this->priceLines($items, $user, $warehouse);

            $order->items()->delete();

            $order->update([
                'customer_id' => $data['customer_id'] ?? null,
                'warehouse_id' => $warehouse->id,
                'branche_id' => $warehouse->branche_id,
                'delivery_date' => $data['delivery_date'],
                'memo' => $data['note'] ?? null,
                'amount' => Bc::round(Bc::sum(array_column($lines, 'subtotal')), 2),
            ]);

            $this->writeItems($order, $lines);

            return $order;
        });
    }

    /**
     * @throws \RuntimeException si el pedido ya no está en espera.
     */
    public function delete(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $order = Order::lockForUpdate()->findOrFail($order->id);

            if (! $order->isPedido() || ! $order->isPending()) {
                throw new \RuntimeException('Solo se puede eliminar un pedido que esté en espera.');
            }

            $order->delete(); // las líneas se borran en cascada
        });
    }

    /**
     * @return array<int, array{unit: \App\Models\ProductUnit, quantity: float, price: float, discount: float, subtotal: float, warehouse_id: int}>
     */
    private function priceLines(array $items, User $user, Warehouse $defaultWarehouse): array
    {
        $lines = array_map(function ($item) use ($user, $defaultWarehouse) {
            $itemWarehouseId = (int) ($item['warehouse_id'] ?? $defaultWarehouse->id);

            return [...$this->pricer->price($item, $user, $itemWarehouseId), 'warehouse_id' => $itemWarehouseId];
        }, $items);

        if (Bc::lte(Bc::round(Bc::sum(array_column($lines, 'subtotal')), 2), 0)) {
            throw new \RuntimeException('El total del pedido debe ser mayor a 0.');
        }

        return $lines;
    }

    private function writeItems(Order $order, array $lines): void
    {
        foreach ($lines as $line) {
            $order->items()->create([
                'product_id' => $line['unit']->product_id,
                'product_unit_id' => $line['unit']->id,
                'warehouse_id' => $line['warehouse_id'],
                'quantity' => $line['quantity'],
                'unit_price' => $line['price'],
                'discount' => $line['discount'],
                'subtotal' => $line['subtotal'],
                'conversion_factor' => $line['unit']->factor,
            ]);
        }
    }
}
