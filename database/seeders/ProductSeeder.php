<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Database\Seeder;

/**
 * Siete productos de ferretería, cada uno para explicar algo distinto:
 *  - Martillo:      producto simple (dos entradas a distinto costo → se ve el FIFO).
 *  - Tornillo:      se vende suelto o en "Caja x100" (ver ProductUnitSeeder).
 *  - Destornillador, Alicate y Juego de herramientas: el juego es un kit armado con
 *                   2 destornilladores + 1 alicate + 20 tornillos (ver ProductComponentSeeder).
 *  - Taladro:       queda con poco stock para que aparezca la alerta de stock mínimo.
 *  - Pintura látex: producto de la categoría con promoción (ver PromotionSeeder).
 */
class ProductSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['TOR-0001', 'Tornillo autorroscante 8 x 1"', 'Tornillería y Fijaciones', 'Unidad', 0.30, 'Punta broca, cabeza Phillips. Se vende suelto o en caja de 100.', 'Fixser', '8 x 1"', 'Zincado'],
            ['HMA-0001', 'Martillo de uña 16 oz', 'Herramientas Manuales', 'Unidad', 75, 'Mango de fibra de vidrio antivibración.', 'Stanley', '16 oz', 'Amarillo'],
            ['HEL-0001', 'Taladro percutor 650W 13mm', 'Herramientas Eléctricas', 'Unidad', 520, 'Velocidad variable y reversible. Incluye maletín.', 'Bosch', '13mm', 'Azul'],
            ['HMA-0002', 'Destornillador plano 6"', 'Herramientas Manuales', 'Pieza', 18, 'Punta imantada, mango ergonómico.', 'Truper', '6"', null],
            ['HMA-0003', 'Alicate universal 8"', 'Herramientas Manuales', 'Pieza', 45, 'Acero al cromo vanadio, mango aislado.', 'Truper', '8"', 'Rojo'],
            ['KIT-0001', 'Juego de herramientas básico para el hogar', 'Herramientas Manuales', 'Juego', 150, 'Kit: 2 destornilladores + 1 alicate + 20 tornillos.', null, 'Básico', null],
            ['PIN-0001', 'Pintura látex interior blanco', 'Pinturas y Accesorios', 'Galón', 95, 'Lavable, acabado mate. Rinde 35 m² por galón.', 'Monopol', '1 galón', 'Blanco'],
        ] as [$sku, $name, $category, $unitName, $price, $description, $marca, $medida, $color]) {
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
            $unit = Unit::where('name', $unitName)->firstOrFail();

            $product->units()->updateOrCreate(['is_base' => true], [
                'unit_id' => $unit->id,
                'name' => $unit->name,
                'factor' => 1,
                'price' => $price,
                'barcode' => $sku,
            ]);
        }

        $this->command->info('✅ Productos de ejemplo creados correctamente.');
    }
}
