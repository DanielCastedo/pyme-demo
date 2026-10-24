<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductComponent;
use Illuminate\Database\Seeder;

/**
 * Kit de ejemplo: la Mesa de comedor no guarda stock propio; al venderla se descuentan sus partes.
 * "quantity" es cuántas unidades de esa parte lleva UN kit.
 */
class ProductComponentSeeder extends Seeder
{
    public function run(): void
    {
        $mesa = Product::where('sku', 'FG-0004')->firstOrFail();

        foreach ([
            'FG-0002' => 4, // patas
            'FG-0003' => 1, // tablero
            'FG-0001' => 8, // tornillos
        ] as $sku => $quantity) {
            ProductComponent::updateOrCreate(
                ['product_id' => $mesa->id, 'component_product_id' => Product::where('sku', $sku)->firstOrFail()->id],
                ['quantity' => $quantity]
            );
        }

        $this->command->info('✅ Kit de ejemplo creado (Mesa de comedor = 4 patas + 1 tablero + 8 tornillos).');
    }
}
