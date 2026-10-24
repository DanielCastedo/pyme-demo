<?php

// database/seeders/UsuariosSeeder.php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsuariosSeeder extends Seeder
{
    public function run(): void
    {
        $sucursal = Branch::where('name', 'Sucursal Brazil Importaciones')->first() ?? Branch::first();

        if (! $sucursal) {
            $this->command->warn('No hay sucursales. Corre primero el BranchSeeder.');

            return;
        }

        $usuarios = [
            [
                'email' => 'admin@gmail.com',
                'datos' => ['name' => 'admin', 'password' => Hash::make('123'), 'branche_id' => $sucursal->id],
                'role' => 'superadmin',
            ],
            [
                'email' => 'nora@importdcb.com',
                'datos' => ['name' => 'Nora', 'password' => Hash::make('12345678'), 'branche_id' => $sucursal->id],
                'role' => 'admin',
            ],
            [
                'email' => 'gabriel@importdcb.com',
                'datos' => ['name' => 'Gabriel', 'password' => Hash::make('12345678'), 'branche_id' => $sucursal->id],
                'role' => 'admin',
            ],
            [
                'email' => 'hernesto@importdcb.com',
                'datos' => ['name' => 'Hernesto', 'password' => Hash::make('12345678'), 'branche_id' => $sucursal->id],
                'role' => 'admin',
            ],
            [
                'email' => 'operador@importdcb.com',
                'datos' => ['name' => 'Operador de Ventas', 'password' => Hash::make('12345678'), 'branche_id' => $sucursal->id],
                'role' => 'operador',
            ],
        ];

        foreach ($usuarios as $u) {
            $user = User::updateOrCreate(['email' => $u['email']], $u['datos']);
            $user->syncRoles([$u['role']]);
        }

        $this->command->info('✅ Usuarios de la ferretería creados y roles asignados correctamente.');
    }
}
