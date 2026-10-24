<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        // Un token CSRF vencido/no coincidente (419) por defecto muestra la página de error como
        // respuesta DIRECTA al POST que falló. Eso hace que el navegador reintente ese mismo POST
        // (con el mismo token viejo) cada vez que el usuario recarga, quedando en un loop de 419
        // sin salida. Redirigir en vez de renderizar la página corta ese loop: recargar ya solo
        // repite el GET de destino, no el POST.
        //
        // OJO: Laravel convierte TokenMismatchException en un HttpException genérico (con
        // getStatusCode() 419) antes de que esta callback se ejecute (ver prepareException() en
        // Illuminate\Foundation\Exceptions\Handler), así que hay que engancharse a ese tipo, no a
        // TokenMismatchException directamente (esa nunca llega hasta acá).
        $this->renderable(function (HttpException $e, Request $request) {
            if ($e->getStatusCode() !== 419) {
                return null;
            }

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Tu sesión expiró. Vuelve a intentarlo.'], 419);
            }

            $destino = $request->user() ? url()->previous() : route('login');

            return redirect($destino)->with('error', 'Tu sesión expiró por inactividad. Vuelve a intentarlo.');
        });
    }
}
