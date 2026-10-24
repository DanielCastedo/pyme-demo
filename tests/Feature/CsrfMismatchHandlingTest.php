<?php

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;

it('convierte un CSRF vencido en una redirección (no en la página 419 directa) para un visitante', function () {
    $handler = app(ExceptionHandler::class);
    $request = Request::create('/logout', 'POST', server: ['HTTP_ACCEPT' => 'text/html']);
    $request->setLaravelSession(app('session.store'));

    $response = $handler->render($request, new TokenMismatchException());

    expect($response->getStatusCode())->toBe(302)
        ->and($response->headers->get('Location'))->toBe(route('login'));
});

it('a un usuario logueado lo redirige de vuelta (no al login) cuando vence el CSRF', function () {
    $user = userWith();

    $handler = app(ExceptionHandler::class);
    $request = Request::create('/sales', 'POST', server: ['HTTP_ACCEPT' => 'text/html']);
    $request->setLaravelSession(app('session.store'));
    $request->setUserResolver(fn () => $user);

    $response = $handler->render($request, new TokenMismatchException());

    expect($response->getStatusCode())->toBe(302)
        ->and($response->headers->get('Location'))->not->toBe(route('login'));
});

it('a una petición que espera JSON le contesta 419 en JSON, no un redirect', function () {
    $handler = app(ExceptionHandler::class);
    $request = Request::create('/logout', 'POST', server: ['HTTP_ACCEPT' => 'application/json']);
    $request->setLaravelSession(app('session.store'));

    $response = $handler->render($request, new TokenMismatchException());

    expect($response->getStatusCode())->toBe(419);
});
