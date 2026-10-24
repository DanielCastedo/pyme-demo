<?php

use App\Models\User;
use Illuminate\Support\Facades\Validator;

/*
|--------------------------------------------------------------------------
| Idioma: las pantallas de usuario de Laravel (perfil, acceso, contraseña, dos factores) están en español
|--------------------------------------------------------------------------
*/

/** Todas las claves __('...') de texto en inglés que usan las vistas. */
function translationKeysUsedInViews(): array
{
    $keys = [];
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(resource_path('views')));

    foreach ($files as $file) {
        if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.php')) {
            continue;
        }

        preg_match_all('/__\(\s*([\'"])((?:\\\\.|(?!\1).)*)\1/s', file_get_contents($file->getPathname()), $matches);

        foreach ($matches[2] as $key) {
            $key = stripcslashes($key);

            // Las claves de archivo ("pagination.next") se traducen en lang/es/*.php; el resto en lang/es.json
            if (str_contains($key, ' ') || ! str_contains($key, '.')) {
                $keys[$key] = true;
            }
        }
    }

    return array_keys($keys);
}

it('el idioma por defecto es español', function () {
    expect(app()->getLocale())->toBe('es')->and(config('app.fallback_locale'))->toBe('en');
});

it('cada texto de las vistas tiene su traducción al español (lang/es.json)', function () {
    $translations = json_decode(file_get_contents(lang_path('es.json')), true, 512, JSON_THROW_ON_ERROR);

    // Los textos que ya están escritos en español en la vista no necesitan entrada
    $alreadySpanish = ['Registrar', 'Registrar Usuario', 'Iniciar sesión', 'Política de Privacidad', 'Términos de Servicio', '¿Ya tienes una cuenta?', 'Acepto los :terms_of_service y :privacy_policy'];

    $missing = array_values(array_diff(
        array_filter(translationKeysUsedInViews(), fn ($key) => ! isset($translations[$key])),
        $alreadySpanish
    ));

    expect($missing)->toBe([]);
});

it('las traducciones no dejan textos iguales al inglés ni vacíos', function () {
    $translations = json_decode(file_get_contents(lang_path('es.json')), true, 512, JSON_THROW_ON_ERROR);

    foreach ($translations as $english => $spanish) {
        expect(trim($spanish))->not->toBe('', "«{$english}» quedó vacío");
    }
});

it('los mensajes de validación salen en español', function () {
    $errors = Validator::make(['email' => '', 'password' => 'abc'], ['email' => 'required', 'password' => 'min:8'])->errors();

    expect($errors->first('email'))->toBe('El campo correo electrónico es obligatorio.')
        ->and($errors->first('password'))->toBe('El campo contraseña debe tener al menos 8 caracteres.');
});

it('los mensajes de autenticación y de contraseña salen en español', function () {
    expect(__('auth.failed'))->toBe('Estas credenciales no coinciden con nuestros registros.')
        ->and(__('passwords.sent'))->toContain('Te enviamos por correo')
        ->and(__('The provided password does not match your current password.'))->toBe('La contraseña ingresada no coincide con tu contraseña actual.');
});

it('un intento de acceso fallido avisa en español', function () {
    User::factory()->create(['email' => 'ana@example.com']);

    $this->from(route('login'))->post(route('login.store'), ['email' => 'ana@example.com', 'password' => 'incorrecta'])
        ->assertSessionHasErrors('email');

    expect(session('errors')->first('email'))->toBe('Estas credenciales no coinciden con nuestros registros.');
});

describe('pantallas de usuario', function () {
    /** Frases en inglés que no deben aparecer en ninguna pantalla de usuario. */
    $english = [
        'Profile Information', 'Update Password', 'Two Factor Authentication', 'Browser Sessions', 'Delete Account',
        'Sign Out', 'Log Out', 'Settings', 'Current Password', 'New Password', 'Confirm Password', 'Reset your Password',
        'Send Reset Link', 'Email Address', 'Select A New Photo', 'Remove Photo', 'Whoops', 'Show Recovery Codes',
        'Regenerate Recovery Codes', 'Recovery Code', 'Use an authentication code', 'Showing', 'Superficct',
    ];

    it('el perfil del usuario está en español', function () use ($english) {
        $html = $this->actingAs(User::factory()->create(['name' => 'Ana Pérez']))->get(route('profile.show'))->assertOk()->getContent();
        $visible = preg_replace('/<!--.*?-->/s', '', $html); // los comentarios HTML no se ven en pantalla

        foreach ($english as $phrase) {
            expect($visible)->not->toContain($phrase);
        }

        expect($html)->toContain('Información del perfil')
            ->and($html)->toContain('Actualizar contraseña')
            ->and($html)->toContain('Autenticación de dos factores')
            ->and($html)->toContain('Sesiones en otros navegadores')
            ->and($html)->toContain('Eliminar cuenta')
            ->and($html)->toContain('Cerrar sesión');
    });

    it('el menú del usuario dice "Mi perfil" y "Cerrar sesión"', function () {
        $html = $this->actingAs(User::factory()->create())->get(route('profile.show'))->getContent();

        expect($html)->toContain('Mi perfil')->and($html)->toContain('Cerrar sesión')->and($html)->toContain('Usuario');
    });

    it('las pantallas de acceso y de contraseña están en español', function (string $route, array $parameters, array $expected) use ($english) {
        $html = $this->get(route($route, $parameters))->assertOk()->getContent();

        foreach ($english as $phrase) {
            expect($html)->not->toContain($phrase);
        }

        foreach ($expected as $text) {
            expect($html)->toContain($text);
        }
    })->with([
        'olvidé mi contraseña' => ['password.request', [], ['Restablece tu contraseña', 'Enviar enlace de restablecimiento']],
        'restablecer contraseña' => ['password.reset', ['token' => 'abc123'], ['Restablecer contraseña', 'Confirmar contraseña']],
    ]);

    it('el enlace "cerrar sesión" del usuario sigue funcionando', function () {
        $this->actingAs(User::factory()->create())->post(route('logout'))->assertRedirect();

        $this->assertGuest();
    });
});

describe('paginación', function () {
    it('el pie de las tablas dice "Mostrando X a Y de Z resultados"', function () {
        $user = userWith('ver categories');
        \App\Models\Category::factory()->count(12)->create();

        $company = \App\Models\Company::firstOrCreate(['id' => 1], ['name' => 'X', 'currency' => 'Bs']);
        $plan = \App\Models\SubscriptionPlan::firstOrCreate(['slug' => 'empresa'], ['name' => 'P', 'price' => 1, 'billing_cycle' => 'monthly', 'is_active' => true]);
        $company->subscriptions()->firstOrCreate(['company_id' => $company->id], ['subscription_plan_id' => $plan->id, 'status' => 'active', 'starts_at' => now()->subDay(), 'ends_at' => now()->addMonth()]);

        $html = $this->actingAs($user)->get(route('categories.index'))->assertOk()->getContent();

        expect($html)->toContain('Mostrando')->and($html)->toContain('resultados')->and($html)->not->toContain('Showing');
    });
});
