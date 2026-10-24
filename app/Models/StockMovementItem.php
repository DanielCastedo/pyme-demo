<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class StockMovementItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_movement_id',
        'product_id',
        'product_unit_id',
        'conversion_factor',
        'quantity',
        'base_quantity',
        'unit_cost',
        'kit_product_id',
        'kit_product_unit_id',
    ];

    protected $casts = [
        'conversion_factor' => 'decimal:4',
        'quantity' => 'decimal:2',
        'base_quantity' => 'decimal:2',
        'unit_cost' => 'decimal:2',
    ];

    public function stockMovement(): BelongsTo
    {
        return $this->belongsTo(StockMovement::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productUnit(): BelongsTo
    {
        return $this->belongsTo(ProductUnit::class);
    }

    /**
     * Kit al que pertenece esta salida (solo cuando es una parte descontada por vender un kit).
     */
    public function kitProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'kit_product_id');
    }

    /**
     * Lote que creó este ítem (solo si el movimiento es una entrada).
     */
    public function batch(): HasOne
    {
        return $this->hasOne(StockBatch::class);
    }

    /**
     * Lotes de los que salió este ítem (solo si el movimiento es una salida, FIFO).
     */
    public function consumptions(): HasMany
    {
        return $this->hasMany(StockBatchConsumption::class);
    }
}
