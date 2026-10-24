<x-authentication-layout>
    <h1 class="text-3xl text-gray-800 dark:text-gray-100 font-bold mb-6">{{ __('Registrar Usuario') }}</h1>

    {{-- Mostrar errores de validación --}}
    @if ($errors->any())
        <x-validation-errors class="mb-4" />
    @endif

    {{-- Mostrar mensaje de éxito --}}
    @if (session('success'))
        <div class="mb-4 text-green-700 bg-green-100 border border-green-400 rounded p-4 dark:bg-green-900 dark:text-green-200 dark:border-green-700">
            {{ session('success') }}
        </div>
    @endif

    <!-- Formulario de registro -->
    <form method="POST" action="{{ route('auth.store') }}">
        @csrf
        <div class="space-y-4">
            <div>
                <x-label for="name">Nombre <span class="text-red-500">*</span></x-label>
                <x-input id="name" type="text" name="name" :value="old('name')" required autofocus autocomplete="name"/>
            </div>

            <div>
                <x-label for="email">Email <span class="text-red-500">*</span></x-label>
                <x-input id="email" type="email" name="email" :value="old('email')" required autocomplete="email"/>
            </div>

            <div>
                <x-label for="password" value="Contraseña"/>
                <x-input id="password" type="password" name="password" required autocomplete="new-password"/>
            </div>

            <div>
                <x-label for="password_confirmation" value="Confirmar Contraseña"/>
                <x-input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"/>
            </div>
        </div>

        <div class="flex items-center justify-between mt-6">
            <div class="mr-1">
                <label class="flex items-center cursor-pointer">
                    <input type="checkbox" class="form-checkbox rounded border-gray-300 dark:border-gray-600 text-emerald-600 shadow-sm focus:ring focus:ring-emerald-200 focus:ring-opacity-50"/>
                    <span class="text-sm ml-2 text-gray-700 dark:text-gray-300">Recibir novedades por email.</span>
                </label>
            </div>
            <x-button>
                {{ __('Registrar') }}
            </x-button>
        </div>
        {{-- Términos y privacidad de Jetstream (opcional) --}}
        @if (Laravel\Jetstream\Jetstream::hasTermsAndPrivacyPolicyFeature())
            <div class="mt-6">
                <label class="flex items-start">
                    <input type="checkbox" class="form-checkbox mt-1 rounded border-gray-300 dark:border-gray-600 text-emerald-600" name="terms" id="terms" />
                    <span class="text-sm ml-2 text-gray-700 dark:text-gray-300">
                            {!! __('Acepto los :terms_of_service y :privacy_policy', [
                                'terms_of_service' => '<a target="_blank" href="'.route('terms.show').'" class="text-sm underline hover:no-underline">'.__('Términos de Servicio').'</a>',
                                'privacy_policy' => '<a target="_blank" href="'.route('policy.show').'" class="text-sm underline hover:no-underline">'.__('Política de Privacidad').'</a>',
                            ]) !!}
                    </span>
                </label>
            </div>
        @endif
    </form>

    <!-- Footer -->
    <div class="pt-5 mt-6 border-t border-gray-100 dark:border-gray-700/60">
        <div class="text-sm">
            {{ __('¿Ya tienes una cuenta?') }}
            <a class="font-medium text-emerald-600 hover:text-emerald-700 dark:hover:text-emerald-400" href="{{ route('login') }}">
                {{ __('Iniciar sesión') }}
            </a>
        </div>
    </div>
</x-authentication-layout>
