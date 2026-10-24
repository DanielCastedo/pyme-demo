<?php

namespace Database\Seeders;

use App\Models\StockMovement;
use App\Models\Type;
use App\Services\StockMovementService;
use Database\Seeders\Concerns\UsesDemoData;
use Illuminate\Database\Seeder;

/**
 * Entradas de stock a mano (sin compra). Cada entrada crea un lote con su propio costo:
 * el ambroxol entra en dos lotes a distinto costo, así se ve cómo la venta gasta primero el más antiguo (FIFO).
 * La amoxicilina entra con poco stock a propósito (ver ProductWarehouseSeeder: su mínimo es 5).
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

        $stock->register($entrada, $this->warehouse()->id, now()->subDays(20), 'Inventario inicial de ambroxol', $this->admin()->id, [
            ['product_unit_id' => $this->unit('MED-0002')->id, 'quantity' => 10, 'unit_cost' => 22],
        ]);

        $stock->register($entrada, $this->warehouse()->id, now()->subDays(10), 'Segundo lote de ambroxol (llegó más caro), amoxicilina y vitamina C', $this->admin()->id, [
            ['product_unit_id' => $this->unit('MED-0002')->id, 'quantity' => 10, 'unit_cost' => 25],
            ['product_unit_id' => $this->unit('MED-0003')->id, 'quantity' => 3, 'unit_cost' => 28],
            ['product_unit_id' => $this->unit('VIT-0001')->id, 'quantity' => 30, 'unit_cost' => 15],
        ]);

        $this->command->info('✅ Entradas de stock de ejemplo registradas (ambroxol en 2 lotes, amoxicilina y vitamina C).');
    }
}
