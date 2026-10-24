<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un pago de una compra a crédito (el anticipo o un abono posterior). El dinero vive en la transacción enlazada.
 */
class PurchasePayment extends Model
{
    protected $fillable = [
        'purchase_id',
        'transaction_id',
        'user_id',
        'amount',
        'canceled',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'canceled' => 'boolean',
    ];

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}
