<?php

namespace Database\Seeders;

use App\Models\Purchase;
use App\Models\Supplier;
use App\Services\PurchaseService;
use Database\Seeders\Concerns\UsesDemoData;
use Illuminate\Database\Seeder;

/**
 * Una compra pagada al contado desde la caja: entra el stock (un lote por línea) y sale el dinero de la caja.
 * Los tornillos se compran por Caja x100: el costo de la caja (Bs 60) se reparte entre sus 100 unidades (Bs 0.60 c/u).
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
            'note' => 'Partes para armar mesas',
            'pay_now' => true,
            'payment_id' => $this->cash()->id,
            'cashbox_id' => $this->cashbox()->id,
        ], [
            ['product_unit_id' => $this->unit('FG-0002')->id, 'quantity' => 40, 'unit_cost' => 20],   // 40 patas    = Bs 800
            ['product_unit_id' => $this->unit('FG-0003')->id, 'quantity' => 10, 'unit_cost' => 150],  // 10 tableros = Bs 1.500
            ['product_unit_id' => $this->unit('FG-0001', 'Caja')->id, 'quantity' => 5, 'unit_cost' => 60], // 5 cajas = 500 tornillos = Bs 300
        ], $this->admin());

        $this->command->info('✅ Compra de ejemplo registrada (Bs 2.600 pagados desde la caja).');

        // Una segunda compra a crédito con el otro proveedor: queda un saldo pendiente (con un anticipo)
        // para poder ver el flujo de compras a crédito y sus abonos (App\Services\PurchasePaymentService).
        $purchases->register([
            'warehouse_id' => $this->warehouse()->id,
            'date' => now()->subDays(5)->toDateString(),
            'supplier_id' => Supplier::where('document', '1029384019')->firstOrFail()->id,
            'invoice_number' => 'FAC-0002',
            'note' => 'Reposición de stock a crédito con el proveedor',
            'payment_type' => 'credit',
            'due_date' => now()->addDays(30)->toDateString(),
            'initial_payment' => 200,
            'payment_id' => $this->cash()->id,
            'cashbox_id' => $this->cashbox()->id,
        ], [
            ['product_unit_id' => $this->unit('FG-0002')->id, 'quantity' => 20, 'unit_cost' => 20],  // 20 patas    = Bs 400
            ['product_unit_id' => $this->unit('FG-0003')->id, 'quantity' => 8, 'unit_cost' => 150],  // 8 tableros  = Bs 1.200
        ], $this->admin());

        $this->command->info('✅ Compra a crédito de ejemplo registrada (Bs 1.600, anticipo Bs 200, saldo Bs 1.400).');
    }
}
