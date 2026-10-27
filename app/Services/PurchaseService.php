<?php

namespace App\Services;

use App\Support\Bc;
use App\Models\Cashbox;
use App\Models\PaymentMethod;
use App\Models\ProductUnit;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\Transaction;
use App\Models\Type;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;

/**
 * Compras: la mercadería entra al inventario como un lote FIFO con su costo. El pago es al contado (opcional,
 * sale de una caja al momento) o a crédito (el proveedor queda con un saldo, con un anticipo opcional y abonos
 * posteriores por App\Services\PurchasePaymentService). Anular la compra hace lo contrario (baja el stock y
 * devuelve todo el dinero pagado).
 *
 * Cada producto puede repartirse entre varios almacenes (ej. 8 unidades = 5 al almacén A y 3 al B): cada
 * almacén destino recibe su parte con un movimiento de entrada propio, todas al mismo costo.
 *
 * A una compra ya registrada se le pueden AGREGAR productos (addItems): lo que ya se compró no se modifica; lo nuevo
 * entra al inventario y, si se paga, sale de la caja con su propia transacción. addItems no cambia la forma de pago
 * de la compra (eso se decide una sola vez, al registrarla).
 */
class PurchaseService
{
    public function __construct(
        private StockMovementService $stock,
        private TransactionService $transactions,
        private PurchasePaymentService $payments,
    ) {}

    /**
     * @param  array{warehouse_id: int, branche_id?: ?int, date?: mixed, supplier_id?: ?int, invoice_number?: ?string, note?: ?string, pay_now?: bool, payment_id?: ?int, cashbox_id?: ?int, payment_type?: ?string, due_date?: ?string, initial_payment?: float|string|null}  $data
     * @param  array<int, array{product_unit_id: int, quantity: float|string, unit_cost: float|string, distribution?: array<int, array{warehouse_id: int, quantity: int|string}>}>  $items
     *         Sin "distribution", todo el producto entra a warehouse_id (el almacén principal). Con "distribution",
     *         el reparto debe sumar exactamente la cantidad comprada.
     *
     * @throws \RuntimeException
     */
    public function register(array $data, array $items, User $user): Purchase
    {
        return DB::transaction(function () use ($data, $items, $user) {
            $warehouse = Warehouse::findOrFail($data['warehouse_id']);
            // La compra es de la sucursal que compra (el almacén puede ser compartido con otras sucursales).
            $branchId = $warehouse->resolveBranchId($data['branche_id'] ?? null, ! empty($data['cashbox_id']) ? Cashbox::find($data['cashbox_id']) : null, $user);
            $supplier = ! empty($data['supplier_id']) ? Supplier::find($data['supplier_id']) : null;
            $date = ! empty($data['date']) ? \Carbon\Carbon::parse($data['date']) : now();
            if ($date->format('H:i:s') === '00:00:00') {
                $date->setTimeFrom(now());
            }

            $purchase = Purchase::create([
                'date' => $date,
                'supplier_id' => $supplier?->id,
                'invoice_number' => $data['invoice_number'] ?? null,
                'note' => $data['note'] ?? null,
                'amount' => 0,
                'status' => Purchase::STATUS_RECEIVED,
                'warehouse_id' => $warehouse->id,
                'branche_id' => $branchId,
                'user_id' => $user->id,
                'payment_type' => Purchase::PAYMENT_CASH,
                'paid_amount' => 0,
            ]);

            $memo = "Compra #{$purchase->id}".($purchase->supplier ? " a {$purchase->supplier->name}" : '');
            [$total, $mainMovementId] = $this->appendItems($purchase, $warehouse, $items, $date, $memo, $user);

            $purchase->amount = $total;
            $purchase->stock_movement_id = $mainMovementId;

            $credit = $this->resolveCreditTerms($data, $total, $supplier, $branchId, $user);

            if ($credit !== null) {
                $purchase->payment_type = Purchase::PAYMENT_CREDIT;
                $purchase->due_date = $credit['due_date'];
            } elseif (! empty($data['pay_now']) && Bc::gt($total, 0)) {
                // Pago inmediato desde una caja (compra al contado, opcional).
                $purchase->transaction_id = $this->pay($data, $total, $memo, $user)->id;
            }

            $purchase->save();

            if ($credit !== null && Bc::gt($credit['collect_now'], 0)) {
                $this->payments->pay($purchase, $credit['collect_now'], $credit['payment'], $credit['cashbox'], $user);
            }

            return $purchase->refresh();
        });
    }

