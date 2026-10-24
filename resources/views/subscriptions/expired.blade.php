<x-app-layout>
    <div class="max-w-2xl mx-auto px-4 py-16 text-center">
        <div class="bg-white dark:bg-slate-900 border border-red-200 dark:border-red-800 rounded-2xl p-8 shadow-sm">
            <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86l-7.5 13A1 1 0 003.67 18h16.66a1 1 0 00.88-1.5l-7.5-13a1 1 0 00-1.74 0z"/>
                </svg>
            </div>

           <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-2">
                @if ($subscription?->status === 'pending')
                    Tu solicitud está en revisión
                @else
                    Tu suscripción ha vencido
                @endif
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
                @if ($subscription?->status === 'pending')
                    Ya enviaste tu solicitud de suscripción al plan <strong>{{ $subscription->plan->name }}</strong>.
                    Un administrador la revisará pronto y activará tu acceso.
                @elseif ($subscription)
                    Tu plan <strong>{{ $subscription->plan->name }}</strong> venció el
                    {{ optional($subscription->ends_at)->format('d/m/Y') }}. Renueva tu suscripción para seguir usando el sistema.
                @else
                    No tienes una suscripción activa. Elige un plan para continuar usando el sistema.
                @endif
            </p>

            @if ($subscription?->status !== 'pending')
                <a href="{{ route('subscriptions.plans') }}">
                    <x-ui.button variant="primary">Ver planes y renovar</x-ui.button>
                </a>
            @endif

            <a href="{{ route('subscriptions.plans') }}">
                <x-ui.button variant="primary">Ver planes y renovar</x-ui.button>
            </a>
        </div>
    </div>
</x-app-layout>