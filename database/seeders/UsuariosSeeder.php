<?php

// database/seeders/UsuariosSeeder.php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsuariosSeeder extends Seeder
{
    // Los usan los seeders de ejemplo y deben coincidir con los accesos directos de config/demo.php.
    public const ADMINISTRACION = 'administracion@mochilassantarosa.com';

    public const VENDEDOR_NORTE = 'operador@mochilassantarosa.com';

    public const VENDEDOR_PLAN_3000 = 'plan3000@mochilassantarosa.com';

    public function run(): void
    {
        $branches = Branch::whereIn('name', [BranchSeeder::CENTRAL, BranchSeeder::NORTE, BranchSeeder::PLAN_3000])->pluck('id', 'name');

        if ($branches->count() < 3) {
            $this->command->warn('Faltan sucursales. Corre primero el BranchSeeder.');

            return;
        }

        $usuarios = [
            ['admin@gmail.com', 'admin', '123', BranchSeeder::CENTRAL, 'superadmin'],
            ['encargado@mochilassantarosa.com', 'Jorge Suárez (Encargado)', '12345678', BranchSeeder::CENTRAL, 'admin'],
            [self::ADMINISTRACION, 'Luis Justiniano (Administración)', '12345678', BranchSeeder::CENTRAL, 'admin'],
            // Vende en la sucursal Norte, que saca la mercadería del Almacén Central (compartido).
            [self::VENDEDOR_NORTE, 'Vendedor Norte', '12345678', BranchSeeder::NORTE, 'operador'],
            [self::VENDEDOR_PLAN_3000, 'Vendedor Plan 3000', '12345678', BranchSeeder::PLAN_3000, 'operador'],
        ];

        foreach ($usuarios as [$email, $name, $password, $branch, $role]) {
            $user = User::updateOrCreate(['email' => $email], [
                'name' => $name,
                'password' => Hash::make($password),
                'branche_id' => $branches[$branch],
            ]);
            $user->syncRoles([$role]);
        }

        $this->command->info('✅ Usuarios de la tienda creados y roles asignados correctamente.');
    }
}
