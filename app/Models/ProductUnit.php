<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductUnit extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'unit_id',
        'name',
        'factor',
        'price',
        'is_base',
        'barcode',
    ];

    protected $casts = [
        'factor' => 'integer',
        'price' => 'decimal:2',
        'is_base' => 'boolean',
    ];

    protected static function booted(): void
    {
        // El nombre de la presentación siempre es el de su unidad del catálogo. Se elige la unidad (unit_id) y el nombre
        // se copia; si alguien solo indica un nombre (importaciones, seeders), se busca o crea esa unidad en el catálogo.
        static::saving(function (ProductUnit $productUnit) {
            if ($productUnit->unit_id) {
                $unit = Unit::find($productUnit->unit_id);
                if ($unit) {
                    $productUnit->name = $unit->name;
                }
            } elseif (filled($productUnit->name)) {
                $productUnit->unit_id = Unit::findOrCreateByName($productUnit->name)->id;
            }
        });
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function quotationItems(): HasMany
    {
        return $this->hasMany(QuotationItem::class);
    }

    public function stockMovementItems(): HasMany
    {
        return $this->hasMany(StockMovementItem::class);
    }
}
