<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockBatchConsumption extends Model
{
    protected $fillable = [
        'stock_batch_id',
        'stock_movement_item_id',
        'quantity',
        'unit_cost',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_cost' => 'decimal:2',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(StockBatch::class, 'stock_batch_id');
    }

    public function stockMovementItem(): BelongsTo
    {
        return $this->belongsTo(StockMovementItem::class);
    }
}
