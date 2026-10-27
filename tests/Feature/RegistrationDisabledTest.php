<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Sin registro público: los usuarios los crea un administrador desde Seguridad > Usuarios
|--------------------------------------------------------------------------
*/

it('no expone ninguna ruta de registro', function () {
    expect(Route::has('register'))->toBeFalse()
        ->and(Route::has('auth.create'))->toBeFalse()
        ->and(Route::has('auth.store'))->toBeFalse();
});

it('no crea usuarios desde un formulario público', function () {
    $this->post('/register', [
        'name' => 'Intruso',
        'email' => 'intruso@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $this->post('/registrar', [
        'name' => 'Intruso',
        'email' => 'intruso2@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    expect(User::whereIn('email', ['intruso@example.com', 'intruso2@example.com'])->exists())->toBeFalse();
    $this->assertGuest();
});
