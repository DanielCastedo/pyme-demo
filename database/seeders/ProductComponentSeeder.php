<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductComponent;
use Illuminate\Database\Seeder;

/**
 * Kit de ejemplo: el Combo Chuflay no guarda stock propio; al venderlo se descuentan sus partes.
 * "quantity" es cuántas unidades de esa parte lleva UN combo.
 */
class ProductComponentSeeder extends Seeder
{
    public function run(): void
    {
        $combo = Product::where('sku', 'CMB-0001')->firstOrFail();

        foreach ([
            'SIN-0001' => 1, // singani
            'GAS-0001' => 2, // gaseosas 2L
            'HIE-0001' => 1, // bolsa de hielo
        ] as $sku => $quantity) {
            ProductComponent::updateOrCreate(
                ['product_id' => $combo->id, 'component_product_id' => Product::where('sku', $sku)->firstOrFail()->id],
                ['quantity' => $quantity]
            );
        }

        $this->command->info('✅ Kit de ejemplo creado (Combo Chuflay = 1 singani + 2 gaseosas + 1 hielo).');
    }
}
