<x-app-layout>
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

        {{-- Bienvenida --}}
        @php
            $hour = now()->hour;
            $saludo = $hour < 12 ? 'Buenos días' : ($hour < 19 ? 'Buenas tardes' : 'Buenas noches');
            $nombre = auth()->user()?->name;
        @endphp
        <div class="mb-12 rounded-2xl bg-gradient-to-r from-slate-600 to-slate-700 dark:from-slate-700 dark:to-slate-800 p-6 sm:p-8 text-white">
            <p class="text-slate-200 text-sm font-medium">{{ $saludo }}{{ $nombre ? ', ' . $nombre : '' }} 👋</p>
            <h1 class="text-2xl sm:text-3xl font-extrabold mt-1">Bienvenido/a a {{ config('app.name') }}</h1>
            <p class="text-slate-100/90 text-sm mt-2 max-w-2xl">
                Desde aquí puedes gestionar tus ventas, inventario, compras y finanzas. Instala la app en tu dispositivo
                para acceder más rápido, o actualízala si notas que algo no carga como debería.
                ¿Necesitas soporte del sistema o alguna ayuda? Escríbenos, con gusto te atendemos.
            </p>

            <div class="flex flex-wrap gap-3 mt-5">
                <button type="button" data-pwa-install-button
                    class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg bg-white text-slate-700 hover:bg-slate-100 text-sm font-semibold transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v10m0 0l-4-4m4 4l4-4M5 20h14" />
                    </svg>
                    Descargar app
                </button>

                <button type="button" data-pwa-update-button
                    class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg bg-white/10 hover:bg-white/20 border border-white/20 text-white text-sm font-semibold transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Actualizar app
                </button>

                <a href="https://wa.me/59176061697?text={{ urlencode('Hola, necesito soporte/ayuda con el sistema.') }}"
                    target="_blank"
                    class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg bg-white/10 hover:bg-white/20 border border-white/20 text-white text-sm font-semibold transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 5.636a9 9 0 010 12.728m-3.536-9.192a4.5 4.5 0 010 6.364M12 12h.01M9 9.5a1.5 1.5 0 013 0c0 1-1.5 1.5-1.5 2.5m.5 3h.01" />
                    </svg>
                    Soporte / Ayuda
                </a>
            </div>
        </div>

        {{-- Contacto directo por WhatsApp --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-12">
            <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-gray-700 rounded-2xl p-6 flex flex-col">
                <div class="w-11 h-11 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-slate-600 dark:text-slate-300" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.372-.025-.521-.075-.148-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                    </svg>
                </div>
                <h3 class="text-base font-bold text-gray-900 dark:text-white mb-1.5">¿Quieres comprar este sistema?</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 flex-1 mb-4">
                    Contáctanos directo por WhatsApp y te ayudamos a activar tu propia versión del sistema para tu negocio.
                </p>
                <a href="https://wa.me/59176061697?text={{ urlencode('Hola, me interesa comprar el sistema de gestión. ¿Me pueden dar más información?') }}"
                    target="_blank"
                    class="inline-flex items-center justify-center gap-2 w-full py-2.5 rounded-lg bg-slate-700 hover:bg-slate-800 text-white text-sm font-semibold transition">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.372-.025-.521-.075-.148-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                    </svg>
                    Escribir por WhatsApp
                </a>
            </div>

            <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-gray-700 rounded-2xl p-6 flex flex-col">
                <div class="w-11 h-11 rounded-xl bg-sky-100 dark:bg-sky-900/30 flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-sky-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                </div>
                <h3 class="text-base font-bold text-gray-900 dark:text-white mb-1.5">¿Necesitas un software a medida?</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 flex-1 mb-4">
                    Desarrollamos sistemas personalizados según las necesidades específicas de tu negocio o empresa.
                </p>
                <a href="https://wa.me/59176061697?text={{ urlencode('Hola, quisiera conversar sobre un software personalizado para mi negocio.') }}"
                    target="_blank"
                    class="inline-flex items-center justify-center gap-2 w-full py-2.5 rounded-lg border border-sky-300 dark:border-sky-700 text-sky-700 dark:text-sky-300 hover:bg-sky-50 dark:hover:bg-sky-900/20 text-sm font-semibold transition">
                    Conversemos tu proyecto
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
