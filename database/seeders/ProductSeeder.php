<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Database\Seeder;

/**
 * Seis productos, cada uno para explicar algo distinto:
 *  - Martillo:  producto simple (dos compras a distinto costo → se ve el FIFO).
 *  - Tornillo:  tiene una presentación "Caja x100" (ver ProductUnitSeeder).
 *  - Pata, Tablero y Mesa: la Mesa es un kit armado con las otras dos partes + tornillos (ver ProductComponentSeeder).
 *  - Pintura:   queda con poco stock para que aparezca la alerta de stock mínimo.
 */
class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $unidad = Unit::where('name', 'Unidad')->firstOrFail();

        foreach ([
            ['HM-0001', 'Martillo de uña 16oz', 'Herramientas Manuales', 55, 'Mango de fibra de vidrio.', 'Stanley', null, '16oz'],
            ['FG-0001', 'Tornillo autorroscante', 'Ferretería General', 1, 'Se vende suelto o por caja de 100.', 'Fischer', null, '1/4 pulgada'],
            ['FG-0002', 'Pata de mesa', 'Ferretería General', 30, 'Pata de madera para mesa.', null, 'Caoba', '75cm'],
            ['FG-0003', 'Tablero de mesa', 'Ferretería General', 250, 'Tablero de madera para mesa.', null, 'Caoba', '120x80cm'],
            ['FG-0004', 'Mesa de comedor', 'Ferretería General', 600, 'Kit: 4 patas + 1 tablero + 8 tornillos.', null, 'Caoba', '120x80x75cm'],
            ['PA-0001', 'Pintura látex blanco 20L', 'Pinturas y Acabados', 260, 'Interior y exterior, lavable.', 'Sherwin Williams', 'Blanco', '20L'],
        ] as [$sku, $name, $category, $price, $description, $marca, $color, $medida]) {
            $product = Product::updateOrCreate(['sku' => $sku], [
                'name' => $name,
                'description' => $description,
                'marca' => $marca,
                'color' => $color,
                'medida' => $medida,
                'category_id' => Category::where('name', $category)->firstOrFail()->id,
                'is_active' => true,
            ]);

            // El precio de venta vive en la unidad base del producto (factor 1); su código de barras es el SKU.
            $product->units()->updateOrCreate(['is_base' => true], [
                'unit_id' => $unidad->id,
                'name' => $unidad->name,
                'factor' => 1,
                'price' => $price,
                'barcode' => $sku,
            ]);
        }

        $this->command->info('✅ Productos de ejemplo creados correctamente.');
    }
}
