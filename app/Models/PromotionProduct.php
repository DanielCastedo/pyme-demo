<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class PromotionProduct extends Pivot
{
    public $incrementing = false;

    /**
     * Los modelos Pivot resuelven la tabla por defecto en singular
     * ("promotion_product"); la migración real la crea en plural.
     */
    protected $table = 'promotion_products';

    protected $fillable = [
        'product_id',
        'promotion_id',
    ];
}
