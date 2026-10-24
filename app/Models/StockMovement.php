<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class StockMovement extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('movimientos_stock')
            ->setDescriptionForEvent(fn (string $eventName) => match ($eventName) {
                'created' => "Se registró movimiento de stock #{$this->id}",
                'updated' => "Se actualizó movimiento de stock #{$this->id}",
                'deleted' => "Se eliminó movimiento de stock #{$this->id}",
                default => "Acción {$eventName} en movimiento de stock #{$this->id}",
            });
    }

    protected $fillable = [
        'type_id',
        'date',
        'note',
        'warehouse_id',
        'user_id',
        'order_id',
        'consignment_id',
    ];

    protected $casts = [
        'date' => 'datetime',
    ];

    public function type(): BelongsTo
    {
        return $this->belongsTo(Type::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function consignment(): BelongsTo
    {
        return $this->belongsTo(Consignment::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockMovementItem::class);
    }

    public function isEntry(): bool
    {
        return $this->type->sign === '+';
    }

    public function isExit(): bool
    {
        return $this->type->sign === '-';
    }

    /**
     * "date" solo guarda el día (el formulario no pide hora, siempre queda en
     * 00:00), así que para mostrar cuándo se registró de verdad el movimiento
     * se usa created_at, que sí tiene la hora real.
     */
    protected function getDateLabelAttribute(): string
    {
        return $this->created_at->format('d/m/Y H:i');
    }
}
