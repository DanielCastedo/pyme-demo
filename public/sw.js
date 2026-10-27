/*
 * Service worker de la PWA.
 * - No cachea páginas del sistema (son dinámicas y dependen de la sesión): siempre van a la red.
 * - Si no hay conexión al abrir una página, muestra /offline.html.
 * - Cachea los archivos estáticos compilados (/build) y las imágenes para que la app abra más rápido.
 * Al cambiar este archivo, sube VERSION para que los navegadores limpien la caché anterior.
 */
const VERSION = 'v3';
const STATIC_CACHE = `static-${VERSION}`;
const OFFLINE_URL = '/offline.html';
const PRECACHE = [OFFLINE_URL];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(STATIC_CACHE).then((cache) => cache.addAll(PRECACHE)).then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.filter((key) => key !== STATIC_CACHE).map((key) => caches.delete(key))))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const request = event.request;
    const url = new URL(request.url);

    // Solo GET del mismo origen; formularios (POST/PUT/DELETE) y otros dominios pasan directo.
    if (request.method !== 'GET' || url.origin !== self.location.origin) {
        return;
    }

    // Páginas: siempre desde la red; sin conexión, la pantalla offline.
    if (request.mode === 'navigate') {
        event.respondWith(fetch(request).catch(() => caches.match(OFFLINE_URL)));
        return;
    }

    // Archivos compilados (nombre con hash) e imágenes: caché primero, y se guardan al pedirlos.
    if (url.pathname.startsWith('/build/') || url.pathname.startsWith('/images/')) {
        event.respondWith(
            caches.match(request).then((cached) => {
                if (cached) {
                    return cached;
                }

                return fetch(request).then((response) => {
                    if (response.ok) {
                        const copy = response.clone();
                        caches.open(STATIC_CACHE).then((cache) => cache.put(request, copy));
                    }

                    return response;
                });
            })
        );
    }
});
