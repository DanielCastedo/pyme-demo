<?php

namespace App\Models;

use App\Support\Bc;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsignmentItem extends Model
{
    protected $fillable = [
        'consignment_id',
        'product_id',
        'product_unit_id',
        'warehouse_id',
        'quantity_sent',
        'quantity_sold',
        'quantity_returned',
        'unit_price',
        'discount',
        'conversion_factor',
        'unit_cost',
    ];

    protected $casts = [
        'quantity_sent' => 'decimal:2',
        'quantity_sold' => 'decimal:2',
        'quantity_returned' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'discount' => 'decimal:2',
        'conversion_factor' => 'decimal:4',
        'unit_cost' => 'decimal:2',
    ];

    public function consignment(): BelongsTo
    {
        return $this->belongsTo(Consignment::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productUnit(): BelongsTo
    {
        return $this->belongsTo(ProductUnit::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Precio real por unidad tras el descuento acordado: (precio × enviado − descuento) / enviado.
     */
    protected function getEffectiveUnitPriceAttribute(): float
    {
        $sent = Bc::num($this->quantity_sent);

        return (float) (Bc::gt($sent, 0)
            ? Bc::round(Bc::div(Bc::sub(Bc::mul($this->unit_price, $sent), $this->discount), $sent), 4)
            : Bc::num($this->unit_price));
    }

    /**
     * Descuento que le toca a una cantidad vendida acumulada, repartido en proporción a lo enviado. Se calcula
     * sobre el acumulado (no por liquidación) para que la suma de todas las liquidaciones sea exactamente el descuento.
     */
    public function discountForSold(int|float|string $soldTotal): float
    {
        $sent = Bc::num($this->quantity_sent);

        return (float) (Bc::gt($sent, 0) ? Bc::round(Bc::div(Bc::mul($this->discount, Bc::min($soldTotal, $sent)), $sent), 2) : 0);
    }

    /**
     * Lo que el cliente todavía tiene: enviado - vendido - devuelto.
     */
    protected function getPendingAttribute(): float
    {
        return (float) Bc::max(0, Bc::round(Bc::sub(Bc::sub($this->quantity_sent, $this->quantity_sold), $this->quantity_returned), 2));
    }
}
