<?php

namespace App\Models;

use App\Support\Bc;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'subscription_id',
        'payment_id',
        'invoice_number',
        'issue_date',
        'due_date',
        'subtotal',
        'tax',
        'total',
        'currency',
        'status',
        'notes',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    /**
     * Recalcula subtotal/total en base a los items relacionados.
     */
    public function recalculateTotals(int|float|string $taxRate = 0): void
    {
        $subtotal = $this->items()->sum('subtotal');
        $tax = Bc::round(Bc::mul($subtotal, $taxRate), 2);

        $this->update([
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => Bc::add($subtotal, $tax),
        ]);
    }
}
