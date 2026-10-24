<?php

namespace Database\Seeders;

use App\Models\StockMovement;
use App\Models\Type;
use App\Services\StockMovementService;
use Database\Seeders\Concerns\UsesDemoData;
use Illuminate\Database\Seeder;

/**
 * Entradas de stock a mano (sin compra). Cada entrada crea un lote con su propio costo:
 * los martillos entran en dos lotes a distinto costo, así se ve cómo la venta gasta primero el más antiguo (FIFO).
 * La pintura entra con poco stock a propósito (ver ProductWarehouseSeeder: su mínimo es 5).
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

        $stock->register($entrada, $this->warehouse()->id, now()->subDays(20), 'Primer lote de martillos', $this->admin()->id, [
            ['product_unit_id' => $this->unit('HM-0001')->id, 'quantity' => 10, 'unit_cost' => 35],
        ]);

        $stock->register($entrada, $this->warehouse()->id, now()->subDays(10), 'Segundo lote (llegó más caro) y pintura', $this->admin()->id, [
            ['product_unit_id' => $this->unit('HM-0001')->id, 'quantity' => 10, 'unit_cost' => 40],
            ['product_unit_id' => $this->unit('PA-0001')->id, 'quantity' => 3, 'unit_cost' => 180],
        ]);

        $this->command->info('✅ Entradas de stock de ejemplo registradas (martillos en 2 lotes y pintura).');
    }
}
