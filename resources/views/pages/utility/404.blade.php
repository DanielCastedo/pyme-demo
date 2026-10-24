<x-app-layout>
    <div class="min-h-[80vh] flex items-center justify-center px-4">
        <div class="text-center max-w-lg mx-auto">

            {{-- Número 404 --}}
            <div class="relative mb-6 select-none">
                <span class="text-[8rem] sm:text-[10rem] font-extrabold text-gray-100 dark:text-slate-800 leading-none">
                    404
                </span>
                <div class="absolute inset-0 flex items-center justify-center">
                    <div class="w-20 h-20 rounded-2xl bg-red-50 dark:bg-red-900/20 border border-red-100 dark:border-red-800/40 flex items-center justify-center shadow-sm">
                        <svg class="w-10 h-10 text-red-500 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Título --}}
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white mb-3">
                Página no encontrada
            </h1>

            {{-- Descripción --}}
            <p class="text-sm sm:text-base text-gray-500 dark:text-gray-400 mb-8 leading-relaxed">
                La página que estás buscando no existe, fue movida o el enlace es incorrecto.
            </p>

            {{-- Acciones --}}
            <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                <a href="{{ route('dashboard') }}"
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold shadow-sm transition-all hover:-translate-y-0.5">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    Ir al inicio
                </a>

                <button onclick="history.back()"
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-700 dark:text-gray-300 text-sm font-semibold hover:bg-gray-50 dark:hover:bg-slate-700 transition-all hover:-translate-y-0.5">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Volver atrás
                </button>
            </div>

        </div>
    </div>
</x-app-layout>
