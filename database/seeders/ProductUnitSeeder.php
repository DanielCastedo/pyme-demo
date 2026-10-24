<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Unit;
use Illuminate\Database\Seeder;

/**
 * Presentaciones (además de la unidad base que ya crea ProductSeeder).
 * Ejemplo: el tornillo se vende suelto a Bs 1 o en "Caja" de 100 a Bs 85 (factor 100 = trae 100 unidades base).
 */
class ProductUnitSeeder extends Seeder
{
    public function run(): void
    {
        $caja = Unit::where('name', 'Caja')->firstOrFail();

        Product::where('sku', 'FG-0001')->firstOrFail()->units()->updateOrCreate(
            ['name' => $caja->name],
            ['unit_id' => $caja->id, 'factor' => 100, 'price' => 85, 'is_base' => false, 'barcode' => 'FG-0001-CAJA']
        );

        $this->command->info('✅ Presentación de ejemplo creada (Tornillo: Caja x100).');
    }
}
