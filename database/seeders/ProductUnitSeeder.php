<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Unit;
use Illuminate\Database\Seeder;

/**
 * Presentaciones (además de la unidad base que ya crea ProductSeeder).
 * Ejemplo: el paracetamol se vende por tableta a Bs 0.50 o en "Caja" de 100 a Bs 40 (factor 100 = trae 100 tabletas).
 */
class ProductUnitSeeder extends Seeder
{
    public function run(): void
    {
        $caja = Unit::where('name', 'Caja')->firstOrFail();

        Product::where('sku', 'MED-0001')->firstOrFail()->units()->updateOrCreate(
            ['name' => $caja->name],
            ['unit_id' => $caja->id, 'factor' => 100, 'price' => 40, 'is_base' => false, 'barcode' => 'MED-0001-CAJA']
        );

        $this->command->info('✅ Presentación de ejemplo creada (Paracetamol: Caja x100).');
    }
}
