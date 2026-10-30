<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\User;
use App\Services\SaleService;
use Database\Seeders\Concerns\UsesDemoData;
use Illuminate\Database\Seeder;

/**
 * Ocho ventas, una por cada caso que conviene ver:
 *  1) Un KIT al contado: se descuentan sus partes (1 estuche, 1 lonchera, 2 llaveros).
 *  2) Contado con descuento (Bs 10 sobre la línea): el costo sale del lote más antiguo de mochilas escolares (Bs 110 c/u).
 *  3) A crédito con anticipo: el colegio deja Bs 100 y queda debiendo el resto (aparece en Cuentas por cobrar).
 *  4) La sucursal Norte vende del Almacén Central (compartido) y cobra en su propia caja.
 *  5) La sucursal Plan 3000 vende de su propio depósito.
 *  6-8) Una venta cobrada por QR en cada sucursal: suben el saldo de la caja pero no el efectivo
 *       (se ven en la columna "QR" de Cajas).
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

        $central = $this->branch(BranchSeeder::CENTRAL);
        $contado = [
            'warehouse_id' => $this->warehouse()->id,
            'branche_id' => $central->id,
            'cashbox_id' => $this->cashbox($central)->id,
            'payment_id' => $this->cash()->id,
            'payment_type' => 'cash',
        ];

        // 1) Kit
        $sales->register(array_merge($contado, ['note' => 'Combo regreso a clases (kit)']), [
            ['product_unit_id' => $this->unit('KIT-0001')->id, 'quantity' => 1],
        ], $this->admin());

        // 2) Contado con descuento
        $sales->register(array_merge($contado, ['customer_id' => $this->customer('6543210')->id, 'note' => 'Dos mochilas escolares con descuento']), [
            ['product_unit_id' => $this->unit('MOC-0001')->id, 'quantity' => 2, 'discount_type' => 'amount', 'discount_value' => 10],
        ], $this->admin());

        // 3) Crédito con anticipo
        // (array_merge y no "+": con "+" gana la clave de la izquierda y seguiría siendo 'cash')
        $sales->register(array_merge($contado, [
            'customer_id' => $this->customer('1023456029')->id,
            'note' => 'Venta a crédito a 15 días para el colegio',
            'payment_type' => 'credit',
            'due_date' => now()->addDays(15)->toDateString(),
            'initial_payment' => 100,
        ]), [
            ['product_unit_id' => $this->unit('LLA-0001', 'Paquete')->id, 'quantity' => 2],
            ['product_unit_id' => $this->unit('MOC-0002')->id, 'quantity' => 1],
        ], $this->admin());

        // 4) Norte vende del almacén que comparte con Central
        $norte = $this->branch(BranchSeeder::NORTE);
        $sales->register([
            'warehouse_id' => $this->warehouse()->id,
            'branche_id' => $norte->id,
            'cashbox_id' => $this->cashbox($norte)->id,
            'payment_id' => $this->cash()->id,
            'payment_type' => 'cash',
            'note' => 'Venta en sucursal Norte desde el Almacén Central',
        ], [
            ['product_unit_id' => $this->unit('MOC-0001')->id, 'quantity' => 1],
            ['product_unit_id' => $this->unit('BOL-0001')->id, 'quantity' => 2],
        ], $this->seller(UsuariosSeeder::VENDEDOR_NORTE));

        // 5) Plan 3000 vende de su propio depósito
        $plan3000 = $this->branch(BranchSeeder::PLAN_3000);
        $sales->register([
            'warehouse_id' => $this->warehouse('ALM-P3K')->id,
            'branche_id' => $plan3000->id,
            'cashbox_id' => $this->cashbox($plan3000)->id,
            'payment_id' => $this->cash()->id,
            'payment_type' => 'cash',
            'note' => 'Venta en sucursal Plan 3000',
        ], [
            ['product_unit_id' => $this->unit('BOL-0001')->id, 'quantity' => 3],
        ], $this->seller(UsuariosSeeder::VENDEDOR_PLAN_3000));

        // 6) Central cobra por QR
        $sales->register(array_merge($contado, [
            'customer_id' => $this->customer('6543210')->id,
            'payment_id' => $this->qr()->id,
            'note' => 'Venta cobrada por QR',
        ]), [
            ['product_unit_id' => $this->unit('EST-0001')->id, 'quantity' => 3],
            ['product_unit_id' => $this->unit('BOL-0001')->id, 'quantity' => 1],
        ], $this->admin());

        // 7) Norte cobra por QR (del Almacén Central)
        $sales->register([
            'warehouse_id' => $this->warehouse()->id,
            'branche_id' => $norte->id,
            'cashbox_id' => $this->cashbox($norte)->id,
            'payment_id' => $this->qr()->id,
            'payment_type' => 'cash',
            'note' => 'Venta en sucursal Norte cobrada por QR',
        ], [
            ['product_unit_id' => $this->unit('LON-0001')->id, 'quantity' => 2],
        ], $this->seller(UsuariosSeeder::VENDEDOR_NORTE));

        // 8) Plan 3000 cobra por QR (de su depósito)
        $sales->register([
            'warehouse_id' => $this->warehouse('ALM-P3K')->id,
            'branche_id' => $plan3000->id,
            'cashbox_id' => $this->cashbox($plan3000)->id,
            'payment_id' => $this->qr()->id,
            'payment_type' => 'cash',
            'note' => 'Venta en sucursal Plan 3000 cobrada por QR',
        ], [
            ['product_unit_id' => $this->unit('MOC-0001')->id, 'quantity' => 1],
            ['product_unit_id' => $this->unit('BOL-0001')->id, 'quantity' => 1],
        ], $this->seller(UsuariosSeeder::VENDEDOR_PLAN_3000));

        $this->command->info('✅ Ventas de ejemplo registradas (kit, descuento, crédito, Norte, Plan 3000 y 3 cobradas por QR).');
    }

    private function seller(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }
}
