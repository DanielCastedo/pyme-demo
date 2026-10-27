<x-guest-layout :title="'Términos y Condiciones — Aceptación Requerida'" :company="$company">
    <div class="w-full">

        {{-- Logo y Empresa --}}
        <div class="text-center mb-6">
            <img class="mx-auto h-14 w-auto object-contain mb-3" src="{{ $appLogoUrl }}" alt="{{ $company?->name }}">
            <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                {{ $company?->name ?? 'Sistema de Gestión y Punto de Venta' }}
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Acreditación de Administrador/a Designado/a · Licencia de Software
            </p>
        </div>

        {{-- Alertas de validación --}}
        @if ($errors->any())
            <x-ui.alert type="error" class="mb-5">
                <ul class="list-disc pl-5 space-y-1 text-xs">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-ui.alert>
        @endif

        {{-- Tarjeta Principal --}}
        <div class="bg-white dark:bg-slate-900 shadow-xl rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden">

            {{-- Cabecera con aviso --}}
            <div class="p-5 sm:p-6 bg-slate-900 text-white border-b border-slate-200 dark:border-slate-800">
                <div class="flex items-center gap-3.5">
                    <div class="w-10 h-10 rounded-xl bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold leading-tight text-white">Lectura y Aceptación Obligatoria de Términos</h2>
                        <p class="text-xs text-slate-300 dark:text-slate-400 mt-0.5">
                            Hola, <strong class="text-white">{{ $user->name }}</strong>. El Superadministrador te ha acreditado como administrador/a firmante. Para ingresar al sistema debes leer y aceptar las condiciones de la licencia.
                        </p>
                    </div>
                </div>
            </div>

            {{-- Caja desplazable con el contrato --}}
            <div class="p-5 sm:p-6">
                <div class="p-4 sm:p-5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 max-h-96 overflow-y-auto shadow-inner text-justify text-xs sm:text-sm text-slate-700 dark:text-slate-300">
                    @include('terms.partials.contract-content')
                </div>

                <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-2 text-right">
                    Desplaza el cuadro superior para leer el contrato completo.
                </p>

                {{-- Formulario de Aceptación --}}
                <form id="terms-accept-form" action="{{ route('terms.accept') }}" method="POST" class="mt-6 pt-5 border-t border-slate-200 dark:border-slate-800">
                    @csrf

                    <label class="flex items-start gap-3 p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700/80 cursor-pointer transition hover:bg-slate-100 dark:hover:bg-slate-800">
                        <input type="checkbox" name="accept_terms" id="accept_terms" value="1"
                            class="w-5 h-5 rounded border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-emerald-600 focus:ring-emerald-500 mt-0.5 cursor-pointer">
                        <span class="text-xs sm:text-sm font-semibold text-slate-800 dark:text-slate-200 leading-snug">
                            He leído atentamente, comprendido y acepto en su totalidad los Términos y Condiciones del Servicio, entendiendo expresamente que la licencia por suscripción no incluye modificaciones gratuitas de código ni desarrollos a medida.
                        </span>
                    </label>

                    <div class="mt-6 flex flex-col-reverse sm:flex-row items-center justify-between gap-3">
                        {{-- Botón Rechazar (dispara el form de reject) --}}
                        <x-ui.button type="button" id="btn-reject" variant="outline" size="lg" class="w-full sm:w-autotext-rose-600 dark:text-rose-400 border-rose-300 dark:border-rose-800 hover:bg-rose-50 dark:hover:bg-rose-900/30">
                            Rechazar y Salir
                        </x-ui.button>

                        {{-- Botón Aceptar --}}
                        <x-ui.button type="submit" id="btn-accept" variant="success" size="lg" :disabled="true"
                            icon='<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />'
                            class="w-full sm:w-auto whitespace-nowrap">
                            Aceptar Términos y Entrar al Sistema
                        </x-ui.button>
                    </div>
                </form>

                {{-- Formulario oculto de Rechazo --}}
                <form id="terms-reject-form" action="{{ route('terms.reject') }}" method="POST" class="hidden">
                    @csrf
                </form>

            </div>
        </div>

        <p class="text-center text-xs text-slate-400 dark:text-slate-500 mt-6">
            IP de conexión: {{ request()->ip() }} · Fecha: {{ now()->format('d/m/Y H:i') }}
        </p>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const check = document.getElementById('accept_terms');
            const btnAccept = document.getElementById('btn-accept');
            const btnReject = document.getElementById('btn-reject');
            const rejectForm = document.getElementById('terms-reject-form');

            if (check && btnAccept) {
                check.addEventListener('change', function() {
                    btnAccept.disabled = !this.checked;
                });
            }

            if (btnReject && rejectForm) {
                btnReject.addEventListener('click', function() {
                    if (confirm('¿Estás seguro de que deseas rechazar los Términos y Condiciones? Se cerrará tu sesión y no podrás utilizar el sistema hasta que los aceptes.')) {
                        rejectForm.submit();
                    }
                });
            }
        });
    </script>
</x-guest-layout>
