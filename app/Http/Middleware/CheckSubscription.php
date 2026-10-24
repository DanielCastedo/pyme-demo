<?php

namespace App\Http\Middleware;

use App\Models\Company;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscription
{
    public function handle(Request $request, Closure $next): Response
    {
        $company = Company::find(1);

        // si aún no hay empresa configurada, deja pasar (evita romper el setup inicial)
        if (! $company) {
            return $next($request);
        }

        // marca vencidas las suscripciones activas que ya pasaron su fecha
        $subscription = $company->activeSubscription();

        if ($subscription && $subscription->status !== 'trial'
            && $subscription->ends_at && $subscription->ends_at->isPast()) {
            $subscription->update(['status' => 'expired']);
        }

        if (! $company->hasValidSubscription()) {
            return redirect()->route('subscriptions.expired');
        }

        return $next($request);
    }
}
