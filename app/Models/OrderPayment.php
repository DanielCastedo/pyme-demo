<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un cobro de una venta (contado, anticipo o abono). El dinero vive en la transacción enlazada.
 */
class OrderPayment extends Model
{
    protected $fillable = [
        'order_id',
        'transaction_id',
        'user_id',
        'amount',
        'canceled',
        'receipt_photo',
    ];

    public function getReceiptPhotoUrlAttribute(): ?string
    {
        return $this->receipt_photo ? \Illuminate\Support\Facades\Storage::disk('uploads')->url($this->receipt_photo) : null;
    }

    protected $casts = [
        'amount' => 'decimal:2',
        'canceled' => 'boolean',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
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