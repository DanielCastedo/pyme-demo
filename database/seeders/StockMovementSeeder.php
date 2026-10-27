<?php

namespace Database\Seeders;

use App\Models\StockMovement;
use App\Models\Type;
use App\Services\StockMovementService;
use Database\Seeders\Concerns\UsesDemoData;
use Illuminate\Database\Seeder;

/**
 * Entradas de stock a mano (sin compra). Cada entrada crea un lote con su propio costo:
 * el martillo entra en dos lotes a distinto costo, así se ve cómo la venta gasta primero el más antiguo (FIFO).
 * El taladro entra con poco stock a propósito (ver ProductWarehouseSeeder: su mínimo es 5).
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

        $stock->register($entrada, $this->warehouse()->id, now()->subDays(20), 'Inventario inicial de martillos', $this->admin()->id, [
            ['product_unit_id' => $this->unit('HMA-0001')->id, 'quantity' => 10, 'unit_cost' => 45],
        ]);

        $stock->register($entrada, $this->warehouse()->id, now()->subDays(10), 'Segundo lote de martillos (llegó más caro), taladros y pintura', $this->admin()->id, [
            ['product_unit_id' => $this->unit('HMA-0001')->id, 'quantity' => 10, 'unit_cost' => 50],
            ['product_unit_id' => $this->unit('HEL-0001')->id, 'quantity' => 3, 'unit_cost' => 380],
            ['product_unit_id' => $this->unit('PIN-0001')->id, 'quantity' => 30, 'unit_cost' => 60],
        ]);

        $stock->register($entrada, $this->warehouse('ALM-P3K')->id, now()->subDays(10), 'Inventario inicial del Depósito Plan 3000', $this->admin()->id, [
            ['product_unit_id' => $this->unit('HMA-0001')->id, 'quantity' => 8, 'unit_cost' => 48],
            ['product_unit_id' => $this->unit('PIN-0001')->id, 'quantity' => 12, 'unit_cost' => 62],
            ['product_unit_id' => $this->unit('TOR-0001', 'Caja')->id, 'quantity' => 3, 'unit_cost' => 16],
        ]);

        $this->command->info('✅ Entradas de stock de ejemplo registradas (Almacén Central y Depósito Plan 3000).');
    }
}
