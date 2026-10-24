<?php

use App\Models\Branch;
use App\Models\Company;

/*
|--------------------------------------------------------------------------
| PWA: el nombre sale de APP_NAME (.env) y el ícono de public/images/logo-pymes.png
|--------------------------------------------------------------------------
| No dependen de la empresa ni de la base de datos, así que la app siempre se puede instalar.
*/

describe('manifest', function () {
    it('usa el nombre de la aplicación del .env (APP_NAME)', function () {
        config(['app.name' => 'Ferretería Sur SRL']);

        $response = $this->get(route('pwa.manifest'))->assertOk();
        $manifest = $response->json();

        expect($manifest['name'])->toBe('Ferretería Sur SRL')
            ->and($manifest['short_name'])->toBe('Ferretería S')
            ->and($manifest['start_url'])->toBe('/dashboard')
            ->and($manifest['display'])->toBe('standalone');
        expect($response->headers->get('content-type'))->toContain('application/manifest+json');
    });

    it('no toma el nombre de la empresa registrada', function () {
        config(['app.name' => 'Nombre del .env']);
        Company::factory()->create(['name' => 'Nombre de la empresa']);

        expect($this->get(route('pwa.manifest'))->json('name'))->toBe('Nombre del .env');
    });

    it('incluye siempre los íconos de logo-pymes.png (192, 512 y maskable)', function () {
        $icons = $this->get(route('pwa.manifest'))->assertOk()->json('icons');

        expect($icons)->toHaveCount(3)
            ->and($icons[0]['src'])->toStartWith('/pwa/icon/192.png')
            ->and($icons[1]['src'])->toStartWith('/pwa/icon/512.png')
            ->and($icons[2]['purpose'])->toBe('maskable');
    });

    it('los íconos son iguales aunque una sucursal tenga otro logo o no tenga', function () {
        $sin = $this->get(route('pwa.manifest'))->json('icons');

        Branch::factory()->create(['logo' => 'logos/otro-logo.png']);
        $con = $this->get(route('pwa.manifest'))->json('icons');

        expect($con)->toBe($sin);
    });
});

describe('ícono', function () {
    it('genera un PNG cuadrado del tamaño pedido a partir de logo-pymes.png', function (int $size) {
        $response = $this->get(route('pwa.icon', ['size' => $size]))->assertOk();
        [$width, $height] = getimagesizefromstring($response->getContent());

        expect($response->headers->get('content-type'))->toBe('image/png')
            ->and([$width, $height])->toBe([$size, $size]);
    })->with([192, 512, 180]);

    it('genera también la versión maskable', function () {
        $response = $this->get(route('pwa.icon', ['size' => 512, 'maskable' => 'maskable']))->assertOk();

        expect(getimagesizefromstring($response->getContent())[0])->toBe(512);
    });

    it('responde 404 para tamaños no permitidos', function () {
        $this->get(route('pwa.icon', ['size' => 999]))->assertNotFound();
    });

    it('la URL del ícono cambia cuando se reemplaza el logo configurado (para no mostrar uno viejo)', function () {
        $path = public_path(config('app.logo'));
        $original = filemtime($path);
        $antes = $this->get(route('pwa.manifest'))->json('icons.0.src');

        touch($path, $original + 100);
        clearstatcache();
        $despues = $this->get(route('pwa.manifest'))->json('icons.0.src');
        touch($path, $original);

        expect($despues)->not->toBe($antes);
    });
});

describe('layout', function () {
    it('enlaza el manifiesto y el ícono de iPhone, con o sin empresa', function () {
        $this->actingAs(userWith('ver customers'));

        $this->get(route('customers.index'))
            ->assertOk()
            ->assertSee(route('pwa.manifest', absolute: false), false)
            ->assertSee('rel="apple-touch-icon"', false)
            ->assertSee('data-pwa-install-button', false);
    });
});
