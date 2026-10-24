@php
    $items = [
        ['label' => 'Nombre', 'value' => $plan->name],
        ['label' => 'Precio', 'value' => number_format($plan->price, 2) . ' ' . $plan->currency],
        ['label' => 'Ciclo', 'value' => ucfirst($plan->billing_cycle)],
        ['label' => 'Máx. Usuarios', 'value' => $plan->max_users ?? 'Ilimitado'],
        ['label' => 'Estado', 'value' => $plan->active ? 'Activo' : 'Inactivo'],
        ['label' => 'Descripción', 'value' => $plan->description ?? '—', 'fullWidth' => true],
    ];
@endphp

<x-ui.show-page
    title="Detalle del Plan"
    category="Suscripciones"
    :backRoute="route('subscription-plans.index')"
    :editRoute="route('subscription-plans.edit', $plan)"
    :items="$items"
    :columns="2"
>
    @if (!empty($plan->features))
        <div>
            <span class="block text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">Características incluidas</span>
            <ul class="space-y-1.5">
                @foreach ($plan->features as $feature)
                    <li class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                        <svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        {{ $feature }}
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</x-ui.show-page>