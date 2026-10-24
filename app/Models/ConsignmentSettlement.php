<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Una liquidación de la consignación: lo que el cliente informó como vendido (se factura como venta) y lo que
 * devolvió (vuelve al stock).
 */
class ConsignmentSettlement extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('liquidaciones_consignacion')
            ->setDescriptionForEvent(fn (string $eventName) => match ($eventName) {
                'created' => "Se registró la liquidación de consignación #{$this->id}",
                'updated' => "Se actualizó la liquidación de consignación #{$this->id}",
                'deleted' => "Se eliminó la liquidación de consignación #{$this->id}",
                default => "Acción {$eventName} en liquidación de consignación #{$this->id}",
            });
    }
    protected $fillable = [
        'consignment_id',
        'user_id',
        'order_id',
        'return_movement_id',
        'date',
        'note',
        'sold_amount',
    ];

    protected $casts = [
        'date' => 'date',
        'sold_amount' => 'decimal:2',
    ];

    public function consignment(): BelongsTo
    {
        return $this->belongsTo(Consignment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    /**
     * Venta generada por lo vendido (null si en esta liquidación solo hubo devoluciones).
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Entrada de stock por lo devuelto (null si no hubo devoluciones).
     */
    public function returnMovement(): BelongsTo
    {
        return $this->belongsTo(StockMovement::class, 'return_movement_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ConsignmentSettlementItem::class);
    }
}
