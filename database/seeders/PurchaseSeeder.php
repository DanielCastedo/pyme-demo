<?php

namespace Database\Seeders;

use App\Models\Purchase;
use App\Models\Supplier;
use App\Services\PurchaseService;
use Database\Seeders\Concerns\UsesDemoData;
use Illuminate\Database\Seeder;

/**
 * Una compra pagada al contado desde la caja: entra el stock (un lote por línea) y sale el dinero de la caja.
 * El tornillo se compra por Caja x100: el costo de la caja (Bs 15) se reparte entre sus 100 tornillos (Bs 0.15 c/u).
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
            'note' => 'Herramientas manuales y tornillos para armar juegos',
            'pay_now' => true,
            'payment_id' => $this->cash()->id,
            'cashbox_id' => $this->cashbox()->id,
        ], [
            ['product_unit_id' => $this->unit('HMA-0002')->id, 'quantity' => 40, 'unit_cost' => 10],         // 40 destornilladores = Bs 400
            ['product_unit_id' => $this->unit('HMA-0003')->id, 'quantity' => 10, 'unit_cost' => 28],         // 10 alicates         = Bs 280
            ['product_unit_id' => $this->unit('TOR-0001', 'Caja')->id, 'quantity' => 5, 'unit_cost' => 15], // 5 cajas = 500 tornillos = Bs 75
        ], $this->admin());

        $this->command->info('✅ Compra de ejemplo registrada (Bs 755 pagados desde la caja).');

        // Una segunda compra a crédito con el otro proveedor: queda un saldo pendiente (con un anticipo)
        // para poder ver el flujo de compras a crédito y sus abonos (App\Services\PurchasePaymentService).
        $purchases->register([
            'warehouse_id' => $this->warehouse()->id,
            'branche_id' => $this->branch(BranchSeeder::CENTRAL)->id,
            'date' => now()->subDays(5)->toDateString(),
            'supplier_id' => Supplier::where('document', '1029384019')->firstOrFail()->id,
            'invoice_number' => 'FAC-0002',
            'note' => 'Reposición de herramientas manuales a crédito',
            'payment_type' => 'credit',
            'due_date' => now()->addDays(30)->toDateString(),
            'initial_payment' => 100,
            'payment_id' => $this->cash()->id,
            'cashbox_id' => $this->cashbox()->id,
        ], [
            ['product_unit_id' => $this->unit('HMA-0002')->id, 'quantity' => 60, 'unit_cost' => 10], // 60 destornilladores = Bs 600
            ['product_unit_id' => $this->unit('HMA-0003')->id, 'quantity' => 10, 'unit_cost' => 28], // 10 alicates         = Bs 280
        ], $this->admin());

        $this->command->info('✅ Compra a crédito de ejemplo registrada (Bs 880, anticipo Bs 100, saldo Bs 780).');
    }
}
