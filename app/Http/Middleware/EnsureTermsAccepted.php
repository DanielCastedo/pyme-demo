<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTermsAccepted
{
    /**
     * Rutas exentas de la intercepción de términos y condiciones.
     */
    protected array $except = [
        'terms.pending',
        'terms.accept',
        'terms.reject',
        'logout',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->mustAcceptTerms()) {
            $routeName = $request->route()?->getName();

            if (!in_array($routeName, $this->except, true)) {
                return redirect()->route('terms.pending');
            }
        }

        return $next($request);
    }
}
