<x-authentication-layout>

    <!-- Encabezado -->
    <div class="text-center mb-8">

        <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight mb-2">
            Bienvenido a <span class="bg-clip-text text-transparent bg-gradient-to-r from-emerald-500 to-emerald-700">Login</span>
        </h1>
        <p class="text-sm text-gray-500 dark:text-gray-400">Ingresa tus credenciales para acceder a tu cuenta</p>
    </div>

    <!-- Alerta de Estado -->
    @if (session('status'))
        <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 dark:bg-emerald-900/30 dark:border-emerald-800 dark:text-emerald-300 font-medium text-sm text-center shadow-sm">
            {{ session('status') }}
        </div>
    @endif

    <!-- Errores de Validación -->
    <x-validation-errors class="mb-6" />

    <!-- Formulario -->
    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <!-- Correo -->
        <div>
            <label for="email" class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Correo Electrónico</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                    <svg class="w-5 h-5 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                    </svg>
                </div>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus placeholder="ejemplo@correo.com"
                    class="block w-full pl-11 py-3 rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-slate-800 text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 dark:focus:ring-emerald-500 transition-shadow shadow-sm">
            </div>
        </div>

        <!-- Contraseña -->
        <div>
            <label for="password" class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Contraseña</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                    <svg class="w-5 h-5 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>
                <input id="password" type="password" name="password" required autocomplete="current-password" placeholder="••••••••"
                    class="block w-full pl-11 py-3 rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-slate-800 text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 dark:focus:ring-emerald-500 transition-shadow shadow-sm">
            </div>
        </div>

        <!-- Opciones Adicionales (Recordarme y Olvidé mi contraseña) -->
        <div class="flex items-center justify-between mt-2">
            <label for="remember_me" class="flex items-center cursor-pointer group">
                <input id="remember_me" type="checkbox" class="w-4 h-4 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 dark:bg-slate-800 dark:border-gray-600 cursor-pointer transition-colors" name="remember">
                <span class="ml-2 text-sm font-medium text-gray-600 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white transition-colors select-none">Recordarme</span>
            </label>


        </div>

        <!-- Botón Ingresar -->
        <div class="pt-2">
            <button type="submit" class="w-full flex items-center justify-center gap-2 px-8 py-3.5 text-sm font-bold text-white transition-all bg-gradient-to-r from-emerald-500 to-emerald-700 hover:from-emerald-600 hover:to-emerald-800 rounded-xl shadow-lg hover:shadow-emerald-500/30 hover:-translate-y-0.5 focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 dark:focus:ring-offset-slate-900">
                Iniciar Sesión
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                </svg>
            </button>
        </div>
    </form>

    <!-- Footer / Aviso -->
    <div class="mt-8 pt-6 border-t border-gray-100 dark:border-gray-800">
        <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-100 dark:border-amber-800/50 p-4 rounded-xl flex items-start gap-3">
            <div class="mt-0.5 p-1.5 bg-amber-100 dark:bg-amber-800/50 rounded-lg text-amber-600 dark:text-amber-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div>
                <h4 class="text-sm font-bold text-amber-800 dark:text-amber-300">Acceso Seguro</h4>
                <p class="text-xs text-amber-700 dark:text-amber-400 mt-1 leading-relaxed">Su información está protegida con encriptación de extremo a extremo.</p>
            </div>
        </div>
    </div>
</x-authentication-layout>
