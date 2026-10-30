<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Modo demostración
    |--------------------------------------------------------------------------
    |
    | Con DEMO_MODE=true el login muestra accesos directos con usuarios de prueba y un botón que
    | restablece la base de datos (migrate:fresh --seed). NUNCA activarlo en un sistema con datos reales:
    | ese botón está en una página pública y borra todo.
    |
    */

    'enabled' => (bool) env('DEMO_MODE', false),

    /*
    | Usuarios de los accesos directos. Deben coincidir con los que crea database/seeders/UsuariosSeeder.php.
    */
    'users' => [
        [
            'label' => 'Administración',
            'role' => 'Administrador',
            'email' => 'administracion@mochilassantarosa.com',
            'password' => '12345678',
        ],
        [
            'label' => 'Vendedor Norte',
            'role' => 'Operador · usa el Almacén Central',
            'email' => 'operador@mochilassantarosa.com',
            'password' => '12345678',
        ],
        [
            'label' => 'Vendedor Plan 3000',
            'role' => 'Operador · almacén propio',
            'email' => 'plan3000@mochilassantarosa.com',
            'password' => '12345678',
        ],
    ],

];
