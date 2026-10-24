<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Database\Seeder;

/**
 * Siete productos de farmacia, cada uno para explicar algo distinto:
 *  - Ambroxol:     producto simple (dos entradas a distinto costo → se ve el FIFO).
 *  - Paracetamol:  se vende por tableta suelta o en "Caja x100" (ver ProductUnitSeeder).
 *  - Gasa, Alcohol y Botiquín: el Botiquín es un kit armado con gasas + alcohol + paracetamol (ver ProductComponentSeeder).
 *  - Amoxicilina:  queda con poco stock para que aparezca la alerta de stock mínimo.
 *  - Vitamina C:   producto de la categoría con promoción (ver PromotionSeeder).
 *
 * "marca" guarda el laboratorio y "medida" la concentración / presentación.
 */
class ProductSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['MED-0001', 'Paracetamol 500mg', 'Analgésicos y Antipiréticos', 'Tableta', 0.50, 'Se vende por tableta suelta o en caja de 100.', 'Laboratorios Bagó', '500mg'],
            ['MED-0002', 'Ambroxol jarabe adulto 120ml', 'Respiratorios y Antigripales', 'Frasco', 35, 'Expectorante. 30mg/5ml.', 'Laboratorios INTI', '120ml'],
            ['MED-0003', 'Amoxicilina suspensión 250mg/5ml', 'Antibióticos', 'Frasco', 45, 'Venta con receta médica. Polvo para reconstituir.', 'Laboratorios Vita', '60ml'],
            ['CUR-0001', 'Gasa estéril 10x10cm', 'Material de Curación', 'Sobre', 3, 'Sobre individual estéril.', null, '10x10cm'],
            ['CUR-0002', 'Alcohol antiséptico 70% 250ml', 'Material de Curación', 'Frasco', 15, 'Alcohol etílico al 70%.', null, '250ml'],
            ['CUR-0003', 'Botiquín de primeros auxilios', 'Material de Curación', 'Unidad', 60, 'Kit: 4 gasas + 1 alcohol 250ml + 8 tabletas de paracetamol.', null, 'Básico'],
            ['VIT-0001', 'Vitamina C 1g efervescente x10', 'Vitaminas y Suplementos', 'Tubo', 25, 'Tubo de 10 tabletas efervescentes.', 'Laboratorios Bagó', '1g'],
        ] as [$sku, $name, $category, $unitName, $price, $description, $laboratorio, $medida]) {
            $product = Product::updateOrCreate(['sku' => $sku], [
                'name' => $name,
                'description' => $description,
                'marca' => $laboratorio,
                'color' => null,
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
