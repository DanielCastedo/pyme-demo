<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Services\SaleService;
use Database\Seeders\Concerns\UsesDemoData;
use Illuminate\Database\Seeder;

/**
 * Tres ventas, una por cada caso que conviene ver:
 *  1) Un KIT al contado: se descuentan sus partes (4 patas, 1 tablero, 8 tornillos).
 *  2) Contado con descuento (Bs 10 sobre la línea): el costo sale del lote más antiguo (Bs 35 c/u).
 *  3) A crédito con anticipo: el cliente deja Bs 100 y queda debiendo el resto (aparece en Cuentas por cobrar).
 */
class OrderSeeder extends Seeder
{
    use UsesDemoData;

    public function run(SaleService $sales): void
    {
        if (Order::exists()) {
            $this->command->info('Ya existen ventas, se omite OrderSeeder.');

            return;
        }

        $contado = [
            'warehouse_id' => $this->warehouse()->id,
            'cashbox_id' => $this->cashbox()->id,
            'payment_id' => $this->cash()->id,
            'payment_type' => 'cash',
        ];

        // 1) Kit
        $sales->register(array_merge($contado, ['note' => 'Mesa de comedor (kit)']), [
            ['product_unit_id' => $this->unit('FG-0004')->id, 'quantity' => 1],
        ], $this->admin());

        // 2) Contado con descuento
        $sales->register(array_merge($contado, ['customer_id' => $this->customer('6543210')->id, 'note' => 'Dos martillos con descuento']), [
            ['product_unit_id' => $this->unit('HM-0001')->id, 'quantity' => 2, 'discount_type' => 'amount', 'discount_value' => 10],
        ], $this->admin());

        // 3) Crédito con anticipo
        // (array_merge y no "+": con "+" gana la clave de la izquierda y seguiría siendo 'cash')
        $sales->register(array_merge($contado, [
            'customer_id' => $this->customer('1023456029')->id,
            'note' => 'Venta a crédito a 15 días',
            'payment_type' => 'credit',
            'due_date' => now()->addDays(15)->toDateString(),
            'initial_payment' => 100,
        ]), [
            ['product_unit_id' => $this->unit('FG-0001', 'Caja')->id, 'quantity' => 1],
            ['product_unit_id' => $this->unit('PA-0001')->id, 'quantity' => 1],
        ], $this->admin());

        $this->command->info('✅ Ventas de ejemplo registradas (kit, contado con descuento y crédito).');
    }
}
