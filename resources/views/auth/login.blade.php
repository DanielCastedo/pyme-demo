<x-authentication-layout>

    <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">Iniciar sesión</h1>
    <p class="mt-1 mb-6 text-sm text-gray-500 dark:text-gray-400">Ingresa con tu correo y contraseña.</p>

    @if (session('status'))
        <div class="mb-4 px-3 py-2 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 dark:bg-emerald-900/30 dark:border-emerald-800 dark:text-emerald-300 text-sm">
            {{ session('status') }}
        </div>
    @endif

    <x-validation-errors class="mb-4" />

    <form id="login-form" method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Correo electrónico</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                class="form-input block w-full px-3 py-2 rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-slate-800 text-sm text-gray-900 dark:text-white focus:outline-none focus:border-emerald-600 focus:ring-emerald-600">
        </div>

        <div>
            <div class="flex items-center justify-between mb-1">
                <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Contraseña</label>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="text-xs text-emerald-700 dark:text-emerald-400 hover:underline">¿La olvidaste?</a>
                @endif
            </div>
            <input id="password" type="password" name="password" required autocomplete="current-password"
                class="form-input block w-full px-3 py-2 rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-slate-800 text-sm text-gray-900 dark:text-white focus:outline-none focus:border-emerald-600 focus:ring-emerald-600">
        </div>

        <label for="remember_me" class="inline-flex items-center gap-2 cursor-pointer select-none">
            <input id="remember_me" type="checkbox" name="remember" class="form-checkbox w-4 h-4 rounded border-gray-300 text-emerald-700 focus:ring-emerald-600 dark:bg-slate-800 dark:border-gray-600">
            <span class="text-sm text-gray-600 dark:text-gray-400">Recordarme</span>
        </label>

        <button type="submit" class="w-full px-4 py-2.5 rounded-lg text-sm font-semibold text-white bg-emerald-700 hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-600 dark:focus:ring-offset-slate-900 transition-colors">
            Ingresar
        </button>
    </form>

    @if (config('demo.enabled'))
        <!-- Modo demostración (DEMO_MODE=true, ver config/demo.php) -->
        <div class="mt-6 pt-5 border-t border-gray-200 dark:border-gray-700">
            <p class="mb-2 text-xs font-medium text-gray-500 dark:text-gray-400">Usuarios de prueba</p>

            <!-- Accesos directos: llenan el formulario con el usuario de prueba y lo envían -->
            <ul class="divide-y divide-gray-200 dark:divide-gray-700 rounded-lg border border-gray-200 dark:border-gray-700">
                @foreach (config('demo.users') as $demoUser)
                    <li>
                        <button type="button" data-demo-login data-email="{{ $demoUser['email'] }}" data-password="{{ $demoUser['password'] }}"
                            class="w-full flex items-center justify-between gap-3 px-3 py-2 text-left hover:bg-gray-50 dark:hover:bg-slate-800 transition-colors first:rounded-t-lg last:rounded-b-lg">
                            <span class="min-w-0">
                                <span class="block text-sm font-medium text-gray-800 dark:text-gray-100">{{ $demoUser['label'] }}</span>
                                <span class="block text-xs text-gray-500 dark:text-gray-400 truncate">{{ $demoUser['email'] }}</span>
                            </span>
                            <span class="shrink-0 text-xs text-gray-500 dark:text-gray-400">{{ $demoUser['role'] }}</span>
                        </button>
                    </li>
                @endforeach
            </ul>

            <!-- Restablecer la base de demostración (migrate:fresh --seed) -->
            <form id="demo-reset-form" method="POST" action="{{ route('demo.reset') }}" class="mt-3 flex items-center justify-between gap-3">
                @csrf
                <span class="text-xs text-gray-400 dark:text-gray-500">Vuelve el inventario y los registros a su estado inicial.</span>
                <button type="submit" id="demo-reset-button"
                    class="shrink-0 inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-md border border-gray-300 dark:border-gray-600 text-xs font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-800 transition-colors disabled:opacity-60 disabled:cursor-wait">
                    <svg id="demo-reset-icon" xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <span id="demo-reset-label">Actualizar inventario</span>
                </button>
            </form>
        </div>

        <script>
            document.querySelectorAll('[data-demo-login]').forEach((button) => {
                button.addEventListener('click', () => {
                    document.getElementById('email').value = button.dataset.email;
                    document.getElementById('password').value = button.dataset.password;
                    document.getElementById('login-form').requestSubmit();
                });
            });

            document.getElementById('demo-reset-form').addEventListener('submit', (event) => {
                if (! confirm('¿Restablecer los datos de demostración?\n\nSe borran todas las ventas, compras, movimientos y cambios hechos, y el inventario vuelve a su estado inicial.')) {
                    event.preventDefault();
                    return;
                }

                document.getElementById('demo-reset-button').disabled = true;
                document.getElementById('demo-reset-icon').classList.add('animate-spin');
                document.getElementById('demo-reset-label').textContent = 'Restableciendo...';
            });
        </script>
    @endif

</x-authentication-layout>