    /**
     * Agrega productos a una compra ya registrada. Lo que se compró antes no se toca: los productos nuevos entran al
     * inventario (en el almacén elegido o repartidos), suman al total de la compra y, si se paga, salen de la caja con
     * una transacción aparte.
     *
     * @param  array{warehouse_id: int, pay_now?: bool, payment_id?: ?int, cashbox_id?: ?int}  $data
     * @param  array<int, array{product_unit_id: int, quantity: float|string, unit_cost: float|string, distribution?: array}>  $items
     *
     * @throws \RuntimeException
     */
    public function addItems(Purchase $purchase, array $data, array $items, User $user): Purchase
    {
        return DB::transaction(function () use ($purchase, $data, $items, $user) {
            $purchase = Purchase::lockForUpdate()->findOrFail($purchase->id);

            if ($purchase->isCanceled()) {
                throw new \RuntimeException('No se pueden agregar productos a una compra anulada.');
            }

            $warehouse = Warehouse::findOrFail($data['warehouse_id']);
            $memo = "Compra #{$purchase->id}".($purchase->supplier ? " a {$purchase->supplier->name}" : '').' (productos agregados)';

            [$total] = $this->appendItems($purchase, $warehouse, $items, now(), $memo, $user);

            $purchase->amount = Bc::round(Bc::add($purchase->amount, $total), 2);

            if (! empty($data['pay_now']) && Bc::gt($total, 0)) {
                $purchase->extraTransactions()->attach($this->pay($data, $total, $memo, $user)->id);
            }

            $purchase->save();

            return $purchase->refresh();
        });
    }

    /**
     * Anula la compra: el stock que entró vuelve a bajar (se borran sus lotes) y el dinero pagado vuelve a la caja.
     *
     * @throws \RuntimeException si parte de la mercadería ya se vendió o salió del almacén.
     */
    public function cancel(Purchase $purchase): Purchase
    {
        return DB::transaction(function () use ($purchase) {
            $purchase = Purchase::lockForUpdate()->findOrFail($purchase->id);

            if ($purchase->isCanceled()) {
                throw new \RuntimeException('Esta compra ya está anulada.');
            }

            // Todas las entradas de la compra (una por almacén y por ampliación); las compras antiguas tienen solo la principal.
            $movements = $purchase->receipts()->with('stockMovement')->get()->pluck('stockMovement')->filter();
            if ($movements->isEmpty() && $purchase->stockMovement) {
                $movements = collect([$purchase->stockMovement]);
            }

            foreach ($movements as $movement) {
                try {
                    $this->stock->revert($movement);
                } catch (\RuntimeException $e) {
                    throw new \RuntimeException('No se puede anular la compra: parte de esa mercadería ya se vendió o salió del almacén. Anula primero esas salidas.');
                }
            }

            // Todos los pagos: el original y los de productos agregados después
            $payments = $purchase->extraTransactions()->get();
            if ($purchase->transaction) {
                $payments->prepend($purchase->transaction);
            }

            foreach ($payments as $payment) {
                if (! $payment->canceled) {
                    $this->transactions->cancel($payment);
                }
            }

            // Compra a crédito: revierte también el anticipo y los abonos (paid_amount vuelve a 0).
            $this->payments->cancelAll($purchase);

            $purchase->update(['status' => Purchase::STATUS_CANCELED]);

            return $purchase->refresh();
        });
    }

    /**
     * Crea las líneas de la compra y las entradas de stock (una por almacén destino, cada línea un lote FIFO).
     * El costo del lote es por unidad BASE (lo que cuesta cada pieza), no por presentación.
     *
     * @return array{0: string, 1: ?int} total de lo agregado y el id del movimiento del almacén principal
     */
    private function appendItems(Purchase $purchase, Warehouse $principal, array $items, $date, string $memo, User $user): array
    {
        $units = ProductUnit::whereIn('id', array_column($items, 'product_unit_id'))->get()->keyBy('id');

        $lines = [];
        $total = '0';
        foreach ($items as $item) {
            $unit = $units[$item['product_unit_id']];
            $quantity = Bc::num($item['quantity']);
            $cost = Bc::round($item['unit_cost'], 2);
            $subtotal = Bc::round(Bc::mul($quantity, $cost), 2);
            $total = Bc::add($total, $subtotal);
            $distribution = $this->resolveDistribution($item, $quantity, $principal->id);
            $lines[] = compact('unit', 'quantity', 'cost', 'subtotal', 'distribution');
        }
        $total = Bc::round($total, 2);

        foreach ($lines as $l) {
            $purchaseItem = $purchase->items()->create([
                'product_id' => $l['unit']->product_id,
                'product_unit_id' => $l['unit']->id,
                'quantity' => $l['quantity'],
                'unit_cost' => $l['cost'],
                'conversion_factor' => $l['unit']->factor,
                'subtotal' => $l['subtotal'],
            ]);

            foreach ($l['distribution'] as $warehouseId => $qty) {
                $purchaseItem->distributions()->create(['warehouse_id' => $warehouseId, 'quantity' => $qty]);
            }
        }

        $entry = Type::where('code', 'ENT')->firstOrFail();
        $byWarehouse = [];
        foreach ($lines as $l) {
            foreach ($l['distribution'] as $warehouseId => $qty) {
                $byWarehouse[$warehouseId][] = [
                    'product_unit_id' => $l['unit']->id,
                    'quantity' => $qty,
                    'unit_cost' => Bc::round(Bc::div($l['cost'], Bc::max($l['unit']->factor, '0.0001')), 4),
                ];
            }
        }

        $names = Warehouse::whereIn('id', array_keys($byWarehouse))->pluck('name', 'id');
        $mainMovementId = null;

        foreach ($byWarehouse as $warehouseId => $movementItems) {
            $movement = $this->stock->register(
                $entry,
                $warehouseId,
                $date,
                $memo.(count($byWarehouse) > 1 ? " → {$names[$warehouseId]}" : ''),
                $user->id,
                $movementItems
            );

            $purchase->receipts()->create(['warehouse_id' => $warehouseId, 'stock_movement_id' => $movement->id]);

            // purchases.stock_movement_id apunta a la entrada del almacén principal (o a la primera, si ese no recibió nada).
            if ($mainMovementId === null || $warehouseId === $principal->id) {
                $mainMovementId = $movement->id;
            }
        }

        return [$total, $mainMovementId];
    }

