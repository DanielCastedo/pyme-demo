<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Unit;
use Illuminate\Database\Seeder;

/**
 * Presentaciones (además de la unidad base que ya crea ProductSeeder).
 * Ejemplo: la cerveza se vende por lata a Bs 10 o en "Caja" de 24 a Bs 200 (factor 24 = trae 24 latas).
 */
class ProductUnitSeeder extends Seeder
{
    public function run(): void
    {
        $caja = Unit::where('name', 'Caja')->firstOrFail();

        Product::where('sku', 'CER-0001')->firstOrFail()->units()->updateOrCreate(
            ['name' => $caja->name],
            ['unit_id' => $caja->id, 'factor' => 24, 'price' => 200, 'is_base' => false, 'barcode' => 'CER-0001-CAJA']
        );

        $this->command->info('✅ Presentación de ejemplo creada (Cerveza: Caja x24).');
    }
}
