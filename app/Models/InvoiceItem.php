<?php

namespace App\Models;

use App\Support\Bc;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'description',
        'quantity',
        'unit_price',
        'subtotal',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    protected static function booted(): void
    {
        static::saving(function (InvoiceItem $item) {
            // Bc (BCMath) en vez de la multiplicación nativa: evita diferencias de centavos por precisión
            // de punto flotante frente a los demás cálculos de dinero del sistema, que ya usan Bc.
            $item->subtotal = Bc::round(Bc::mul($item->quantity, $item->unit_price), 2);
        });
    }
}
