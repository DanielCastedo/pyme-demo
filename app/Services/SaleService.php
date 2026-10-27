<?php

namespace App\Services;

use App\Support\Bc;
use App\Models\Cashbox;
use App\Models\Customer;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Type;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;

/**
 * Una venta es UNA sola operación que deja todo consistente (o no deja nada):
 *  1. orden + líneas (precio del catálogo, o el editado si el usuario tiene permiso; con su descuento);
 *  2. salida de stock por FIFO, con el costo real de cada línea;
 *  3. cobro: al contado se cobra todo en caja; a crédito el cliente queda debiendo el saldo (con un
 *     anticipo opcional) y los abonos se registran después (ver OrderPaymentService).
 *
 * Un pedido en espera (ver OrderService) se convierte en venta con confirm(), que hace los pasos 2 y 3.
 */
class SaleService
{
    public function __construct(
        private readonly StockMovementService $stock,
        private readonly OrderPaymentService $payments,
        private readonly OrderLinePricer $pricer,
    ) {}

    /**
     * @param  array{customer_id?: int|null, warehouse_id: int, branche_id?: int|null, note?: string|null, payment_type?: string, due_date?: string|null, initial_payment?: float|string|null, payment_id?: int|null, cashbox_id?: int|null}  $data
     * @param  array<int, array{product_unit_id: int, warehouse_id?: int|string|null, quantity: float|string, unit_price?: float|string|null, discount_type?: string|null, discount_value?: float|string|null}>  $items
     *         Cada línea puede traer su propio warehouse_id (de qué almacén sale ESA línea); si no trae,
     *         se asume el warehouse_id de la venta.
     *
     * @throws \RuntimeException si no hay stock, la caja/almacén no corresponden, faltan permisos (precio, descuento,
     *                           crédito), se pasa el límite de crédito o el cobro no se puede aplicar.
     */
    public function register(array $data, array $items, User $user): Order
    {
        return DB::transaction(function () use ($data, $items, $user) {
            $warehouse = Warehouse::findOrFail($data['warehouse_id']);
            $customer = ! empty($data['customer_id']) ? Customer::findOrFail($data['customer_id']) : null;

            $lines = array_map(function ($item) use ($user, $warehouse) {
                $itemWarehouseId = (int) ($item['warehouse_id'] ?? $warehouse->id);

                return [...$this->pricer->price($item, $user, $itemWarehouseId), 'warehouse_id' => $itemWarehouseId];
            }, $items);
            $total = Bc::round(Bc::sum(array_column($lines, 'subtotal')), 2);

            if (Bc::lte($total, 0)) {
                throw new \RuntimeException('El total de la venta debe ser mayor a 0.');
            }

            // La venta es de la sucursal que vende (puede compartir el almacén con otras), no de la dueña del almacén.
            $branchId = $warehouse->resolveBranchId($data['branche_id'] ?? null, ! empty($data['cashbox_id']) ? Cashbox::find($data['cashbox_id']) : null, $user);
            $terms = $this->resolveTerms($data, $total, $customer, $branchId, $user);

            $order = Order::create([
                'user_id' => $user->id,
                'customer_id' => $customer?->id,
                'warehouse_id' => $warehouse->id,
                'branche_id' => $branchId,
                'date' => now()->toDateString(),
                'memo' => $data['note'] ?? null,
                'amount' => $total,
                'payment_type' => $terms['type'],
                'due_date' => $terms['due_date'],
                'paid_amount' => 0,
                'status' => Order::STATUS_COMPLETED,
                'receipt_photo' => $data['receipt_photo'] ?? null,
            ]);

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

            $this->settle($order, $terms, $user, $data['receipt_photo'] ?? null);

            // El cobro se aplica sobre otra copia bloqueada de la orden: se recarga para devolver paid_amount al día.
            return $order->refresh();
        });
    }

