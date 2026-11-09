<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockBatch extends Model
{
    protected $fillable = [
        'product_id',
        'warehouse_id',
        'stock_movement_item_id',
        'lot_number',
        'expires_at',
        'received_at',
        'unit_cost',
        'qty_initial',
        'qty_remaining',
    ];

    protected $casts = [
        'received_at' => 'datetime',
        'expires_at' => 'date',
        'unit_cost' => 'decimal:2',
        'qty_initial' => 'decimal:2',
        'qty_remaining' => 'decimal:2',
    ];

    /** Días antes del vencimiento en que un lote empieza a avisar "por vencer". */
    public const EXPIRY_WARNING_DAYS = 60;

    /**
     * Orden en que se gastan los lotes (FEFO): primero el que vence antes; los que no tienen vencimiento van al
     * final; entre iguales, el que entró primero (FIFO).
     */
    public function scopeConsumptionOrder($query)
    {
        return $query->orderByRaw('expires_at is null')->orderBy('expires_at')->orderBy('received_at')->orderBy('id');
    }

    /** Lotes con stock que ya vencieron o vencen dentro de los próximos EXPIRY_WARNING_DAYS días. */
    public function scopeExpiringSoon($query)
    {
        return $query->where('qty_remaining', '>', 0)
            ->whereNotNull('expires_at')
            ->whereDate('expires_at', '<=', now()->addDays(self::EXPIRY_WARNING_DAYS));
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->lt(today());
    }

    public function expiresSoon(): bool
    {
        return $this->expires_at !== null && ! $this->isExpired() && $this->expires_at->lte(today()->addDays(self::EXPIRY_WARNING_DAYS));
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function stockMovementItem(): BelongsTo
    {
        return $this->belongsTo(StockMovementItem::class);
    }

    public function consumptions(): HasMany
    {
        return $this->hasMany(StockBatchConsumption::class);
    }
}
