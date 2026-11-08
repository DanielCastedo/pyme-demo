<?php

namespace Database\Seeders;

use App\Models\StockMovement;
use App\Models\Type;
use App\Services\StockMovementService;
use Database\Seeders\Concerns\UsesDemoData;
use Illuminate\Database\Seeder;

/**
 * Entradas de stock a mano (sin compra). Cada entrada crea un lote con su propio costo:
 * el whisky entra en dos lotes a distinto costo, así se ve cómo la venta gasta primero el más antiguo (FIFO).
 * El ron entra con poco stock a propósito (ver ProductWarehouseSeeder: su mínimo es 5).
 * El Depósito Plan 3000 recibe su propio stock: esa sucursal no comparte almacén.
 */
class StockMovementSeeder extends Seeder
{
    use UsesDemoData;

    public function run(StockMovementService $stock): void
    {
        if (StockMovement::exists()) {
            $this->command->info('Ya existen movimientos de stock, se omite StockMovementSeeder.');

            return;
        }

        $entrada = Type::where('code', 'ENT')->firstOrFail();

        $stock->register($entrada, $this->warehouse()->id, now()->subDays(20), 'Inventario inicial de whisky', $this->admin()->id, [
            ['product_unit_id' => $this->unit('WHI-0001')->id, 'quantity' => 10, 'unit_cost' => 120],
        ]);

        $stock->register($entrada, $this->warehouse()->id, now()->subDays(10), 'Segundo lote de whisky (llegó más caro), ron y vino', $this->admin()->id, [
            ['product_unit_id' => $this->unit('WHI-0001')->id, 'quantity' => 10, 'unit_cost' => 130],
            ['product_unit_id' => $this->unit('RON-0001')->id, 'quantity' => 3, 'unit_cost' => 150],
            ['product_unit_id' => $this->unit('VIN-0001')->id, 'quantity' => 30, 'unit_cost' => 40],
        ]);

        $stock->register($entrada, $this->warehouse('ALM-P3K')->id, now()->subDays(10), 'Inventario inicial del Depósito Plan 3000', $this->admin()->id, [
            ['product_unit_id' => $this->unit('WHI-0001')->id, 'quantity' => 8, 'unit_cost' => 125],
            ['product_unit_id' => $this->unit('VIN-0001')->id, 'quantity' => 12, 'unit_cost' => 42],
            ['product_unit_id' => $this->unit('CER-0001', 'Caja')->id, 'quantity' => 3, 'unit_cost' => 155],
        ]);

        $this->command->info('✅ Entradas de stock de ejemplo registradas (Almacén Central y Depósito Plan 3000).');
    }
}
