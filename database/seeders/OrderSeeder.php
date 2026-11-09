<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Services\CashSessionService;
use App\Services\SaleService;
use Database\Seeders\Concerns\UsesDemoData;
use Illuminate\Database\Seeder;

/**
 * Tres ventas, una por cada caso que conviene ver:
 *  1) Un KIT al contado: se descuentan sus partes (4 gasas, 1 alcohol, 8 tabletas de paracetamol).
 *  2) Contado con descuento (Bs 5 sobre la línea): el costo sale del lote de ambroxol que vence antes (Bs 25 c/u).
 *  3) A crédito con anticipo: la clínica deja Bs 50 y queda debiendo el resto (aparece en Cuentas por cobrar).
 *
 * Las ventas se hacen dentro de un turno de caja (Mi caja): se abre con Bs 100 de fondo para dar cambio y al final se
 * cierra contando el efectivo, que cuadra con lo esperado (fondo + ventas en efectivo).
 */
class OrderSeeder extends Seeder
{
    use UsesDemoData;

    public function run(SaleService $sales, CashSessionService $cashSessions): void
    {
        if (Order::exists()) {
            $this->command->info('Ya existen ventas, se omite OrderSeeder.');

            return;
        }

        $session = $cashSessions->open($this->admin(), $this->cashbox(), 100, 'Fondo para dar cambio');

        $contado = [
            'warehouse_id' => $this->warehouse()->id,
            'cashbox_id' => $this->cashbox()->id,
            'payment_id' => $this->cash()->id,
            'payment_type' => 'cash',
        ];

        // 1) Kit
        $sales->register(array_merge($contado, ['note' => 'Botiquín de primeros auxilios (kit)']), [
            ['product_unit_id' => $this->unit('CUR-0003')->id, 'quantity' => 1],
        ], $this->admin());

        // 2) Contado con descuento
        $sales->register(array_merge($contado, ['customer_id' => $this->customer('6543210')->id, 'note' => 'Dos jarabes de ambroxol con descuento']), [
            ['product_unit_id' => $this->unit('MED-0002')->id, 'quantity' => 2, 'discount_type' => 'amount', 'discount_value' => 5],
        ], $this->admin());

        // 3) Crédito con anticipo
        // (array_merge y no "+": con "+" gana la clave de la izquierda y seguiría siendo 'cash')
        $sales->register(array_merge($contado, [
            'customer_id' => $this->customer('1023456029')->id,
            'note' => 'Venta a crédito a 15 días para la clínica',
            'payment_type' => 'credit',
            'due_date' => now()->addDays(15)->toDateString(),
            'initial_payment' => 50,
        ]), [
            ['product_unit_id' => $this->unit('MED-0001', 'Caja')->id, 'quantity' => 2],
            ['product_unit_id' => $this->unit('MED-0003')->id, 'quantity' => 1],
        ], $this->admin());

        // Cierre del turno: el efectivo contado es exactamente el esperado (fondo + ventas en efectivo).
        $cashSessions->close($session, $session->summary()['expected_cash'], $this->admin(), 'Cierre del día');

        $this->command->info('✅ Ventas de ejemplo registradas (kit, contado con descuento y crédito) en un turno de caja abierto con Bs 100 y ya cerrado.');
    }
}
