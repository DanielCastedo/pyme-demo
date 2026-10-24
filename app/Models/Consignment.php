<?php

namespace App\Models;

use App\Support\Bc;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Consignación de salida: mercadería entregada a un cliente para que la venda. Se liquida en una o varias
 * veces: lo vendido pasa a ser una venta y lo devuelto vuelve al stock (ver ConsignmentService).
 */
class Consignment extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('consignaciones')
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(function (string $eventName) {
                $client = $this->customer?->name ?? 'Cliente';
                return match ($eventName) {
                    'created' => "Se creó la consignación #{$this->id} para el cliente {$client}",
                    'updated' => "Se actualizó la consignación #{$this->id} ({$client})",
                    'deleted' => "Se anuló la consignación #{$this->id} ({$client})",
                    default => "Acción {$eventName} en consignación #{$this->id}",
                };
            });
    }

    public const STATUS_OPEN = 'open';

    public const STATUS_SETTLED = 'settled';

    public const STATUS_CANCELED = 'canceled';

    public const STATUS_LABELS = [
        self::STATUS_OPEN => 'En consignación',
        self::STATUS_SETTLED => 'Liquidada',
        self::STATUS_CANCELED => 'Anulada',
    ];

    protected $fillable = [
        'customer_id',
        'warehouse_id',
        'branche_id',
        'user_id',
        'date',
        'due_date',
        'status',
        'note',
    ];

    protected $casts = [
        'date' => 'date',
        'due_date' => 'date',
    ];

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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    /**
     * Salidas de stock con las que se entregó la mercadería: una por cada almacén distinto que hayan
     * usado sus líneas.
     */
    public function dispatchMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'consignment_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ConsignmentItem::class);
    }

    public function settlements(): HasMany
    {
        return $this->hasMany(ConsignmentSettlement::class);
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function isSettled(): bool
    {
        return $this->status === self::STATUS_SETTLED;
    }

    public function isCanceled(): bool
    {
        return $this->status === self::STATUS_CANCELED;
    }

    protected function getIsOverdueAttribute(): bool
    {
        return $this->isOpen() && $this->due_date !== null && $this->due_date->isPast() && ! $this->due_date->isToday();
    }

    protected function getDateLabelAttribute(): string
    {
        return $this->created_at->format('d/m/Y H:i');
    }

    protected function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    protected function getDueLabelAttribute(): string
    {
        return $this->due_date ? $this->due_date->format('d/m/Y').($this->is_overdue ? ' (vencida)' : '') : '—';
    }

    /**
     * Valor entregado a precio acordado.
     */
    protected function getSentValueAttribute(): float
    {
        return (float) Bc::round(Bc::sum($this->items->map(fn ($i) => Bc::sub(Bc::mul($i->quantity_sent, $i->unit_price), $i->discount))), 2);
    }

    protected function getSoldValueAttribute(): float
    {
        return (float) Bc::round(Bc::sum($this->items->map(fn ($i) => Bc::sub(Bc::mul($i->quantity_sold, $i->unit_price), $i->discountForSold($i->quantity_sold)))), 2);
    }

    /**
     * Valor de lo que el cliente todavía tiene sin vender ni devolver.
     */
    protected function getPendingValueAttribute(): float
    {
        return (float) Bc::round(Bc::sum($this->items->map(fn ($i) => Bc::mul($i->pending, $i->effective_unit_price))), 2);
    }

    protected function getReturnedValueAttribute(): float
    {
        return (float) Bc::round(Bc::sum($this->items->map(fn ($i) => Bc::mul($i->quantity_returned, $i->effective_unit_price))), 2);
    }

    protected function getSentValueLabelAttribute(): string
    {
        return number_format($this->sent_value, 2);
    }

    protected function getSoldValueLabelAttribute(): string
    {
        return number_format($this->sold_value, 2);
    }

    protected function getPendingValueLabelAttribute(): string
    {
        return number_format($this->pending_value, 2);
    }
}
