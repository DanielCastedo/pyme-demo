<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'number',
        'date',
        'amount',
        'canceled',
        'memo',
        'photo',
        'type_id',
        'payment_id',
        'cashbox_id',
        'branche_id',
        'user_id',
        'related_transaction_id',
    ];

    protected $casts = [
        'date' => 'datetime',
        'amount' => 'decimal:2',
        'canceled' => 'boolean',
    ];

    public function type(): BelongsTo
    {
        return $this->belongsTo(Type::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_id');
    }

    public function cashbox(): BelongsTo
    {
        return $this->belongsTo(Cashbox::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branche_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function relatedTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'related_transaction_id');
    }

    public function relatedTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'related_transaction_id');
    }

    /**
     * Cobro de venta al que corresponde esta transacción (si es un cobro de venta).
     */
    public function orderPayment(): HasOne
    {
        return $this->hasOne(OrderPayment::class);
    }

    protected function getSignedAmountAttribute(): string
    {
        return ($this->type->sign === '-' ? '- ' : '+ ').number_format((float) $this->amount, 2);
    }

    protected function getStatusLabelAttribute(): string
    {
        return $this->canceled ? 'Anulada' : 'Vigente';
    }

    public function isCanceled(): bool
    {
        return (bool) $this->canceled;
    }
}
