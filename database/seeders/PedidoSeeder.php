<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Services\OrderService;
use Database\Seeders\Concerns\UsesDemoData;
use Illuminate\Database\Seeder;

/**
 * Un pedido en espera: todavía no descuenta stock ni cobra; se confirma después como venta.
 */
class PedidoSeeder extends Seeder
{
    use UsesDemoData;

    public function run(OrderService $pedidos): void
    {
        if (Order::whereNotNull('delivery_date')->exists()) {
            $this->command->info('Ya existen pedidos, se omite PedidoSeeder.');

            return;
        }

        $pedidos->create([
            'customer_id' => $this->customer('7788990')->id,
            'warehouse_id' => $this->warehouse()->id,
            'delivery_date' => now()->addDays(3)->toDateString(),
            'note' => 'Retira en 3 días',
        ], [
            ['product_unit_id' => $this->unit('MED-0002')->id, 'quantity' => 3],
        ], $this->admin());

        $this->command->info('✅ Pedido de ejemplo registrado (3 jarabes de ambroxol, en espera).');
    }
}
