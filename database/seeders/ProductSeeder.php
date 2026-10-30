<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Database\Seeder;

/**
 * Siete productos de una tienda de mochilas y estuches, cada uno para explicar algo distinto:
 *  - Mochila escolar:     producto simple (dos entradas a distinto costo → se ve el FIFO).
 *  - Llavero:             se vende suelto o en "Paquete x12" (ver ProductUnitSeeder).
 *  - Estuche, Lonchera y Combo regreso a clases: el combo es un kit armado con
 *                         1 estuche + 1 lonchera + 2 llaveros (ver ProductComponentSeeder).
 *  - Mochila para laptop: queda con poco stock para que aparezca la alerta de stock mínimo.
 *  - Bolso deportivo:     producto de la categoría con promoción (ver PromotionSeeder).
 */
class ProductSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['LLA-0001', 'Llavero colgante para mochila', 'Accesorios', 'Unidad', 5, 'Figuras surtidas de goma. Se vende suelto o en paquete de 12.', 'Kawaii', '6 cm', 'Surtido'],
            ['MOC-0001', 'Mochila escolar clásica 18"', 'Mochilas Escolares', 'Unidad', 180, 'Dos compartimentos, espalda acolchada y tela impermeable.', 'Totto', '18"', 'Azul marino'],
            ['MOC-0002', 'Mochila antirrobo para laptop 15.6"', 'Mochilas Urbanas y de Laptop', 'Unidad', 420, 'Cierre oculto, puerto USB y compartimento acolchado para laptop.', 'Xtrem', '15.6"', 'Gris'],
            ['EST-0001', 'Estuche escolar de doble cierre', 'Estuches y Cartucheras', 'Unidad', 35, 'Dos compartimentos, tela poliéster lavable.', 'Totto', '22 x 10 cm', 'Rosado'],
            ['LON-0001', 'Lonchera térmica', 'Loncheras', 'Unidad', 65, 'Forro térmico: mantiene frío o calor hasta 4 horas.', 'Xtrem', '24 x 18 cm', 'Verde'],
            ['KIT-0001', 'Combo regreso a clases', 'Mochilas Escolares', 'Combo', 99, 'Combo: 1 estuche + 1 lonchera + 2 llaveros.', null, 'Escolar', null],
            ['BOL-0001', 'Bolso deportivo 50 L', 'Bolsos y Maletines', 'Unidad', 150, 'Compartimento para calzado y correa acolchada.', 'Adidas', '50 L', 'Negro'],
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
