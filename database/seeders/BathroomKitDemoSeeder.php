<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductComponent;
use App\Models\ProductUnit;
use App\Models\ProductWarehouse;
use App\Models\StockBatch;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BathroomKitDemoSeeder extends Seeder
{
    public function run(): void
    {
        $category = Category::firstOrCreate(
            ['name' => 'Plomería y Sanitarios'],
            ['description' => 'Juegos de baño, cerámicas, inodoros, tapas, grifería y accesorios.']
        );

        $warehouses = Warehouse::all();
        if ($warehouses->isEmpty()) {
            $warehouses = collect([Warehouse::create(['name' => 'Almacén Central', 'address' => 'Central'])]);
        }

        // 1. Crear las piezas individuales (componentes unitarios)
        $taza = Product::updateOrCreate(
            ['sku' => 'SAN-0001'],
            [
                'category_id' => $category->id,
                'name' => 'Inodoro Cerámica Blanca Incepa',
                'description' => 'Taza sanitaria de cerámica blanca de alta durabilidad.',
                'is_active' => true,
            ]
        );

        $tapa = Product::updateOrCreate(
            ['sku' => 'SAN-0002'],
            [
                'category_id' => $category->id,
                'name' => 'Tapa y Asiento Sanitario Blanco',
                'description' => 'Tapa asiento para inodoro con bisagras ajustables.',
                'is_active' => true,
            ]
        );

        $palanca = Product::updateOrCreate(
            ['sku' => 'SAN-0003'],
            [
                'category_id' => $category->id,
                'name' => 'Palanca y Mecanismo de Descarga',
                'description' => 'Sistema de descarga y palanca cromada universal.',
                'is_active' => true,
            ]
        );

        // 2. Crear el Kit / Lote Completo (Juego de Baño)
        $kitBano = Product::updateOrCreate(
            ['sku' => 'SAN-KIT01'],
            [
                'category_id' => $category->id,
                'name' => 'Juego de Baño Completo 3 Pzs (Inodoro + Tapa + Palanca)',
                'description' => 'Combo completo sanitario: incluye taza de cerámica, tapa asiento y mecanismo de descarga.',
                'is_active' => true,
            ]
        );

        // 3. Crear Unidades de Venta (ProductUnit)
        // Presentación del Kit
        ProductUnit::updateOrCreate(
            ['product_id' => $kitBano->id, 'name' => 'Juego Completo (Combo 3 Pzs)'],
            [
                'factor' => 1.0000,
                'price' => 850.00,
                'is_base' => true,
            ]
        );

        // Presentaciones de las Piezas Individuales
        ProductUnit::updateOrCreate(
            ['product_id' => $taza->id, 'name' => 'Pieza / Unidad'],
            [
                'factor' => 1.0000,
                'price' => 650.00,
                'is_base' => true,
            ]
        );

        ProductUnit::updateOrCreate(
            ['product_id' => $tapa->id, 'name' => 'Pieza / Unidad'],
            [
                'factor' => 1.0000,
                'price' => 120.00,
                'is_base' => true,
            ]
        );

        ProductUnit::updateOrCreate(
            ['product_id' => $palanca->id, 'name' => 'Pieza / Unidad'],
            [
                'factor' => 1.0000,
                'price' => 80.00,
                'is_base' => true,
            ]
        );

        // 4. Relación en product_components: Vincular las 3 piezas al Kit
        ProductComponent::updateOrCreate(
            ['product_id' => $kitBano->id, 'component_product_id' => $taza->id],
            ['quantity' => 1]
        );

        ProductComponent::updateOrCreate(
            ['product_id' => $kitBano->id, 'component_product_id' => $tapa->id],
            ['quantity' => 1]
        );

        ProductComponent::updateOrCreate(
            ['product_id' => $kitBano->id, 'component_product_id' => $palanca->id],
            ['quantity' => 1]
        );

        // 5. Stock inicial en todos los almacenes. El stock y el costo salen de los lotes (FIFO): cada pieza entra
        //    como un lote de apertura con su costo, y min_stock se guarda aparte. Idempotente: no repite lotes.
        $inicial = [
            [$kitBano, 12, 2, 515.00],
            [$taza, 20, 3, 420.00],
            [$tapa, 35, 5, 60.00],
            [$palanca, 50, 5, 35.00],
        ];

        foreach ($warehouses as $warehouse) {
            foreach ($inicial as [$product, $stock, $minStock, $cost]) {
                if (! StockBatch::where('product_id', $product->id)->where('warehouse_id', $warehouse->id)->exists()) {
                    ProductWarehouse::receiveStock($product->id, $warehouse->id, $stock, $cost, null, now()->subDays(10));
                }

                DB::table('product_warehouses')
                    ->where('product_id', $product->id)
                    ->where('warehouse_id', $warehouse->id)
                    ->update(['min_stock' => $minStock]);
            }
        }
        $this->command->info('✅ Datos de prueba para Lotes y Unitarios creados con éxito.');
    }
}
