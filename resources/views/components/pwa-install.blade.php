{{-- Aviso para instalar la app. Chrome/Edge/Android usan el evento beforeinstallprompt; en iPhone se explica el paso manual. --}}
@php
    $installIcon = \App\Http\Controllers\PwaController::iconUrl(192);
@endphp

<div id="pwa-install" class="hidden fixed bottom-4 left-4 z-[9998] max-w-sm bg-white dark:bg-slate-900 text-gray-800 dark:text-gray-100 rounded-xl shadow-2xl border border-gray-200 dark:border-slate-700 p-4 flex items-start gap-3">
    @if ($installIcon)
        <img src="{{ $installIcon }}" alt="" class="w-10 h-10 rounded-lg shrink-0">
    @endif
    <div class="flex-1 min-w-0">
        <p class="text-sm font-semibold">Instala la aplicación</p>
        <p id="pwa-install-text" class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Accede más rápido desde tu pantalla de inicio, como una app.</p>
        <div class="flex gap-2 mt-2.5">
            <button type="button" id="pwa-install-btn" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white">Instalar</button>
            <button type="button" id="pwa-install-dismiss" class="px-3 py-1.5 text-xs font-medium rounded-lg text-gray-500 hover:text-gray-800 dark:text-gray-300 dark:hover:text-white">Ahora no</button>
        </div>
    </div>
</div>

<script>
    (function () {
        const box = document.getElementById('pwa-install');
        const btn = document.getElementById('pwa-install-btn');
        const text = document.getElementById('pwa-install-text');
        const headerButtons = document.querySelectorAll('[data-pwa-install-button]');
        const updateButtons = document.querySelectorAll('[data-pwa-update-button]');
        const KEY = 'pwa-install-dismissed';
        const WAIT = 7 * 24 * 60 * 60 * 1000; // no volver a mostrar el aviso automático por 7 días
        let deferred = null;

        const standalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
        const ios = /iphone|ipad|ipod/i.test(navigator.userAgent) && !window.MSStream;
        const dismissedAt = () => { try { return parseInt(localStorage.getItem(KEY) || '0', 10); } catch (e) { return 0; } };
        const canShow = () => !standalone && Date.now() - dismissedAt() > WAIT;

        // Ya instalada (abierta como app): el botón del encabezado no hace falta.
        if (standalone) headerButtons.forEach((b) => b.classList.add('hidden'));

        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferred = e;
            if (canShow()) box.classList.remove('hidden');
        });

        window.addEventListener('appinstalled', () => {
            box.classList.add('hidden');
            headerButtons.forEach((b) => b.classList.add('hidden'));
        });

        async function install() {
            if (!deferred) return;
            deferred.prompt();
            await deferred.userChoice;
            deferred = null;
            box.classList.add('hidden');
        }

        btn.addEventListener('click', install);

        document.getElementById('pwa-install-dismiss').addEventListener('click', () => {
            try { localStorage.setItem(KEY, String(Date.now())); } catch (e) {}
            box.classList.add('hidden');
        });

        // Botón del encabezado: instala directo si el navegador lo permite; si no, explica cómo hacerlo a mano.
        headerButtons.forEach((b) => b.addEventListener('click', () => {
            if (deferred) {
                install();
                return;
            }

            text.textContent = !window.isSecureContext
                ? 'Para instalar la aplicación el sitio debe abrirse con HTTPS (conexión segura).'
                : ios
                    ? 'En Safari toca el botón Compartir y elige "Agregar a inicio".'
                    : 'En el menú del navegador (⋮) elige "Instalar aplicación" o "Agregar a la pantalla de inicio".';
            btn.classList.add('hidden');
            box.classList.remove('hidden');
        }));

        // iPhone/iPad (Safari) no lanza el evento: se muestra la indicación manual.
        if (ios && canShow()) {
            text.textContent = 'En Safari toca el botón Compartir y elige "Agregar a inicio".';
            btn.classList.add('hidden');
            box.classList.remove('hidden');
        }

        // Botón "Actualizar app": revisa si hay una versión nueva del service worker, limpia la
        // caché de archivos estáticos y recarga, para que la app quede al día.
        async function updateApp(button) {
            const original = button.innerHTML;
            button.disabled = true;
            button.style.opacity = '0.7';

            try {
                if ('serviceWorker' in navigator) {
                    const registrations = await navigator.serviceWorker.getRegistrations();
                    await Promise.all(registrations.map((r) => r.update().catch(() => {})));
                }
                if ('caches' in window) {
                    const keys = await caches.keys();
                    await Promise.all(keys.map((key) => caches.delete(key)));
                }
            } catch (e) {
                // Si algo falla, igual recargamos: la red servirá la versión más reciente.
            }

            window.location.reload();
        }

        updateButtons.forEach((b) => b.addEventListener('click', () => updateApp(b)));
    })();
</script>