    /**
     * Confirma un pedido en espera: baja el stock (FIFO) y cobra (contado) o deja el saldo a crédito, con los
     * precios y descuentos ya acordados en el pedido. El pedido pasa a ser una venta.
     *
     * @param  array{payment_type?: string, due_date?: string|null, initial_payment?: float|string|null, payment_id?: int|null, cashbox_id?: int|null}  $data
     *
     * @throws \RuntimeException si el pedido no está en espera, no hay stock suficiente ahora o los términos no son válidos.
     */
    public function confirm(Order $order, array $data, User $user): Order
    {
        return DB::transaction(function () use ($order, $data, $user) {
            $order = Order::with('items', 'customer', 'warehouse')->lockForUpdate()->findOrFail($order->id);

            if (! $order->isPedido() || ! $order->isPending()) {
                throw new \RuntimeException('Solo se puede confirmar un pedido que esté en espera.');
            }

            if ($order->items->isEmpty() || Bc::lte($order->amount, 0)) {
                throw new \RuntimeException('El pedido no tiene productos o su total es 0.');
            }

            $terms = $this->resolveTerms($data, $order->amount, $order->customer, (int) $order->branche_id, $user);

            $order->update([
                'status' => Order::STATUS_COMPLETED,
                'date' => now()->toDateString(),
                'payment_type' => $terms['type'],
                'due_date' => $terms['due_date'],
                'paid_amount' => 0,
            ]);

            $this->settle($order, $terms, $user);

            // El cobro se aplica sobre otra copia bloqueada de la orden: se recarga para devolver paid_amount al día.
            return $order->refresh();
        });
    }

    /**
     * Venta generada por lo que un cliente vendió de una consignación. La mercadería ya salió del almacén cuando
     * se entregó, así que aquí NO se mueve stock: solo se crea la orden (a los precios acordados, con el costo
     * FIFO que tuvo al despachar) y se cobra o se deja a crédito.
     *
     * @param  array{note?: string|null, payment_type?: string, due_date?: string|null, initial_payment?: float|string|null, payment_id?: int|null, cashbox_id?: int|null}  $data
     * @param  array<int, array{unit: \App\Models\ProductUnit, quantity: float, price: float, discount?: float, unit_cost: float}>  $lines
     *
     * @throws \RuntimeException
     */
    public function registerFromConsignment(Customer $customer, Warehouse $warehouse, array $data, array $lines, User $user): Order
    {
        return DB::transaction(function () use ($customer, $warehouse, $data, $lines, $user) {
            $total = Bc::round(Bc::sum(array_map(fn ($l) => Bc::round(Bc::sub(Bc::mul($l['quantity'], $l['price']), $l['discount'] ?? 0), 2), $lines)), 2);

            if (Bc::lte($total, 0)) {
                throw new \RuntimeException('El total de lo vendido debe ser mayor a 0.');
            }

            $branchId = (int) ($data['branche_id'] ?? $warehouse->branche_id);
            $terms = $this->resolveTerms($data, $total, $customer, $branchId, $user);

            $order = Order::create([
                'user_id' => $user->id,
                'customer_id' => $customer->id,
                'warehouse_id' => $warehouse->id,
                'branche_id' => $branchId,
                'date' => now()->toDateString(),
                'memo' => $data['note'] ?? null,
                'amount' => $total,
                'payment_type' => $terms['type'],
                'due_date' => $terms['due_date'],
                'paid_amount' => 0,
                'status' => Order::STATUS_COMPLETED,
            ]);

            foreach ($lines as $line) {
                $order->items()->create([
                    'product_id' => $line['unit']->product_id,
                    'product_unit_id' => $line['unit']->id,
                    'warehouse_id' => $line['warehouse_id'] ?? $warehouse->id,
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['price'],
                    'discount' => $line['discount'] ?? 0,
                    'subtotal' => Bc::round(Bc::sub(Bc::mul($line['quantity'], $line['price']), $line['discount'] ?? 0), 2),
                    'conversion_factor' => $line['unit']->factor,
                    'unit_cost' => $line['unit_cost'],
                ]);
            }

            if (Bc::gt($terms['collect_now'], 0)) {
                $this->payments->collect($order, $terms['collect_now'], $terms['payment'], $terms['cashbox'], $user);
            }

            return $order->refresh();
        });
    }

