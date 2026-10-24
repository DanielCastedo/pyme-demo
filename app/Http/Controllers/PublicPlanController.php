<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSubscriptionRequestRequest;
use App\Models\Company;
use App\Models\SubscriptionPlan;
use App\Models\SubscriptionRequest;

class PublicPlanController extends Controller
{
    public function index()
    {
        // Si ya está logueado, lo mandamos a la vista interna de suscripción,
        // no tiene sentido mostrarle la landing pública genérica.
        if (auth()->check()) {
            return redirect()->route('subscriptions.plans');
        }

        $plans = SubscriptionPlan::where('active', true)->orderBy('price')->get();
        $company = Company::find(1);

        return view('public.plans', compact('plans', 'company'));
    }

    public function store(StoreSubscriptionRequestRequest $request)
    {
        SubscriptionRequest::create($request->validated());

        return back()->with('success', '¡Solicitud enviada! Nos pondremos en contacto contigo muy pronto.');
    }
}
