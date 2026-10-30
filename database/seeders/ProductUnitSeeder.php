<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Unit;
use Illuminate\Database\Seeder;

/**
 * Presentaciones (además de la unidad base que ya crea ProductSeeder).
 * Ejemplo: el tornillo se vende suelto a Bs 0.30 o en "Caja" de 100 a Bs 25 (factor 100 = trae 100 tornillos).
 */
class ProductUnitSeeder extends Seeder
{
    public function run(): void
    {
        $paquete = Unit::where('name', 'Paquete')->firstOrFail();

        Product::where('sku', 'LLA-0001')->firstOrFail()->units()->updateOrCreate(
            ['name' => $paquete->name],
            ['unit_id' => $paquete->id, 'factor' => 12, 'price' => 50, 'is_base' => false, 'barcode' => 'LLA-0001-PAQ']
        );

        $this->command->info('✅ Presentación de ejemplo creada (Llavero: Paquete x12).');
    }
}
