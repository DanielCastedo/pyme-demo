<?php

namespace Database\Seeders;

use App\Models\Purchase;
use App\Models\Supplier;
use App\Services\PurchaseService;
use Database\Seeders\Concerns\UsesDemoData;
use Illuminate\Database\Seeder;

/**
 * Una compra pagada al contado desde la caja: entra el stock (un lote por línea) y sale el dinero de la caja.
 * La cerveza se compra por Caja x24: el costo de la caja (Bs 150) se reparte entre sus 24 latas (Bs 6.25 c/u).
 */
class PurchaseSeeder extends Seeder
{
    use UsesDemoData;

    public function run(PurchaseService $purchases): void
    {
        if (Purchase::exists()) {
            $this->command->info('Ya existen compras, se omite PurchaseSeeder.');

            return;
        }

        $purchases->register([
            'warehouse_id' => $this->warehouse()->id,
            'branche_id' => $this->branch(BranchSeeder::CENTRAL)->id,
            'date' => now()->subDays(15)->toDateString(),
            'supplier_id' => Supplier::where('document', '3049582019')->firstOrFail()->id,
            'invoice_number' => 'FAC-0001',
            'note' => 'Singani, gaseosas, hielo y cerveza para el fin de semana',
            'pay_now' => true,
            'payment_id' => $this->cash()->id,
            'cashbox_id' => $this->cashbox()->id,
        ], [
            ['product_unit_id' => $this->unit('SIN-0001')->id, 'quantity' => 20, 'unit_cost' => 50],         // 20 singanis = Bs 1.000
            ['product_unit_id' => $this->unit('GAS-0001')->id, 'quantity' => 30, 'unit_cost' => 9],          // 30 gaseosas = Bs 270
            ['product_unit_id' => $this->unit('HIE-0001')->id, 'quantity' => 20, 'unit_cost' => 5],          // 20 hielos   = Bs 100
            ['product_unit_id' => $this->unit('CER-0001', 'Caja')->id, 'quantity' => 5, 'unit_cost' => 150], // 5 cajas = 120 latas = Bs 750
        ], $this->admin());

        $this->command->info('✅ Compra de ejemplo registrada (Bs 2.120 pagados desde la caja).');

        // Una segunda compra a crédito con el otro proveedor: queda un saldo pendiente (con un anticipo)
        // para poder ver el flujo de compras a crédito y sus abonos (App\Services\PurchasePaymentService).
        $purchases->register([
            'warehouse_id' => $this->warehouse()->id,
            'branche_id' => $this->branch(BranchSeeder::CENTRAL)->id,
            'date' => now()->subDays(5)->toDateString(),
            'supplier_id' => Supplier::where('document', '1029384019')->firstOrFail()->id,
            'invoice_number' => 'FAC-0002',
            'note' => 'Reposición de singani a crédito',
            'payment_type' => 'credit',
            'due_date' => now()->addDays(30)->toDateString(),
            'initial_payment' => 200,
            'payment_id' => $this->cash()->id,
            'cashbox_id' => $this->cashbox()->id,
        ], [
            ['product_unit_id' => $this->unit('SIN-0001')->id, 'quantity' => 20, 'unit_cost' => 50], // 20 singanis = Bs 1.000
        ], $this->admin());

        $this->command->info('✅ Compra a crédito de ejemplo registrada (Bs 1.000, anticipo Bs 200, saldo Bs 800).');
    }
}
