<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsignmentSettlementItem extends Model
{
    protected $fillable = [
        'consignment_settlement_id',
        'consignment_item_id',
        'quantity_sold',
        'quantity_returned',
    ];

    protected $casts = [
        'quantity_sold' => 'decimal:2',
        'quantity_returned' => 'decimal:2',
    ];

    public function settlement(): BelongsTo
    {
        return $this->belongsTo(ConsignmentSettlement::class, 'consignment_settlement_id');
    }

    public function consignmentItem(): BelongsTo
    {
        return $this->belongsTo(ConsignmentItem::class);
    }
}
