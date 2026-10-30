<x-app-layout>
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        @if (session('success'))
            <x-ui.alert type="success" :message="session('success')" />
        @endif

        <x-ui.page-header category="Configuración" title="Planes Disponibles"
            description="Elige el plan que mejor se adapte a tu negocio." />

        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            @foreach ($plans as $plan)
                <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-gray-700 rounded-2xl p-6 flex flex-col shadow-sm">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ $plan->name }}</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 mb-4">{{ $plan->description }}</p>

                    <div class="mb-4">
                        <span class="text-3xl font-extrabold text-gray-900 dark:text-white">{{ number_format($plan->price, 0) }}</span>
                        <span class="text-sm text-gray-500 dark:text-gray-400"> {{ $plan->currency }} / {{ $plan->billing_cycle === 'monthly' ? 'mes' : ($plan->billing_cycle === 'yearly' ? 'año' : 'trimestre') }}</span>
                    </div>

                    <ul class="space-y-2 mb-6 flex-1">
                        @foreach ($plan->features ?? [] as $feature)
                            <li class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                <svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                {{ $feature }}
                            </li>
                        @endforeach
                    </ul>

                    @auth
                        <x-ui.button type="button" variant="primary" :fullWidth="true"
                            onclick="openSubscribeModal({{ $plan->id }}, '{{ $plan->name }}', '{{ $plan->qr_image_url }}')">
                            Elegir {{ $plan->name }}
                        </x-ui.button>
                    @else
                        <a href="{{ route('login') }}">
                            <x-ui.button type="button" variant="primary" :fullWidth="true">
                                Elegir {{ $plan->name }}
                            </x-ui.button>
                        </a>
                    @endauth
                </div>
            @endforeach
        </div>
    </div>

    @auth
        {{-- Modal de confirmación de pago --}}
        <div id="subscribe-modal" tabindex="-1" aria-hidden="true"
            class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm p-0 sm:p-4 flex items-end sm:items-center justify-center">
            <div class="w-full sm:max-w-md sm:my-8">
                <div class="bg-white dark:bg-slate-900 rounded-t-2xl sm:rounded-2xl border border-gray-200 dark:border-slate-700 shadow-2xl overflow-hidden max-h-[90vh] flex flex-col">

                    <div class="px-6 py-5 border-b border-gray-100 dark:border-slate-800 shrink-0">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Confirmar Suscripción</h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    Plan seleccionado: <strong id="subscribe-plan-name" class="text-gray-700 dark:text-gray-300"></strong>
                                </p>
                            </div>
                            <button type="button" onclick="document.getElementById('subscribe-modal').classList.add('hidden')"
                                class="inline-flex items-center justify-center w-9 h-9 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-slate-800 transition shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <form action="{{ route('subscriptions.subscribe') }}" method="POST" class="px-6 py-5 overflow-y-auto">
                        @csrf
                        <input type="hidden" name="subscription_plan_id" id="subscribe-plan-id">

                        <div class="space-y-5">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                    Método de Pago <span class="text-red-500">*</span>
                                </label>

                                <div class="grid grid-cols-2 gap-2">
                                    <label class="cursor-pointer">
                                        <input type="radio" name="method" value="qr" class="peer hidden" required checked
                                            onchange="toggleQr(true)">
                                        <div class="text-center px-3 py-2.5 rounded-lg border border-gray-300 dark:border-slate-600 text-sm font-medium text-gray-600 dark:text-gray-300 peer-checked:border-emerald-500 peer-checked:bg-emerald-50 peer-checked:text-emerald-700 dark:peer-checked:bg-emerald-900/20 dark:peer-checked:text-emerald-300 transition">
                                            Pago QR
                                        </div>
                                    </label>
                                    <label class="cursor-pointer">
                                        <input type="radio" name="method" value="efectivo" class="peer hidden" required
                                            onchange="toggleQr(false)">
                                        <div class="text-center px-3 py-2.5 rounded-lg border border-gray-300 dark:border-slate-600 text-sm font-medium text-gray-600 dark:text-gray-300 peer-checked:border-emerald-500 peer-checked:bg-emerald-50 peer-checked:text-emerald-700 dark:peer-checked:bg-emerald-900/20 dark:peer-checked:text-emerald-300 transition">
                                            Efectivo
                                        </div>
                                    </label>
                                </div>
                            </div>

                            {{-- Bloque QR (visible solo si el método es QR) --}}
                            <div id="qr-block" class="text-center">
                                <img id="qr-image" src="" alt="QR de pago" class="w-40 h-40 mx-auto rounded-lg border border-gray-200 dark:border-slate-700 object-contain hidden">
                                <p id="qr-empty" class="text-xs text-gray-500 dark:text-gray-400">Este plan aún no tiene un QR configurado. Contacta al administrador.</p>

                                <div class="mt-3 p-3 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-lg text-xs text-emerald-800 dark:text-emerald-300 text-left">
                                    Después de pagar, envía tu comprobante por WhatsApp al
                                    <a href="https://wa.me/{{ config('payments.whatsapp_number') }}" target="_blank" class="font-bold underline">
                                        {{ config('payments.whatsapp_number') }}
                                    </a>
                                    para agilizar la aprobación.
                                </div>
                            </div>

                            <x-ui.form-field type="text" name="reference" label="Referencia / N° de Comprobante"
                                value="{{ $defaultReference }}" required />
                        </div>

                        <div class="mt-6 pt-4 border-t border-gray-100 dark:border-slate-800 flex justify-end gap-2">
                            <x-ui.button type="button" variant="outline"
                                onclick="document.getElementById('subscribe-modal').classList.add('hidden')">
                                Cancelar
                            </x-ui.button>
                            <x-ui.button type="submit" variant="primary">Enviar Solicitud</x-ui.button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        @push('scripts')
            <script>
                function openSubscribeModal(planId, planName, qrUrl) {
                    document.getElementById('subscribe-plan-id').value = planId;
                    document.getElementById('subscribe-plan-name').textContent = planName;

                    const qrImg = document.getElementById('qr-image');
                    const qrEmpty = document.getElementById('qr-empty');

                    if (qrUrl && qrUrl !== '') {
                        qrImg.src = qrUrl;
                        qrImg.classList.remove('hidden');
                        qrEmpty.classList.add('hidden');
                    } else {
                        qrImg.classList.add('hidden');
                        qrEmpty.classList.remove('hidden');
                    }

                    toggleQr(true); // método QR viene marcado por defecto
                    document.getElementById('subscribe-modal').classList.remove('hidden');
                }

                function toggleQr(show) {
                    document.getElementById('qr-block').style.display = show ? 'block' : 'none';
                }
            </script>
        @endpush
    @endauth
</x-app-layout>