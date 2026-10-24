<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    testBranch();
});

describe('registro de usuario (AuthController)', function () {
    it('muestra el formulario de registro', function () {
        $response = $this->get(route('auth.create'));

        $response->assertOk()
            ->assertViewIs('auth.create');
    });

    it('registra a un nuevo usuario e inicia sesión automáticamente', function () {
        $payload = [
            'name' => 'Juan Perez',
            'email' => 'juan.perez@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->post(route('auth.store'), $payload);

        $response->assertRedirect(route('dashboard'))
            ->assertSessionHas('success');

        $user = User::where('email', 'juan.perez@test.com')->first();
        expect($user)->not->toBeNull()
            ->and(Hash::check('password123', $user->password))->toBeTrue();

        $this->assertAuthenticatedAs($user);
    });

    it('falla si la confirmación de contraseña no coincide o es muy corta', function () {
        $response = $this->post(route('auth.store'), [
            'name' => 'Juan Perez',
            'email' => 'juan@test.com',
            'password' => 'pass123',
            'password_confirmation' => 'diferente',
        ]);

        $response->assertSessionHasErrors(['password']);
    });

    it('falla si el email ya existe', function () {
        User::factory()->create(['email' => 'repetido@test.com']);

        $response = $this->post(route('auth.store'), [
            'name' => 'Otro Juan',
            'email' => 'repetido@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors(['email']);
    });
});
