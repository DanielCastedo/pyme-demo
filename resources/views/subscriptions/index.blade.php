<x-app-layout>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        @if (session('success'))
            <x-ui.alert type="success" :message="session('success')" />
        @endif

        <x-ui.page-header category="Configuración" title="Mi Suscripción"
            description="Estado actual de la suscripción de {{ $company->name }}." />

        <div class="bg-white dark:bg-slate-900 shadow-sm border border-gray-200 dark:border-gray-700 rounded-xl p-5 sm:p-6">
            @if ($subscription)
                @php
                    $statusColors = [
                        'active'   => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-900/20 dark:text-emerald-300',
                        'trial'    => 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-900/20 dark:text-blue-300',
                        'past_due' => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-900/20 dark:text-amber-300',
                        'canceled' => 'bg-gray-100 text-gray-700 border-gray-300 dark:bg-slate-800 dark:text-gray-300',
                        'expired'  => 'bg-red-50 text-red-700 border-red-200 dark:bg-red-900/20 dark:text-red-300',
                    ];
                    $statusLabels = [
                        'active' => 'Activa', 'trial' => 'Prueba', 'past_due' => 'Pago Pendiente',
                        'canceled' => 'Cancelada', 'expired' => 'Vencida',
                    ];
                @endphp

                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                    <div>
                        <span class="text-[10px] font-bold tracking-wider text-gray-400 uppercase">Plan actual</span>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white">{{ $subscription->plan->name }}</h3>
                    </div>
                    <span class="inline-flex items-center h-7 px-3 rounded-full text-xs font-semibold border {{ $statusColors[$subscription->status] ?? '' }}">
                        {{ $statusLabels[$subscription->status] ?? $subscription->status }}
                    </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                    <div class="p-3 bg-gray-50 dark:bg-slate-800/40 rounded-lg border border-gray-100 dark:border-gray-800/60">
                        <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-0.5">Inicio</span>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $subscription->starts_at->format('d/m/Y') }}</span>
                    </div>
                    <div class="p-3 bg-gray-50 dark:bg-slate-800/40 rounded-lg border border-gray-100 dark:border-gray-800/60">
                        <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-0.5">Vence</span>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ optional($subscription->ends_at)->format('d/m/Y') ?? '—' }}</span>
                    </div>
                    <div class="p-3 bg-gray-50 dark:bg-slate-800/40 rounded-lg border border-gray-100 dark:border-gray-800/60">
                        <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-0.5">Precio</span>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ number_format($subscription->plan->price, 2) }} {{ $subscription->plan->currency }}</span>
                    </div>
                </div>

                {{-- <div class="flex flex-wrap gap-2">
                    <a href="{{ route('subscriptions.plans') }}">
                        <x-ui.button variant="primary">Cambiar / Renovar Plan</x-ui.button>
                    </a>
                    <a href="{{ route('subscriptions.invoices') }}">
                        <x-ui.button variant="outline">Ver Facturas</x-ui.button>
                    </a>
                    @if (in_array($subscription->status, ['active', 'trial']))
                        <form action="{{ route('subscriptions.cancel') }}" method="POST"
                            onsubmit="return confirm('¿Seguro que deseas cancelar tu suscripción?')">
                            @csrf
                            <x-ui.button type="submit" variant="danger">Cancelar Suscripción</x-ui.button>
                        </form>
                    @endif
                </div> --}}
            @else
                {{-- <div class="text-center py-8">
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Aún no tienes una suscripción activa.</p>
                    <a href="{{ route('subscriptions.plans') }}">
                        <x-ui.button variant="primary">Ver Planes Disponibles</x-ui.button>
                    </a>
                </div> --}}
            @endif
        </div>
    </div>
</x-app-layout>