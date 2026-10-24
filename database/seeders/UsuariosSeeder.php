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
        $sucursal = Branch::where('name', BranchSeeder::CENTRAL)->first() ?? Branch::first();

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
                'email' => 'regente@farmaciasantarosa.com',
                'datos' => ['name' => 'Dra. Carla Vaca (Regente)', 'password' => Hash::make('12345678'), 'branche_id' => $sucursal->id],
                'role' => 'admin',
            ],
            [
                'email' => 'administracion@farmaciasantarosa.com',
                'datos' => ['name' => 'Luis Justiniano (Administración)', 'password' => Hash::make('12345678'), 'branche_id' => $sucursal->id],
                'role' => 'admin',
            ],
            [
                'email' => 'operador@farmaciasantarosa.com',
                'datos' => ['name' => 'Auxiliar de Farmacia', 'password' => Hash::make('12345678'), 'branche_id' => $sucursal->id],
                'role' => 'operador',
            ],
        ];

        foreach ($usuarios as $u) {
            $user = User::updateOrCreate(['email' => $u['email']], $u['datos']);
            $user->syncRoles([$u['role']]);
        }

        $this->command->info('✅ Usuarios de la farmacia creados y roles asignados correctamente.');
    }
}
