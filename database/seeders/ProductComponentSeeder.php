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
            'HMA-0002' => 2,  // destornilladores
            'HMA-0003' => 1,  // alicate
            'TOR-0001' => 20, // tornillos
        ] as $sku => $quantity) {
            ProductComponent::updateOrCreate(
                ['product_id' => $kit->id, 'component_product_id' => Product::where('sku', $sku)->firstOrFail()->id],
                ['quantity' => $quantity]
            );
        }

        $this->command->info('✅ Kit de ejemplo creado (Juego de herramientas = 2 destornilladores + 1 alicate + 20 tornillos).');
    }
}
