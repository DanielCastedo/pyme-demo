<x-app-layout>
    <div class="max-w-10xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        @if (session('success'))
            <x-ui.alert type="success" :message="session('success')" />
        @endif

        <x-ui.page-header category="Suscripciones" title="Solicitudes Pendientes"
            description="Aprueba o rechaza las solicitudes de suscripción con pago pendiente de verificación." />

        @forelse ($requests as $subscription)
            @php $payment = $subscription->payments->first(); @endphp
            <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-gray-700 rounded-xl p-5 mb-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <span class="text-[10px] font-bold tracking-wider text-amber-600 uppercase">Pendiente</span>
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">
                        {{ $subscription->company->name }} — Plan {{ $subscription->plan->name }}
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Método: <strong>{{ ucfirst($payment?->method) }}</strong> ·
                        Referencia: <strong>{{ $payment?->reference }}</strong> ·
                        Monto: {{ number_format($subscription->plan->price, 2) }} {{ $subscription->plan->currency }}
                    </p>
                    <p class="text-[10px] text-gray-400 mt-1">Solicitado: {{ $subscription->created_at->format('d/m/Y H:i') }}</p>
                </div>

                <div class="flex gap-2 shrink-0">
                    <form action="{{ route('subscriptions.approve', $subscription) }}" method="POST"
                        onsubmit="return confirm('¿Aprobar esta suscripción?')">
                        @csrf
                        <x-ui.button type="submit" variant="success">Aprobar</x-ui.button>
                    </form>
                    <form action="{{ route('subscriptions.reject', $subscription) }}" method="POST"
                        onsubmit="return confirm('¿Rechazar esta solicitud?')">
                        @csrf
                        <x-ui.button type="submit" variant="danger">Rechazar</x-ui.button>
                    </form>
                </div>
            </div>
        @empty
            <div class="text-center py-12 text-sm text-gray-500 dark:text-gray-400">
                No hay solicitudes pendientes por revisar.
            </div>
        @endforelse

        {{ $requests->links() }}

         @if ($publicLeads->isNotEmpty())
        <div class="mt-10">
            <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-3">Solicitudes desde la página pública ({{ $publicLeads->count() }})</span>

            @foreach ($publicLeads as $lead)
                <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-gray-700 rounded-xl p-5 mb-4">
                    <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3">
                        <div>
                            <span class="text-[10px] font-bold tracking-wider text-gray-400 uppercase">{{ $lead->created_at_label }}</span>
                            <h3 class="text-base font-bold text-gray-900 dark:text-white">{{ $lead->name }}</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                {{ $lead->business_name ?? 'Sin nombre de negocio' }} ·
                                <a href="https://wa.me/{{ preg_replace('/\D/', '', $lead->phone) }}" target="_blank" class="text-emerald-600 hover:underline">{{ $lead->phone }}</a>
                            </p>
                            @if ($lead->plan)
                                <p class="text-xs text-gray-600 dark:text-gray-300 mt-1">Interesado en: <strong>{{ $lead->plan->name }}</strong></p>
                            @endif
                            @if ($lead->message)
                                <p class="text-sm text-gray-700 dark:text-gray-300 mt-2 italic">"{{ $lead->message }}"</p>
                            @endif
                        </div>

                        <div class="flex items-center gap-2 shrink-0">
                            <form action="{{ route('subscription-leads.convert', $lead) }}" method="POST" class="flex items-center gap-2"
                                onsubmit="return confirm('¿Activar la suscripción del negocio con este plan?')">
                                @csrf
                                <select name="subscription_plan_id" required
                                    class="px-2 h-9 text-xs rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white">
                                    <option value="">Elegir plan...</option>
                                    @foreach (\App\Models\SubscriptionPlan::where('active', true)->get() as $planOption)
                                        <option value="{{ $planOption->id }}" @selected($lead->subscription_plan_id == $planOption->id)>{{ $planOption->name }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="px-3 h-9 text-xs font-medium rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white">
                                    Activar
                                </button>
                            </form>

                            <form action="{{ route('subscription-leads.update', $lead) }}" method="POST">
                                @csrf @method('PUT')
                                <select name="status" onchange="this.form.submit()"
                                    class="px-2 h-9 text-xs rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white">
                                    <option value="pendiente" @selected($lead->status === 'pendiente')>Pendiente</option>
                                    <option value="contactado" @selected($lead->status === 'contactado')>Contactado</option>
                                    <option value="descartado" @selected($lead->status === 'descartado')>Descartado</option>
                                </select>
                            </form>

                            <form action="{{ route('subscription-leads.destroy', $lead) }}" method="POST" onsubmit="return confirm('¿Eliminar esta solicitud?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-500 hover:text-red-700 text-xs font-medium">Eliminar</button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
    </div>

    

   
</x-app-layout>