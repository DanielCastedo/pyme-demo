<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductWarehouse;
use Database\Seeders\Concerns\UsesDemoData;
use Illuminate\Database\Seeder;

/**
 * Stock mínimo de ejemplo. Va después de las entradas y compras (el stock ya existe; aquí solo se define el mínimo).
 * El taladro queda con 2 (entran 3 y se vende 1) y su mínimo es 5: aparece en la campana de notificaciones.
 */
class ProductWarehouseSeeder extends Seeder
{
    use UsesDemoData;

    public function run(): void
    {
        foreach (['HMA-0001' => 5, 'HEL-0001' => 5, 'TOR-0001' => 200] as $sku => $minimo) {
            ProductWarehouse::where('product_id', Product::where('sku', $sku)->firstOrFail()->id)
                ->where('warehouse_id', $this->warehouse()->id)
                ->update(['min_stock' => $minimo]);
        }

        $this->command->info('✅ Stock mínimo de ejemplo definido (martillo y taladro: 5; tornillos: 200).');
    }
}
