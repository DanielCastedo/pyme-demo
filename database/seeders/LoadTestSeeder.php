<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Transaction;
use App\Models\Type;
use App\Models\Unit;
use App\Services\PurchaseService;
use App\Services\SaleService;
use App\Services\StockMovementService;
use Database\Seeders\Concerns\UsesDemoData;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

/**
 * Datos de VOLUMEN (no de historia): a diferencia de los demás seeders de "database/seeders" (que arman
 * una historia chica y a mano para explicar el sistema), este genera miles de registros al azar para
 * probar cómo se comporta el sistema con una base de datos grande (listados, paginación, reportes, dashboard).
 *
 * NO se llama desde DatabaseSeeder::run() — es pesado (varios minutos) y no aporta nada para entender el
 * sistema, solo para medirlo. Se corre a mano, aparte:
 *
 *     php artisan db:seed --class=LoadTestSeeder
 *
 * Requiere que ya haya corrido lo básico (branches, categorías, unidades, usuarios, sucursal/almacén/caja
 * de ejemplo): lo más simple es tener la base ya sembrada con el seeder normal (`php artisan migrate --seed`)
 * y recién ahí correr este.
 *
 * Genera (aprox.): 200 proveedores, 2.500 clientes, 1.200 productos (con su unidad base y stock inicial),
 * 300 compras y 2.500 ventas — unos 20.000 registros contando las líneas, movimientos de stock, lotes y
 * transacciones que eso arrastra.
 */
class LoadTestSeeder extends Seeder
{
    use UsesDemoData;

    private const SUPPLIERS = 200;

    private const CUSTOMERS = 2500;

    private const PRODUCTS = 1200;

    private const PURCHASES = 300;

    private const SALES = 2500;

    /** Prefijo de los SKU generados: sirve para detectar que esto ya corrió y para poder ubicar/borrar los datos después. */
    private const SKU_PREFIX = 'LT-';

    public function run(StockMovementService $stock, PurchaseService $purchases, SaleService $sales): void
    {
        if (Product::where('sku', 'like', self::SKU_PREFIX.'%')->exists()) {
            $this->command->info('Ya se corrió LoadTestSeeder antes (hay productos "'.self::SKU_PREFIX.'*"), se omite.');

            return;
        }

        // Miles de inserts uno por uno: el log de queries en memoria de lo contrario crece sin límite.
        DB::connection()->disableQueryLog();

        $this->command->info('Generando datos de carga (esto puede tardar varios minutos)...');

        $supplierIds = $this->makeSuppliers();
        $this->command->info('✅ '.self::SUPPLIERS.' proveedores creados.');

        $customerIds = $this->makeCustomers();
        $this->command->info('✅ '.self::CUSTOMERS.' clientes creados.');

        $units = $this->makeProducts();
        $this->command->info('✅ '.self::PRODUCTS.' productos creados (con su unidad base).');

        $this->loadInitialStock($stock, $units);
        $this->command->info('✅ Stock inicial cargado para los '.self::PRODUCTS.' productos.');

        $this->makePurchases($purchases, $supplierIds, $units);
        $this->command->info('✅ '.self::PURCHASES.' compras registradas.');

        $orderIds = $this->makeSales($sales, $customerIds, $units);
        $this->command->info('✅ '.self::SALES.' ventas registradas.');

        $this->spreadDatesOverLastMonths($orderIds);
        $this->command->info('✅ Fechas de las ventas repartidas en los últimos 6 meses (para poder probar reportes por rango).');

        $this->command->info('✅ Listo: datos de carga generados.');
    }

    /** @return array<int, int> IDs de los proveedores creados */
    private function makeSuppliers(): array
    {
        $ids = [];

        for ($i = 1; $i <= self::SUPPLIERS; $i++) {
            $ids[] = Supplier::create([
                'name' => 'LT Proveedor '.fake()->company(),
                'document_type' => 'nit',
                'document' => 'LT'.str_pad((string) $i, 8, '0', STR_PAD_LEFT),
                'contact_name' => fake()->name(),
                'phone' => fake()->numerify('7#######'),
                'email' => fake()->unique()->safeEmail(),
                'address' => fake()->streetAddress(),
                'city' => fake()->randomElement(['Santa Cruz', 'La Paz', 'Cochabamba', 'Sucre', 'Tarija']),
                'is_active' => true,
            ])->id;
        }

        return $ids;
    }

    /** @return array<int, int> IDs de los clientes creados */
    private function makeCustomers(): array
    {
        $ids = [];

        foreach (Customer::factory()->count(self::CUSTOMERS)->create() as $customer) {
            $ids[] = $customer->id;
        }

        return $ids;
    }

