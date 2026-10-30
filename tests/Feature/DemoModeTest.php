<?php

use App\Models\User;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| Modo demostración (DEMO_MODE, ver config/demo.php)
|--------------------------------------------------------------------------
| El login muestra accesos directos y un botón que restablece la base (migrate:fresh --seed).
| Apagado, no aparece nada y la ruta no existe para nadie.
*/

describe('modo demostración apagado', function () {
    beforeEach(fn () => config(['demo.enabled' => false]));

    it('no muestra los accesos directos ni el botón de restablecer', function () {
        $this->get(route('login'))->assertOk()
            ->assertDontSee('Usuarios de prueba')
            ->assertDontSee('operador@mochilassantarosa.com')
            ->assertDontSee(route('demo.reset'));
    });

    it('no restablece la base', function () {
        Artisan::spy();

        $this->post(route('demo.reset'))->assertNotFound();

        Artisan::shouldNotHaveReceived('call');
    });
});

describe('modo demostración encendido', function () {
    beforeEach(fn () => config(['demo.enabled' => true]));

    it('muestra un acceso directo por cada usuario de prueba y el botón de restablecer', function () {
        $response = $this->get(route('login'))->assertOk()->assertSee('Usuarios de prueba');

        foreach (config('demo.users') as $demoUser) {
            $response->assertSee($demoUser['email']);
        }

        $response->assertSee(route('demo.reset'));
    });

    it('restablece la base con migrate:fresh --seed y vuelve al login con un aviso', function () {
        Artisan::spy();

        $this->post(route('demo.reset'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('status');

        Artisan::shouldHaveReceived('call')->with('migrate:fresh', ['--seed' => true, '--force' => true])->once();
    });

    it('muestra el error en el login si el restablecimiento falla', function () {
        Artisan::shouldReceive('call')->andThrow(new RuntimeException('sin conexión'));

        $this->post(route('demo.reset'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('demo');
    });

    it('no deja restablecer a un usuario con sesión iniciada', function () {
        Artisan::spy();

        $this->actingAs(User::factory()->create())->post(route('demo.reset'))->assertRedirect();

        Artisan::shouldNotHaveReceived('call');
    });
});
