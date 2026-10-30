<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductComponent;
use Illuminate\Database\Seeder;

/**
 * Kit de ejemplo: el Juego de herramientas no guarda stock propio; al venderlo se descuentan sus partes.
 * "quantity" es cuántas unidades de esa parte lleva UN kit.
 */
class ProductComponentSeeder extends Seeder
{
    public function run(): void
    {
        $kit = Product::where('sku', 'KIT-0001')->firstOrFail();

        foreach ([
            'EST-0001' => 1, // estuche
            'LON-0001' => 1, // lonchera
            'LLA-0001' => 2, // llaveros
        ] as $sku => $quantity) {
            ProductComponent::updateOrCreate(
                ['product_id' => $kit->id, 'component_product_id' => Product::where('sku', $sku)->firstOrFail()->id],
                ['quantity' => $quantity]
            );
        }

        $this->command->info('✅ Kit de ejemplo creado (Combo regreso a clases = 1 estuche + 1 lonchera + 2 llaveros).');
    }
}
