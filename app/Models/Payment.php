<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Payment extends Model
{
    use HasFactory;

    /**
     * Pago de una suscripción SaaS. Se declara explícito porque la tabla "payments"
     * (convención por defecto para este modelo) la ocupa ahora el catálogo de
     * métodos de cobro del inventario (ver PaymentMethod).
     */
    protected $table = 'payment_subscriptions';

    protected $fillable = [
        'subscription_id',
        'company_id',
        'amount',
        'currency',
        'method',
        'reference',
        'status',
        'paid_at',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'date',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }
}
