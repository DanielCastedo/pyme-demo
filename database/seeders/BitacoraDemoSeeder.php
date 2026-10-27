<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Expense;
use App\Models\Order;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Activitylog\Models\Activity;

class BitacoraDemoSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->first() ?? User::first();
        $operador = User::where('email', 'operador@ferreteriasantarosa.com')->first() ?? $admin;
        $nora = Customer::where('name', 'like', '%Nora%')->first();
        $producto = Product::first();
        $gasto = Expense::first();
        $compra = Purchase::first();

        if (! $admin) {
            return;
        }

        // 1. Inicios de sesión y accesos
        activity('autenticacion')
            ->causedBy($admin)
            ->event('login')
            ->withProperties(['ip' => '192.168.1.10', 'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/128.0'])
            ->createdAt(now()->subHours(6))
            ->log("Inicio de sesión exitoso del usuario {$admin->name}");

        activity('autenticacion')
            ->event('failed')
            ->withProperties(['ip' => '181.115.12.45', 'email' => 'intruso@desconocido.com'])
            ->createdAt(now()->subHours(5))
            ->log("Intento fallido de inicio de sesión con correo intruso@desconocido.com");

        if ($operador) {
            activity('autenticacion')
                ->causedBy($operador)
                ->event('login')
                ->withProperties(['ip' => '192.168.1.15', 'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Edge/128.0'])
                ->createdAt(now()->subHours(4))
                ->log("Inicio de sesión exitoso del operador {$operador->name}");
        }

        // 2. Registro del cliente Nora
        if ($nora) {
            activity('clientes')
                ->causedBy($admin)
                ->performedOn($nora)
                ->event('created')
                ->withProperties([
                    'attributes' => [
                        'name' => $nora->name,
                        'document' => $nora->document,
                        'phone' => $nora->phone,
                        'email' => $nora->email,
                    ],
                ])
                ->createdAt(now()->subDays(2))
                ->log("Se registró al cliente {$nora->name} (Doc: {$nora->document})");

            activity('ventas')
                ->causedBy($operador ?? $admin)
                ->event('created')
                ->withProperties([
                    'attributes' => [
                        'customer' => $nora->name,
                        'total' => 450.00,
                        'payment' => 'Efectivo',
                    ],
                ])
                ->createdAt(now()->subHours(3))
                ->log("Se registró la venta #10025 al cliente {$nora->name} por Bs. 450.00");
        }

        // 3. Productos y Catálogos
        if ($producto) {
            activity('productos')
                ->causedBy($admin)
                ->performedOn($producto)
                ->event('updated')
                ->withProperties([
                    'old' => ['price_sale' => 120.00],
                    'attributes' => ['price_sale' => $producto->price_sale ?? 135.00],
                ])
                ->createdAt(now()->subDays(1))
                ->log("Se actualizó el precio de venta del producto {$producto->name}");
        }

        // 4. Compras y Gastos
        if ($compra) {
            activity('compras')
                ->causedBy($admin)
                ->performedOn($compra)
                ->event('created')
                ->withProperties([
                    'attributes' => [
                        'invoice' => $compra->invoice_number,
                        'amount' => $compra->amount,
                    ],
                ])
                ->createdAt(now()->subDays(3))
                ->log("Se registró la compra #{$compra->id} con factura {$compra->invoice_number}");
        }

        if ($gasto) {
            activity('gastos')
                ->causedBy($admin)
                ->performedOn($gasto)
                ->event('created')
                ->withProperties([
                    'attributes' => [
                        'concept' => $gasto->concept,
                        'amount' => $gasto->amount,
                    ],
                ])
                ->createdAt(now()->subDays(2))
                ->log("Se registró el gasto '{$gasto->concept}' por Bs. " . number_format((float) $gasto->amount, 2));
        }

        $this->command->info('✅ Actividades de bitácora de ejemplo registradas correctamente.');
    }
}
