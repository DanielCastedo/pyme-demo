<?php

namespace Database\Seeders;

use App\Models\Consignment;
use App\Services\ConsignmentService;
use Database\Seeders\Concerns\UsesDemoData;
use Illuminate\Database\Seeder;

/**
 * Una consignación abierta: 5 galones de pintura salen del almacén y quedan con el maestro de obra sin cobrarse;
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
            'note' => 'Pintura para ofrecer en sus obras',
        ], [
            ['product_unit_id' => $this->unit('PIN-0001')->id, 'quantity' => 5],
        ], $this->admin());

        $this->command->info('✅ Consignación de ejemplo registrada (5 galones de pintura).');
    }
}
