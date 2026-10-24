<?php

namespace App\Models;

use App\Support\Bc;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Compra de mercadería a un proveedor (ver App\Services\PurchaseService).
 */
class Purchase extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('compras')
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(function (string $eventName) {
                $prov = $this->supplier?->name ?? 'Proveedor';
                return match ($eventName) {
                    'created' => "Se registró la compra #{$this->id} al proveedor {$prov} por Bs. " . number_format((float) $this->total, 2),
                    'updated' => "Se actualizó la compra #{$this->id} ({$prov})",
                    'deleted' => "Se anuló o eliminó la compra #{$this->id} ({$prov})",
                    default => "Acción {$eventName} en compra #{$this->id}",
                };
            });
    }

    public const STATUS_RECEIVED = 'received';

    public const STATUS_CANCELED = 'canceled';

    public const PAYMENT_CASH = 'cash';

    public const PAYMENT_CREDIT = 'credit';

    public const STATUS_LABELS = [
        self::STATUS_RECEIVED => 'Recibida',
        self::STATUS_CANCELED => 'Anulada',
    ];

    protected $fillable = [
        'date', 'supplier_id', 'invoice_number', 'note', 'amount', 'status',
        'warehouse_id', 'branche_id', 'user_id', 'stock_movement_id', 'transaction_id',
        'payment_type', 'due_date', 'paid_amount',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
        'due_date' => 'date',
        'paid_amount' => 'decimal:2',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    /** Partes de la compra por almacén destino (una por almacén que recibió mercadería). */
    public function receipts(): HasMany
    {
        return $this->hasMany(PurchaseReceipt::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branche_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function stockMovement(): BelongsTo
    {
        return $this->belongsTo(StockMovement::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    /** Pagos de productos agregados después de registrar la compra (el pago original está en transaction). */
    public function extraTransactions(): BelongsToMany
    {
        return $this->belongsToMany(Transaction::class, 'purchase_transactions')->withTimestamps();
    }

    /** Pagos de una compra a crédito: el anticipo y cada abono posterior (ver App\Services\PurchasePaymentService). */
    public function payments(): HasMany
    {
        return $this->hasMany(PurchasePayment::class);
    }

    /** ¿La mercadería se repartió entre más de un almacén? */
    public function isDistributed(): bool
    {
        return $this->receipts->pluck('warehouse_id')->unique()->count() > 1;
    }

    /** Texto del almacén para listados: el nombre, o "Varios (N)" si se repartió. */
    protected function getWarehouseLabelAttribute(): string
    {
        return $this->isDistributed() ? 'Varios ('.$this->receipts->pluck('warehouse_id')->unique()->count().')' : ($this->warehouse?->name ?? '—');
    }

    public function isCanceled(): bool
    {
        return $this->status === self::STATUS_CANCELED;
    }

    protected function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    protected function getSupplierLabelAttribute(): string
    {
        return $this->supplier?->name ?? 'Sin proveedor';
    }

    protected function getDateLabelAttribute(): string
    {
        return $this->created_at->format('d/m/Y H:i');
    }

    protected function getAmountLabelAttribute(): string
    {
        return number_format((float) $this->amount, 2);
    }

    public function isCredit(): bool
    {
        return $this->payment_type === self::PAYMENT_CREDIT;
    }

    /**
     * Lo que todavía se le debe al proveedor de esta compra (0 si está anulada o ya está paga).
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
     * Estado del pago: Anulada, Pagada, o (si es crédito con saldo) Vencida / Parcial / Pendiente.
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
}