    /**
     * Pago desde una caja: sale dinero con una transacción tipo Compra.
     *
     * @throws \RuntimeException si falta el método de pago o la caja, o la caja no tiene fondos.
     */
    private function pay(array $data, string $amount, string $memo, User $user): Transaction
    {
        if (empty($data['payment_id']) || empty($data['cashbox_id'])) {
            throw new \RuntimeException('Selecciona el método de pago y la caja de donde sale el dinero.');
        }

        return $this->transactions->register([
            'type_id' => Type::where('code', 'COM')->firstOrFail()->id,
            'payment_id' => $data['payment_id'],
            'cashbox_id' => $data['cashbox_id'],
            'amount' => $amount,
            'memo' => $memo,
            'date' => now(),
            'user_id' => $user->id,
        ]);
    }

    /**
     * Si se pide comprar a crédito, valida y devuelve sus términos; si es al contado (el valor por defecto),
     * devuelve null y el pago sigue el camino original: pay_now opcional vía transaction_id.
     *
     * @return array{due_date: string, collect_now: string, payment: ?PaymentMethod, cashbox: ?Cashbox}|null
     *
     * @throws \RuntimeException
     */
    private function resolveCreditTerms(array $data, string $total, ?Supplier $supplier, int $branchId, User $user): ?array
    {
        if (($data['payment_type'] ?? Purchase::PAYMENT_CASH) !== Purchase::PAYMENT_CREDIT) {
            return null;
        }

        if (! $user->can('compras.credito')) {
            throw new \RuntimeException('No tienes permiso para comprar a crédito.');
        }

        if (! $supplier) {
            throw new \RuntimeException('Una compra a crédito necesita un proveedor registrado.');
        }

        if (empty($data['due_date']) || now()->startOfDay()->gte(\Illuminate\Support\Carbon::parse($data['due_date'])->startOfDay())) {
            throw new \RuntimeException('La fecha de vencimiento del crédito debe ser posterior a hoy.');
        }

        $dueDate = \Illuminate\Support\Carbon::parse($data['due_date'])->toDateString();
        $collectNow = Bc::round($data['initial_payment'] ?? 0, 2);

        if (Bc::lt($collectNow, 0) || Bc::gte($collectNow, $total)) {
            throw new \RuntimeException('El anticipo debe ser mayor o igual a 0 y menor al total (si paga todo, es una compra al contado).');
        }

        $payment = ! empty($data['payment_id']) ? PaymentMethod::find($data['payment_id']) : null;
        $cashbox = ! empty($data['cashbox_id']) ? Cashbox::find($data['cashbox_id']) : null;

        if (Bc::gt($collectNow, 0)) {
            if (! $payment || ! $cashbox) {
                throw new \RuntimeException('Selecciona el método de pago y la caja donde se paga el anticipo.');
            }

            if ((int) $cashbox->branche_id !== $branchId) {
                throw new \RuntimeException('La caja elegida no es de la sucursal que hace la compra.');
            }
        }

        return ['due_date' => $dueDate, 'collect_now' => $collectNow, 'payment' => $payment, 'cashbox' => $cashbox];
    }

    /**
     * Reparto de una línea entre almacenes: [warehouse_id => cantidad]. Sin reparto, todo va al almacén principal.
     *
     * @return array<int, string>
     *
     * @throws \RuntimeException si el reparto no suma exactamente la cantidad comprada o repite un almacén.
     */
    private function resolveDistribution(array $item, string $quantity, int $principalWarehouseId): array
    {
        $parts = array_values($item['distribution'] ?? []);

        if ($parts === []) {
            return [$principalWarehouseId => $quantity];
        }

        $result = [];
        $assigned = '0';

        foreach ($parts as $part) {
            $warehouseId = (int) $part['warehouse_id'];
            $qty = Bc::num($part['quantity']);

            if (isset($result[$warehouseId])) {
                throw new \RuntimeException('Un almacén está repetido en el reparto de un producto.');
            }

            if (Bc::lte($qty, 0)) {
                throw new \RuntimeException('Cada parte del reparto debe ser mayor a 0.');
            }

            $result[$warehouseId] = $qty;
            $assigned = Bc::add($assigned, $qty);
        }

        if (! Bc::eq($assigned, $quantity)) {
            throw new \RuntimeException("El reparto suma {$assigned} pero se compró {$quantity}: reparte exactamente la cantidad comprada.");
        }

        return $result;
    }
}
