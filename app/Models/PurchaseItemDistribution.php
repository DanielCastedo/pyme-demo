<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Cuánto de un producto comprado fue a cada almacén.
 */
class PurchaseItemDistribution extends Model
{
    protected $fillable = ['purchase_item_id', 'warehouse_id', 'quantity'];

    protected $casts = ['quantity' => 'decimal:2'];

    public function item(): BelongsTo
    {
        return $this->belongsTo(PurchaseItem::class, 'purchase_item_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }
}
