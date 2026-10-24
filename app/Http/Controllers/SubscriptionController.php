<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\SubscriptionRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SubscriptionController extends Controller
{
    public function index()
    {
        $company = Company::findOrFail(1);
        $subscription = $company->subscriptions()->latest('id')->first();
        $plans = SubscriptionPlan::where('active', true)->get();

        return view('subscriptions.index', compact('company', 'subscription', 'plans'));
    }

    public function plans()
    {
        $plans = SubscriptionPlan::where('active', true)->get();
        $defaultReference = 'REF-'.now()->format('Ymd').'-'.Str::upper(Str::random(4));

        return view('subscriptions.plans', compact('plans', 'defaultReference'));
    }

    /**
     * El usuario ENVÍA su solicitud de suscripción — queda pendiente de aprobación.
     */
    public function subscribe(Request $request)
    {
        $request->validate([
            'subscription_plan_id' => 'required|exists:subscription_plans,id',
            'method' => 'required|in:qr,efectivo',
            'reference' => 'required|string|max:255',
        ]);

        $company = Company::findOrFail(1);
        $plan = SubscriptionPlan::findOrFail($request->subscription_plan_id);

        DB::transaction(function () use ($company, $plan, $request) {
            // cancela cualquier solicitud pendiente/activa previa para no acumular
            $company->subscriptions()
                ->whereIn('status', ['pending', 'active', 'trial', 'past_due'])
                ->update(['status' => 'canceled', 'canceled_at' => now()]);

            $subscription = Subscription::create([
                'company_id' => $company->id,
                'subscription_plan_id' => $plan->id,
                'starts_at' => now(),
                'ends_at' => null, // se define recién al aprobar
                'status' => 'pending',
                'auto_renew' => true,
            ]);

            Payment::create([
                'subscription_id' => $subscription->id,
                'company_id' => $company->id,
                'amount' => $plan->price,
                'currency' => $plan->currency,
                'method' => $request->method,
                'reference' => $request->reference,
                'status' => 'pending',
            ]);
        });

        return redirect()->route('subscriptions.index')
            ->with('success', 'Tu solicitud fue enviada. Un administrador revisará tu pago y activará tu plan pronto.');
    }

    public function cancel()
    {
        $company = Company::findOrFail(1);

        $subscription = $company->subscriptions()
            ->whereIn('status', ['pending', 'active', 'trial', 'past_due'])
            ->latest('id')
            ->first();

        if ($subscription) {
            $subscription->update(['status' => 'canceled', 'auto_renew' => false, 'canceled_at' => now()]);
        }

        return redirect()->route('subscriptions.index')->with('success', 'Suscripción cancelada.');
    }

    public function invoices()
    {
        $invoices = Invoice::where('company_id', 1)->with('items')->latest('issue_date')->paginate(10);

        return view('subscriptions.invoices', compact('invoices'));
    }

    public function expired()
    {
        $company = Company::find(1);
        $subscription = $company?->subscriptions()->latest('id')->first();

        return view('subscriptions.expired', compact('subscription'));
    }

    /**
     * ── ZONA ADMIN ──────────────────────────────────────────────────────
     */
    public function pending()
    {
        $requests = Subscription::where('status', 'pending')
            ->with(['company', 'plan', 'payments' => fn ($q) => $q->latest()])
            ->latest('created_at')
            ->paginate(10);

        $publicLeads = SubscriptionRequest::where('status', 'pendiente')
            ->with('plan')
            ->latest()
            ->get();

        return view('subscriptions.pending', compact('requests', 'publicLeads'));
    }

    public function convertLead(Request $request, SubscriptionRequest $subscriptionRequest)
    {
        $request->validate([
            'subscription_plan_id' => 'required|exists:subscription_plans,id',
        ]);

        $company = Company::findOrFail(1);
        $plan = SubscriptionPlan::findOrFail($request->subscription_plan_id);

        DB::transaction(function () use ($company, $plan, $subscriptionRequest) {
            $company->subscriptions()
                ->whereIn('status', ['active', 'trial', 'past_due'])
                ->update(['status' => 'canceled', 'canceled_at' => now()]);

            $endsAt = match ($plan->billing_cycle) {
                'monthly' => now()->addMonth(),
                'quarterly' => now()->addMonths(3),
                'yearly' => now()->addYear(),
                default => now()->addMonth(),
            };

            $subscription = Subscription::create([
                'company_id' => $company->id,
                'subscription_plan_id' => $plan->id,
                'starts_at' => now(),
                'ends_at' => $endsAt,
                'status' => 'active',
                'auto_renew' => true,
            ]);

            $payment = Payment::create([
                'subscription_id' => $subscription->id,
                'company_id' => $company->id,
                'amount' => $plan->price,
                'currency' => $plan->currency,
                'method' => 'otro',
                'reference' => 'Lead público #'.$subscriptionRequest->id,
                'status' => 'completed',
                'paid_at' => now(),
            ]);

            $invoice = Invoice::create([
                'company_id' => $company->id,
                'subscription_id' => $subscription->id,
                'payment_id' => $payment->id,
                'invoice_number' => 'FAC-'.now()->format('Ymd').'-'.Str::upper(Str::random(5)),
                'issue_date' => now(),
                'due_date' => now(),
                'currency' => $plan->currency,
                'status' => 'paid',
            ]);

            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'description' => "Suscripción plan {$plan->name} ({$plan->billing_cycle}) — activada desde solicitud pública",
                'quantity' => 1,
                'unit_price' => $plan->price,
                'subtotal' => $plan->price,
            ]);

            $invoice->recalculateTotals();

            $subscriptionRequest->update(['status' => 'convertido']);
        });

        return back()->with('success', "Suscripción activada con el plan {$plan->name}.");
    }

    public function updateLeadStatus(Request $request, SubscriptionRequest $subscriptionRequest)
    {
        $request->validate(['status' => 'required|in:pendiente,contactado,convertido,descartado']);
        $subscriptionRequest->update(['status' => $request->status]);

        return back()->with('success', 'Solicitud actualizada.');
    }

    public function destroyLead(SubscriptionRequest $subscriptionRequest)
    {
        $subscriptionRequest->delete();

        return back()->with('success', 'Solicitud eliminada.');
    }

    public function approve(Subscription $subscription)
    {
        if ($subscription->status !== 'pending') {
            return back()->with('error', 'Esta solicitud ya fue procesada.');
        }

        DB::transaction(function () use ($subscription) {
            $plan = $subscription->plan;

            $endsAt = match ($plan->billing_cycle) {
                'monthly' => now()->addMonth(),
                'quarterly' => now()->addMonths(3),
                'yearly' => now()->addYear(),
                default => now()->addMonth(),
            };

            $subscription->update([
                'status' => 'active',
                'starts_at' => now(),
                'ends_at' => $endsAt,
            ]);

            $payment = $subscription->payments()->latest()->first();
            $payment?->update(['status' => 'completed', 'paid_at' => now()]);

            $invoice = Invoice::create([
                'company_id' => $subscription->company_id,
                'subscription_id' => $subscription->id,
                'payment_id' => $payment?->id,
                'invoice_number' => 'FAC-'.now()->format('Ymd').'-'.Str::upper(Str::random(5)),
                'issue_date' => now(),
                'due_date' => now(),
                'currency' => $plan->currency,
                'status' => 'paid',
            ]);

            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'description' => "Suscripción plan {$plan->name} ({$plan->billing_cycle})",
                'quantity' => 1,
                'unit_price' => $plan->price,
                'subtotal' => $plan->price,
            ]);

            $invoice->recalculateTotals();
        });

        return back()->with('success', 'Suscripción aprobada y activada.');
    }

    public function reject(Request $request, Subscription $subscription)
    {
        if ($subscription->status !== 'pending') {
            return back()->with('error', 'Esta solicitud ya fue procesada.');
        }

        $subscription->update(['status' => 'canceled', 'canceled_at' => now()]);
        $subscription->payments()->latest()->first()?->update(['status' => 'failed']);

        return back()->with('success', 'Solicitud rechazada.');
    }
}
