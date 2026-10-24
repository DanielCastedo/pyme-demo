<div id="update-banner"
    class="hidden fixed bottom-4 right-4 z-[9999] max-w-sm bg-slate-900 text-white rounded-xl shadow-2xl border border-slate-700 p-4 flex items-start gap-3">
    <svg class="w-5 h-5 text-emerald-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
    </svg>
    <div class="flex-1 min-w-0">
        <p class="text-sm font-semibold">Hay una actualización disponible</p>
        <p class="text-xs text-slate-300 mt-0.5">Actualiza la página para usar la última versión del sistema.</p>
        <div class="flex gap-2 mt-2.5">
            <button type="button" id="update-banner-btn"
                class="px-3 py-1.5 text-xs font-medium rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white">
                Actualizar ahora
            </button>
            <button type="button" id="update-banner-dismiss"
                class="px-3 py-1.5 text-xs font-medium rounded-lg text-slate-300 hover:text-white">
                Más tarde
            </button>
        </div>
    </div>
</div>

<script>
    (function () {
        const initialVersion = document.querySelector('meta[name="app-version"]')?.content;
        if (!initialVersion) return;

        const banner = document.getElementById('update-banner');
        const btnUpdate = document.getElementById('update-banner-btn');
        const btnDismiss = document.getElementById('update-banner-dismiss');

        btnUpdate?.addEventListener('click', () => window.location.reload());
        btnDismiss?.addEventListener('click', () => banner.classList.add('hidden'));

        async function checkVersion() {
            try {
                const res = await fetch('{{ route('app.version') }}', { cache: 'no-store' });
                const data = await res.json();

                if (data.version && data.version !== initialVersion) {
                    banner.classList.remove('hidden');
                }
            } catch (e) {
                // silencioso: si falla la red, no molesta al usuario
            }
        }

        // primera revisión a los 2 minutos, luego cada 5 minutos
        setTimeout(checkVersion, 2 * 60 * 1000);
        setInterval(checkVersion, 5 * 60 * 1000);
    })();
</script>