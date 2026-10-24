<?php

namespace Database\Seeders;

use App\Models\Purchase;
use App\Models\Supplier;
use App\Services\PurchaseService;
use Database\Seeders\Concerns\UsesDemoData;
use Illuminate\Database\Seeder;

/**
 * Una compra pagada al contado desde la caja: entra el stock (un lote por línea) y sale el dinero de la caja.
 * El paracetamol se compra por Caja x100: el costo de la caja (Bs 25) se reparte entre sus 100 tabletas (Bs 0.25 c/u).
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
            'date' => now()->subDays(15)->toDateString(),
            'supplier_id' => Supplier::where('document', '3049582019')->firstOrFail()->id,
            'invoice_number' => 'FAC-0001',
            'note' => 'Material de curación y paracetamol para botiquines',
            'pay_now' => true,
            'payment_id' => $this->cash()->id,
            'cashbox_id' => $this->cashbox()->id,
        ], [
            ['product_unit_id' => $this->unit('CUR-0001')->id, 'quantity' => 40, 'unit_cost' => 1.5],        // 40 gasas    = Bs 60
            ['product_unit_id' => $this->unit('CUR-0002')->id, 'quantity' => 10, 'unit_cost' => 9],          // 10 alcoholes = Bs 90
            ['product_unit_id' => $this->unit('MED-0001', 'Caja')->id, 'quantity' => 5, 'unit_cost' => 25], // 5 cajas = 500 tabletas = Bs 125
        ], $this->admin());

        $this->command->info('✅ Compra de ejemplo registrada (Bs 275 pagados desde la caja).');

        // Una segunda compra a crédito con el otro proveedor: queda un saldo pendiente (con un anticipo)
        // para poder ver el flujo de compras a crédito y sus abonos (App\Services\PurchasePaymentService).
        $purchases->register([
            'warehouse_id' => $this->warehouse()->id,
            'date' => now()->subDays(5)->toDateString(),
            'supplier_id' => Supplier::where('document', '1029384019')->firstOrFail()->id,
            'invoice_number' => 'FAC-0002',
            'note' => 'Reposición de material de curación a crédito',
            'payment_type' => 'credit',
            'due_date' => now()->addDays(30)->toDateString(),
            'initial_payment' => 50,
            'payment_id' => $this->cash()->id,
            'cashbox_id' => $this->cashbox()->id,
        ], [
            ['product_unit_id' => $this->unit('CUR-0001')->id, 'quantity' => 60, 'unit_cost' => 1.5], // 60 gasas    = Bs 90
            ['product_unit_id' => $this->unit('CUR-0002')->id, 'quantity' => 10, 'unit_cost' => 9],   // 10 alcoholes = Bs 90
        ], $this->admin());

        $this->command->info('✅ Compra a crédito de ejemplo registrada (Bs 180, anticipo Bs 50, saldo Bs 130).');
    }
}
