<?php

namespace App\Models;

use App\Support\Bc;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Una orden es el documento de una venta. Se registra ya cobrada (ver SaleService).
 */
class Order extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('ventas')
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(function (string $eventName) {
                $client = $this->customer?->name ?? 'Cliente Mostrador';
                return match ($eventName) {
                    'created' => "Se registró la venta #{$this->number} al cliente {$client} por Bs. " . number_format((float) $this->amount, 2),
                    'updated' => "Se actualizó la venta #{$this->number} de {$client}",
                    'deleted' => "Se anuló o eliminó la venta #{$this->number} de {$client}",
                    default => "Acción {$eventName} en venta #{$this->number}",
                };
            });
    }

    public const STATUS_PENDING = 'pending';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELED = 'canceled';

    public const PAYMENT_CASH = 'cash';

    public const PAYMENT_CREDIT = 'credit';

    public const STATUS_LABELS = [
        self::STATUS_PENDING => 'Pendiente',
        self::STATUS_COMPLETED => 'Vendida',
        self::STATUS_CANCELED => 'Anulada',
    ];

    protected $fillable = [
        'user_id',
        'customer_id',
        'warehouse_id',
        'branche_id',
        'payment_type',
        'due_date',
        'paid_amount',
        'receipt_photo',
        'date',
        'memo',
        'amount',
        'delivery_date',
        'status',
    ];

    public function getReceiptPhotoUrlAttribute(): ?string
    {
        return $this->receipt_photo ? \Illuminate\Support\Facades\Storage::disk('uploads')->url($this->receipt_photo) : null;
    }

    protected function getDateLabelAttribute(): string
    {
        return $this->created_at->format('d/m/Y H:i');
    }

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
        'delivery_date' => 'date',
        'due_date' => 'date',
        'paid_amount' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    /**
     * Cliente de la venta (opcional: puede ser una venta a un cliente sin registrar).
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branche_id');
    }

    /**
     * Cobros de la venta: el pago al contado, el anticipo de un crédito o cada abono posterior.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(OrderPayment::class);
    }

    /**
     * Salidas de stock que generó esta venta: una por cada almacén distinto que hayan usado sus líneas.
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Liquidación de consignación que generó esta venta (null si es una venta normal).
     */
    public function consignmentSettlement(): HasOne
    {
        return $this->hasOne(ConsignmentSettlement::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Un pedido es una orden con fecha de entrega (se toma antes de vender). Las ventas directas
     * de mostrador no tienen fecha de entrega.
     */
    public function isPedido(): bool
    {
        return $this->delivery_date !== null;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isCanceled(): bool
    {
        return $this->status === self::STATUS_CANCELED;
    }

    protected function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    /**
     * Estado con las palabras del módulo de pedidos (En espera → Vendido).
     */
    protected function getPedidoStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'En espera',
            self::STATUS_COMPLETED => 'Vendido',
            self::STATUS_CANCELED => 'Anulado',
            default => $this->status,
        };
    }

    protected function getIsOverdueAttribute(): bool
    {
        return $this->isPending() && $this->delivery_date?->isPast() && ! $this->delivery_date->isToday();
    }

    protected function getDeliveryLabelAttribute(): string
    {
        if (! $this->delivery_date) {
            return '—';
        }

        return $this->delivery_date->format('d/m/Y').($this->is_overdue ? ' (atrasado)' : '');
    }

    public function isCredit(): bool
    {
        return $this->payment_type === self::PAYMENT_CREDIT;
    }

    /**
     * Lo que el cliente todavía debe de esta venta (0 si está anulada o ya se cobró).
     */
    protected function getBalanceAttribute(): float
    {
        if ($this->isCanceled()) {
            return 0.0;
        }

        return (float) Bc::max(0, Bc::round(Bc::sub($this->amount, $this->paid_amount), 2));
    }

    protected function getIsPaidAttribute(): bool
    {
        return Bc::lte($this->balance, 0);
    }

    protected function getIsPaymentOverdueAttribute(): bool
    {
        return $this->isCredit() && $this->balance > 0 && $this->due_date !== null
            && $this->due_date->isPast() && ! $this->due_date->isToday();
    }

    protected function getBalanceLabelAttribute(): string
    {
        return number_format($this->balance, 2);
    }

    protected function getPaidLabelAttribute(): string
    {
        return number_format((float) $this->paid_amount, 2);
    }

    protected function getDueLabelAttribute(): string
    {
        return $this->due_date ? $this->due_date->format('d/m/Y').($this->is_payment_overdue ? ' (vencido)' : '') : '—';
    }

    /**
     * Estado del cobro: Anulada, Pagada, o (si es crédito con saldo) Vencida / Parcial / Pendiente.
     */
    protected function getPaymentStatusLabelAttribute(): string
    {
        if ($this->isCanceled()) {
            return 'Anulada';
        }

        if ($this->is_paid) {
            return 'Pagada';
        }

        if ($this->is_payment_overdue) {
            return 'Vencida';
        }

        return Bc::gt($this->paid_amount, 0) ? 'Parcial' : 'Pendiente';
    }

    /**
     * Cómo se vendió: "Crédito" o el método de pago del contado.
     */
    protected function getPaymentLabelAttribute(): string
    {
        if ($this->isCredit()) {
            return 'Crédito';
        }

        return $this->payments->first()?->transaction?->paymentMethod?->name ?? 'Contado';
    }

    /**
     * Costo real de la mercadería vendida (FIFO): suma de lo consumido de los lotes. Null si no hay salida de stock cargada.
     */
    protected function getCostOfGoodsAttribute(): ?float
    {
        $movements = $this->stockMovements;

        if ($movements->isNotEmpty()) {
            $itemCosts = $movements->flatMap(fn ($m) => $m->items)->map(fn ($i) => Bc::mul($i->base_quantity, $i->unit_cost));

            return (float) Bc::round(Bc::sum($itemCosts), 2);
        }

        // Ventas de consignación: la mercadería salió antes (al despachar) y cada línea guarda su costo.
        if ($this->items->isNotEmpty() && $this->items->every(fn ($i) => $i->unit_cost !== null)) {
            return (float) Bc::round(Bc::sum($this->items->map(fn ($i) => Bc::mul(Bc::mul($i->quantity, $i->conversion_factor), $i->unit_cost))), 2);
        }

        return null;
    }

    protected function getProfitAttribute(): ?float
    {
        return $this->cost_of_goods === null ? null : (float) Bc::round(Bc::sub($this->amount, $this->cost_of_goods), 2);
    }

    protected function getDiscountTotalAttribute(): float
    {
        return (float) Bc::round(Bc::sum($this->items->pluck('discount')), 2);
    }

    protected function getAmountLabelAttribute(): string
    {
        return number_format((float) $this->amount, 2);
    }

    protected function getCustomerLabelAttribute(): string
    {
        return $this->customer?->name ?? 'Cliente general';
    }
}
