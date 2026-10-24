<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductComponent;
use Illuminate\Database\Seeder;

/**
 * Kit de ejemplo: el Botiquín no guarda stock propio; al venderlo se descuentan sus partes.
 * "quantity" es cuántas unidades de esa parte lleva UN kit.
 */
class ProductComponentSeeder extends Seeder
{
    public function run(): void
    {
        $botiquin = Product::where('sku', 'CUR-0003')->firstOrFail();

        foreach ([
            'CUR-0001' => 4, // gasas
            'CUR-0002' => 1, // alcohol
            'MED-0001' => 8, // tabletas de paracetamol
        ] as $sku => $quantity) {
            ProductComponent::updateOrCreate(
                ['product_id' => $botiquin->id, 'component_product_id' => Product::where('sku', $sku)->firstOrFail()->id],
                ['quantity' => $quantity]
            );
        }

        $this->command->info('✅ Kit de ejemplo creado (Botiquín = 4 gasas + 1 alcohol + 8 tabletas de paracetamol).');
    }
}