    /** @return array<int, int> IDs de las unidades base (product_units) creadas, una por producto */
    private function makeProducts(): array
    {
        $categoryIds = Category::pluck('id');
        $unitId = Unit::where('is_active', true)->value('id') ?? Unit::first()->id;
        $unitName = Unit::find($unitId)->name;

        $unitIds = [];

        for ($i = 1; $i <= self::PRODUCTS; $i++) {
            $product = Product::create([
                'sku' => self::SKU_PREFIX.str_pad((string) $i, 6, '0', STR_PAD_LEFT),
                'name' => ucfirst(fake()->words(3, true)),
                'description' => fake()->sentence(),
                'marca' => fake()->company(),
                'category_id' => $categoryIds->random(),
                'is_active' => true,
            ]);

            $unitIds[] = $product->units()->create([
                'unit_id' => $unitId,
                'name' => $unitName,
                'factor' => 1,
                'price' => fake()->randomFloat(2, 10, 500),
                'is_base' => true,
                'barcode' => $product->sku,
            ])->id;

            if ($i % 200 === 0) {
                $this->command->info("  ...{$i}/".self::PRODUCTS.' productos');
            }
        }

        return $unitIds;
    }

    /** @param  array<int, int>  $unitIds */
    private function loadInitialStock(StockMovementService $stock, array $unitIds): void
    {
        $entrada = Type::where('code', 'ENT')->firstOrFail();

        $items = array_map(fn (int $unitId) => [
            'product_unit_id' => $unitId,
            'quantity' => random_int(200, 500),
            'unit_cost' => fake()->randomFloat(2, 5, 300),
        ], $unitIds);

        // Un solo movimiento con todas las líneas: mucho más rápido que uno por producto.
        $stock->register($entrada, $this->warehouse()->id, now()->subMonths(6), 'Carga inicial de stock (LoadTestSeeder)', $this->admin()->id, $items);
    }

    /**
     * @param  array<int, int>  $supplierIds
     * @param  array<int, int>  $unitIds
     */
    private function makePurchases(PurchaseService $purchases, array $supplierIds, array $unitIds): void
    {
        for ($i = 1; $i <= self::PURCHASES; $i++) {
            $howMany = min(random_int(1, 3), count($unitIds));
            $chosen = (array) array_rand(array_flip($unitIds), $howMany);

            $lines = array_map(fn ($unitId) => [
                'product_unit_id' => $unitId,
                'quantity' => random_int(5, 30),
                'unit_cost' => fake()->randomFloat(2, 5, 300),
            ], $chosen);

            try {
                $purchases->register([
                    'warehouse_id' => $this->warehouse()->id,
                    'date' => now()->subDays(random_int(1, 180))->toDateString(),
                    'supplier_id' => $supplierIds[array_rand($supplierIds)],
                    'invoice_number' => 'LT-FAC-'.$i,
                    'note' => 'Compra de carga (LoadTestSeeder)',
                    'pay_now' => true,
                    'payment_id' => $this->cash()->id,
                    'cashbox_id' => $this->cashbox()->id,
                ], $lines, $this->admin());
            } catch (\RuntimeException) {
                continue; // no afecta el objetivo (volumen): una compra fallida no se cuenta y ya
            }

            if ($i % 100 === 0) {
                $this->command->info("  ...{$i}/".self::PURCHASES.' compras');
            }
        }
    }

    /**
     * @param  array<int, int>  $customerIds
     * @param  array<int, int>  $unitIds
     * @return array<int, int> IDs de las ventas (orders) creadas
     */
    private function makeSales(SaleService $sales, array $customerIds, array $unitIds): array
    {
        $orderIds = [];
        $admin = $this->admin();
        $warehouseId = $this->warehouse()->id;
        $cashboxId = $this->cashbox()->id;
        $paymentId = $this->cash()->id;

        for ($i = 1; $i <= self::SALES; $i++) {
            $howMany = random_int(1, 3);
            $chosen = (array) array_rand(array_flip($unitIds), min($howMany, count($unitIds)));

            $lines = array_map(fn ($unitId) => [
                'product_unit_id' => $unitId,
                'quantity' => random_int(1, 5),
            ], $chosen);

            try {
                $order = $sales->register([
                    'warehouse_id' => $warehouseId,
                    'cashbox_id' => $cashboxId,
                    'payment_id' => $paymentId,
                    'payment_type' => 'cash',
                    'customer_id' => random_int(1, 4) === 1 ? null : $customerIds[array_rand($customerIds)],
                    'note' => 'Venta de carga (LoadTestSeeder)',
                ], $lines, $admin);

                $orderIds[] = $order->id;
            } catch (\RuntimeException) {
                continue; // stock insuficiente en esa línea puntual: se salta, no afecta el objetivo de volumen
            }

            if ($i % 250 === 0) {
                $this->command->info("  ...{$i}/".self::SALES.' ventas');
            }
        }

        return $orderIds;
    }

    /**
     * SaleService siempre fecha la venta "hoy": para poder probar filtros de reportes por rango de fechas,
     * se reparten las fechas (de la orden, sus líneas y su transacción de caja) en los últimos 6 meses.
     *
     * @param  array<int, int>  $orderIds
     */
    private function spreadDatesOverLastMonths(array $orderIds): void
    {
        foreach ($orderIds as $orderId) {
            $date = now()->subDays(random_int(0, 180));

            Order::where('id', $orderId)->update(['date' => $date->toDateString(), 'created_at' => $date, 'updated_at' => $date]);
            Transaction::where('order_id', $orderId)->update(['date' => $date, 'created_at' => $date, 'updated_at' => $date]);
        }
    }
}
