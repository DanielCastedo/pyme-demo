<?php

namespace Database\Seeders;

use App\Models\Consignment;
use App\Services\ConsignmentService;
use Database\Seeders\Concerns\UsesDemoData;
use Illuminate\Database\Seeder;

/**
 * Una consignación abierta: 12 botellas de vino salen del almacén y quedan en el restaurante sin cobrarse;
 * después se liquida lo que vendió y lo que devuelve.
 */
class ConsignmentSeeder extends Seeder
{
    use UsesDemoData;

    public function run(ConsignmentService $consignments): void
    {
        if (Consignment::exists()) {
            $this->command->info('Ya existen consignaciones, se omite ConsignmentSeeder.');

            return;
        }

        $consignments->create([
            'customer_id' => $this->customer('7788990')->id,
            'warehouse_id' => $this->warehouse()->id,
            'branche_id' => $this->branch(BranchSeeder::CENTRAL)->id,
            'due_date' => now()->addDays(30)->toDateString(),
            'note' => 'Vino para ofrecer en la carta del restaurante',
        ], [
            ['product_unit_id' => $this->unit('VIN-0001')->id, 'quantity' => 12],
        ], $this->admin());

        $this->command->info('✅ Consignación de ejemplo registrada (12 botellas de vino).');
    }
}
