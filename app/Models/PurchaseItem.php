<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseItem extends Model
{
    protected $fillable = ['purchase_id', 'product_id', 'product_unit_id', 'quantity', 'unit_cost', 'conversion_factor', 'subtotal'];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'conversion_factor' => 'decimal:4',
        'subtotal' => 'decimal:2',
    ];

    /** Reparto de este producto entre almacenes (vacío en compras que entraron a un solo almacén). */
    public function distributions(): HasMany
    {
        return $this->hasMany(PurchaseItemDistribution::class);
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productUnit(): BelongsTo
    {
        return $this->belongsTo(ProductUnit::class);
    }
}
