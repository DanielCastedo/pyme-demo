<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Database\Seeder;

/**
 * Ocho productos de licorería, cada uno para explicar algo distinto:
 *  - Whisky:   producto simple (dos entradas a distinto costo → se ve el FIFO).
 *  - Cerveza:  se vende por lata o en "Caja x24" (ver ProductUnitSeeder).
 *  - Singani, Gaseosa, Hielo y Combo Chuflay: el combo es un kit armado con
 *              1 singani + 2 gaseosas + 1 bolsa de hielo (ver ProductComponentSeeder).
 *  - Ron:      queda con poco stock para que aparezca la alerta de stock mínimo.
 *  - Vino:     producto de la categoría con promoción (ver PromotionSeeder).
 *
 * "marca" guarda la marca, "medida" el contenido y "color" el tipo (tinto, rubia, etc.).
 */
class ProductSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['CER-0001', 'Cerveza rubia lata 354ml', 'Cervezas', 'Lata', 10, 'Se vende por lata o en caja de 24.', 'Paceña', '354ml', 'Rubia'],
            ['WHI-0001', 'Whisky escocés Red Label 750ml', 'Whiskys', 'Botella', 180, 'Blended Scotch whisky, 40% vol.', 'Johnnie Walker', '750ml', null],
            ['RON-0001', 'Ron añejo 7 años 750ml', 'Rones y Vodkas', 'Botella', 210, 'Ron cubano añejado 7 años, 40% vol.', 'Havana Club', '750ml', 'Añejo'],
            ['SIN-0001', 'Singani etiqueta negra 750ml', 'Singanis y Piscos', 'Botella', 75, 'Singani boliviano de uva moscatel, 40% vol.', 'Casa Real', '750ml', null],
            ['GAS-0001', 'Gaseosa lima-limón 2L', 'Bebidas sin Alcohol y Hielo', 'Botella', 15, 'Ideal para preparar chuflay.', 'Sprite', '2L', null],
            ['HIE-0001', 'Hielo en cubos 3kg', 'Bebidas sin Alcohol y Hielo', 'Bolsa', 10, 'Bolsa de cubos de hielo.', null, '3kg', null],
            ['CMB-0001', 'Combo Chuflay', 'Singanis y Piscos', 'Combo', 110, 'Kit: 1 singani 750ml + 2 gaseosas 2L + 1 bolsa de hielo.', null, 'Para 8 vasos', null],
            ['VIN-0001', 'Vino tinto Cabernet Sauvignon 750ml', 'Vinos y Espumantes', 'Botella', 65, 'Vino de altura de Tarija, 13% vol.', 'Kohlberg', '750ml', 'Tinto'],
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
