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
        'received_at',
        'unit_cost',
        'qty_initial',
        'qty_remaining',
    ];

    protected $casts = [
        'received_at' => 'datetime',
        'unit_cost' => 'decimal:2',
        'qty_initial' => 'decimal:2',
        'qty_remaining' => 'decimal:2',
    ];

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
