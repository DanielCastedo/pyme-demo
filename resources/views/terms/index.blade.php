<x-app-layout>
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        @if (session('success'))
            <x-ui.alert type="success" :message="session('success')" />
        @endif

        @if (session('error'))
            <x-ui.alert type="error" :message="session('error')" />
        @endif

        {{-- Encabezado --}}
        <x-ui.page-header category="Información Legal" title="Términos y Condiciones de la Licencia"
            description="Contrato de licencia de software y términos de uso bajo suscripción." />

        {{-- Tarjeta de Estado del Acuerdo --}}
        <div class="mb-6 p-5 rounded-xl border bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 shadow-sm">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    @if ($user->hasAcceptedTerms() || ($user->hasRole('superadmin') && $designatedAdmin?->hasAcceptedTerms()))
                        <div class="w-10 h-10 rounded-full bg-emerald-100 dark:bg-emerald-900/40 border border-emerald-300 dark:border-emerald-700 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <div>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-emerald-100 dark:bg-emerald-900/40 text-emerald-800 dark:text-emerald-300">
                                Estado: Confirmado
                            </span>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                @if ($user->hasRole('superadmin') && $designatedAdmin)
                                    Aceptado por la administradora/admin designada: <strong>{{ $designatedAdmin->name }}</strong> ({{ $designatedAdmin->email }}) el {{ $designatedAdmin->terms_accepted_at?->format('d/m/Y H:i') }} desde IP: {{ $designatedAdmin->terms_ip ?? 'N/D' }}.
                                @elseif($user->hasAcceptedTerms())
                                    Confirmado formalmente por ti el {{ $user->terms_accepted_at?->format('d/m/Y H:i') }} desde IP: {{ $user->terms_ip ?? request()->ip() }}.
                                @else
                                    Licencia activa para {{ $company->name ?? 'la Empresa' }}.
                                @endif
                            </p>
                        </div>
                    @elseif($designatedAdmin && !$designatedAdmin->hasAcceptedTerms())
                        <div class="w-10 h-10 rounded-full bg-amber-100 dark:bg-amber-900/40 border border-amber-300 dark:border-amber-700 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-amber-100 dark:bg-amber-900/40 text-amber-800 dark:text-amber-300">
                                Estado: Pendiente de Confirmación
                            </span>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                Administrador/a designado/a: <strong>{{ $designatedAdmin->name }}</strong> aún no ha confirmado la lectura y aceptación al ingresar.
                            </p>
                        </div>
                    @else
                        <div class="w-10 h-10 rounded-full bg-sky-100 dark:bg-sky-900/40 border border-sky-300 dark:border-sky-700 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5 text-sky-600 dark:text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-sky-100 dark:bg-sky-900/40 text-sky-800 dark:text-sky-300">
                                Licencia Vigente
                            </span>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                Contrato marco de software por suscripción para {{ $company->name ?? 'la Empresa' }}.
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Contenedor del Contrato --}}
        <div class="p-6 sm:p-8 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm">
            @include('terms.partials.contract-content')
        </div>

    </div>
</x-app-layout>