    /**
     * Anula una venta: devuelve a cada lote lo que salió, revierte todos sus cobros en caja y la marca anulada.
     *
     * @throws \RuntimeException
     */
    public function cancel(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $order = Order::with('stockMovements')->lockForUpdate()->findOrFail($order->id);

            if ($order->isCanceled()) {
                throw new \RuntimeException('La venta ya está anulada.');
            }

            if ($order->consignmentSettlement) {
                throw new \RuntimeException('Esta venta salió de la consignación #'.$order->consignmentSettlement->consignment_id.' y no se puede anular por separado: la mercadería ya se dio por vendida.');
            }

            foreach ($order->stockMovements as $movement) {
                $this->stock->revert($movement);
            }

            $this->payments->cancelAll($order);

            $order->update(['status' => Order::STATUS_CANCELED]);
        });
    }

    /**
     * Valida y normaliza cómo se paga la venta. Devuelve el tipo, vencimiento, anticipo y los medios
     * (método de pago y caja) que se usarán para cobrar lo que se cobre ahora.
     *
     * @return array{type: string, due_date: ?string, collect_now: float, payment: ?PaymentMethod, cashbox: ?Cashbox}
     *
     * @throws \RuntimeException
     */
    private function resolveTerms(array $data, int|float|string $total, ?Customer $customer, int $branchId, User $user): array
    {
        $type = ($data['payment_type'] ?? Order::PAYMENT_CASH) === Order::PAYMENT_CREDIT ? Order::PAYMENT_CREDIT : Order::PAYMENT_CASH;
        $payment = ! empty($data['payment_id']) ? PaymentMethod::find($data['payment_id']) : null;
        $cashbox = ! empty($data['cashbox_id']) ? Cashbox::find($data['cashbox_id']) : null;
        $dueDate = null;

        if ($type === Order::PAYMENT_CASH) {
            $collectNow = $total;
        } else {
            if (! $user->can('ventas.credito')) {
                throw new \RuntimeException('No tienes permiso para vender a crédito.');
            }

            if (! $customer) {
                throw new \RuntimeException('Una venta a crédito necesita un cliente registrado.');
            }

            if (empty($data['due_date']) || now()->startOfDay()->gte(\Illuminate\Support\Carbon::parse($data['due_date'])->startOfDay())) {
                throw new \RuntimeException('La fecha de vencimiento del crédito debe ser posterior a hoy.');
            }

            $dueDate = \Illuminate\Support\Carbon::parse($data['due_date'])->toDateString();
            $collectNow = Bc::round($data['initial_payment'] ?? 0, 2);

            if (Bc::lt($collectNow, 0) || Bc::gte($collectNow, $total)) {
                throw new \RuntimeException('El anticipo debe ser mayor o igual a 0 y menor al total (si paga todo, vende al contado).');
            }

            if ($customer->credit_limit !== null) {
                $owed = $customer->receivableBalance();
                $newDebt = Bc::round(Bc::sub($total, $collectNow), 2);

                if (Bc::gt(Bc::add($owed, $newDebt), Bc::add($customer->credit_limit, '0.004'))) {
                    throw new \RuntimeException(sprintf(
                        'Se pasa del límite de crédito de %s: ya debe %s y esta venta sumaría %s (disponible: %s).',
                        $customer->name,
                        number_format($owed, 2),
                        number_format($newDebt, 2),
                        number_format((float) Bc::max(0, Bc::sub($customer->credit_limit, $owed)), 2)
                    ));
                }
            }
        }

        if (Bc::gt($collectNow, 0)) {
            if (! $payment || ! $cashbox) {
                throw new \RuntimeException('Selecciona el método de pago y la caja donde se cobra.');
            }

            if ((int) $cashbox->branche_id !== $branchId) {
                throw new \RuntimeException('La caja elegida no es de la sucursal que hace la venta.');
            }
        }

        return ['type' => $type, 'due_date' => $dueDate, 'collect_now' => $collectNow, 'payment' => $payment, 'cashbox' => $cashbox];
    }

    /**
     * Pasos comunes de toda venta ya con su orden guardada: salida de stock FIFO y el cobro de lo que se paga ahora.
     *
     * @param  array{collect_now: float, payment: ?PaymentMethod, cashbox: ?Cashbox}  $terms
     */
    private function settle(Order $order, array $terms, User $user, ?string $receiptPhoto = null): void
    {
        $type = Type::where('code', 'SAL')->firstOrFail();

        // Una línea puede salir de un almacén distinto al de la venta: se agrupa por almacén y sale
        // un movimiento de stock por cada uno (todos enlazados a la misma venta vía order_id).
        $itemsByWarehouse = $order->items()->get()->groupBy('warehouse_id');

        foreach ($itemsByWarehouse as $warehouseId => $warehouseItems) {
            $this->stock->register(
                $type,
                (int) $warehouseId,
                now(),
                "Venta #{$order->id}",
                $user->id,
                $warehouseItems->map(fn ($i) => ['product_unit_id' => $i->product_unit_id, 'quantity' => $i->quantity])->all(),
                $order->id
            );
        }

        if ($terms['collect_now'] > 0) {
            $this->payments->collect($order, $terms['collect_now'], $terms['payment'], $terms['cashbox'], $user, $receiptPhoto);
        }
    }
}
