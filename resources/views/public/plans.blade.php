<x-guest-layout>
    <main class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

        @if (session('success'))
            <div class="mb-8 p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800 text-sm text-center">
                {{ session('success') }}
            </div>
        @endif

        <div class="text-center mb-10">
            <h2 class="text-3xl font-extrabold text-gray-900">Elige el plan ideal para tu negocio</h2>
            <p class="text-gray-500 mt-2">Sin compromiso. Te contactamos para activar tu cuenta.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-16">
            @foreach ($plans as $plan)
                <div class="bg-white border border-gray-200 rounded-2xl p-6 flex flex-col shadow-sm hover:shadow-md transition">
                    <h3 class="text-lg font-bold text-gray-900">{{ $plan->name }}</h3>
                    <p class="text-xs text-gray-500 mt-1 mb-4">{{ $plan->description }}</p>

                    <div class="mb-4">
                        <span class="text-3xl font-extrabold text-gray-900">{{ number_format($plan->price, 0) }}</span>
                        <span class="text-sm text-gray-500"> {{ $plan->currency }} / {{ $plan->billing_cycle === 'monthly' ? 'mes' : ($plan->billing_cycle === 'yearly' ? 'año' : 'trimestre') }}</span>
                    </div>

                    <ul class="space-y-2 mb-6 flex-1">
                        @foreach ($plan->features ?? [] as $feature)
                            <li class="flex items-center gap-2 text-sm text-gray-700">
                                <svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                {{ $feature }}
                            </li>
                        @endforeach
                    </ul>

                    <button type="button"
                        onclick="document.getElementById('plan-id-input').value='{{ $plan->id }}';document.getElementById('plan-name-label').textContent='{{ $plan->name }}';document.getElementById('request-modal').classList.remove('hidden')"
                        class="w-full py-2.5 rounded-lg bg-slate-800 hover:bg-slate-900 text-white text-sm font-semibold transition">
                        Quiero este plan
                    </button>
                </div>
            @endforeach
        </div>

        <div class="text-center">
            <p class="text-sm text-gray-500 mb-3">¿No estás seguro de qué plan necesitas?</p>
            <button type="button"
                onclick="document.getElementById('plan-id-input').value='';document.getElementById('plan-name-label').textContent='Consulta general';document.getElementById('request-modal').classList.remove('hidden')"
                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg border border-gray-300 text-gray-700 text-sm font-medium hover:bg-gray-50 transition">
                Solicitar información
            </button>
        </div>
    </main>

    <div id="request-modal" tabindex="-1" aria-hidden="true"
        class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm p-0 sm:p-4 flex items-end sm:items-center justify-center">
        <div class="w-full sm:max-w-md sm:my-8">
            <div class="bg-white rounded-t-2xl sm:rounded-2xl border border-gray-200 shadow-2xl overflow-hidden max-h-[90vh] flex flex-col">
                <div class="px-6 py-5 border-b border-gray-100 shrink-0 flex items-start justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">Solicitar Suscripción</h3>
                        <p class="text-xs text-gray-500 mt-1">Plan: <strong id="plan-name-label">—</strong></p>
                    </div>
                    <button type="button" onclick="document.getElementById('request-modal').classList.add('hidden')"
                        class="text-gray-400 hover:text-gray-600 transition shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form action="{{ route('public.plans.request') }}" method="POST" class="px-6 py-5 overflow-y-auto space-y-3">
                    @csrf
                    <input type="hidden" name="subscription_plan_id" id="plan-id-input" value="">

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">Nombre Completo *</label>
                        <input type="text" name="name" required value="{{ old('name') }}" placeholder="Ej. Juan Pérez"
                            class="w-full px-3 h-10 text-sm rounded-lg border border-gray-300 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                        @error('name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">Nombre del Negocio</label>
                        <input type="text" name="business_name" value="{{ old('business_name') }}" placeholder="Ej. Mochilas El Estudiante"
                            class="w-full px-3 h-10 text-sm rounded-lg border border-gray-300 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">Teléfono / WhatsApp *</label>
                        <input type="text" name="phone" required value="{{ old('phone') }}" placeholder="Ej. 70012345"
                            class="w-full px-3 h-10 text-sm rounded-lg border border-gray-300 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                        @error('phone') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">Correo</label>
                        <input type="email" name="email" value="{{ old('email') }}" placeholder="ejemplo@correo.com"
                            class="w-full px-3 h-10 text-sm rounded-lg border border-gray-300 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">Mensaje</label>
                        <textarea name="message" rows="3" placeholder="Contanos qué necesitas (opcional)"
                            class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">{{ old('message') }}</textarea>
                    </div>

                    <button type="submit" class="w-full py-2.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold transition mt-2">
                        Enviar Solicitud
                    </button>
                </form>
            </div>
        </div>
    </div>

    @if ($errors->any())
        <script>document.getElementById('request-modal').classList.remove('hidden');</script>
    @endif
</x-guest-layout>