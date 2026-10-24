<?php

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Quotation;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Configuración global
|--------------------------------------------------------------------------
| Todas las pruebas de Feature usan la aplicación completa y una base de datos limpia por prueba
| (SQLite en memoria, ver phpunit.xml; tests/CreatesApplication.php frena cualquier otra conexión).
*/

uses(TestCase::class, RefreshDatabase::class)
    ->beforeEach(function () {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        if (config('database.default') === 'sqlite') {
            \Illuminate\Support\Facades\DB::connection()->getPdo()->sqliteCreateFunction('REGEXP', function ($pattern, $value) {
                if ($value === null) {
                    return 0;
                }
                return @preg_match('/' . str_replace('/', '\/', $pattern) . '/', $value) === 1 ? 1 : 0;
            }, 2);
        }

        \App\Models\User::creating(function ($user) {
            if (empty($user->branche_id)) {
                $user->branche_id = testBranch()->id;
            }
        });
    })
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Ayudantes
|--------------------------------------------------------------------------
*/

/**
 * Sucursal base que necesita casi todo (los usuarios, almacenes y órdenes pertenecen a una sucursal).
 */
function testBranch(): Branch
{
    return Branch::firstOrCreate(
        ['name' => 'Sucursal de prueba'],
        ['address' => 'Calle 1', 'phone' => '3-000000', 'is_active' => true]
    );
}

/**
 * Usuario con exactamente los permisos indicados (ej. "ver customers", "crear customers").
 */
function userWith(string ...$permissions): User
{
    $user = User::factory()->create(['branche_id' => testBranch()->id]);

    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user->givePermissionTo($permissions);

    return $user;
}

/**
 * Usuario con rol superadmin.
 */
function superAdminUser(): User
{
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
    $user = User::factory()->create(['branche_id' => testBranch()->id]);
    $user->assignRole('superadmin');
    return $user;
}

/**
 * Orden (venta) de un cliente, mínima pero válida.
 *
 * @param  array<string, mixed>  $overrides
 */
function orderFor(Customer $customer, array $overrides = []): Order
{
    $branch = testBranch();

    $warehouse = Warehouse::firstOrCreate(
        ['code' => 'ALM-TEST'],
        ['name' => 'Almacén de prueba', 'is_active' => true, 'branche_id' => $branch->id]
    );

    return Order::create($overrides + [
        'user_id' => User::query()->value('id') ?? User::factory()->create(['branche_id' => $branch->id])->id,
        'customer_id' => $customer->id,
        'warehouse_id' => $warehouse->id,
        'branche_id' => $branch->id,
        'date' => now()->toDateString(),
        'amount' => 100,
        'paid_amount' => 0,
        'payment_type' => Order::PAYMENT_CASH,
        'status' => Order::STATUS_COMPLETED,
    ]);
}

/**
 * Proforma de un cliente, mínima pero válida.
 */
function quotationFor(Customer $customer, string $number, array $overrides = []): Quotation
{
    $quotation = new Quotation();
    $quotation->forceFill($overrides + [
        'user_id' => User::query()->value('id') ?? User::factory()->create(['branche_id' => testBranch()->id])->id,
        'customer_id' => $customer->id,
        'number' => $number,
        'date' => now()->toDateString(),
        'status' => 'pending',
        'amount' => 50,
    ])->save();

    return $quotation;
}
