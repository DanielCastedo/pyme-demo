{{-- Datos para instalar la app (PWA): manifiesto, colores, ícono de iOS y registro del service worker.
     El nombre sale de APP_NAME (.env) y el ícono de public/images/logo-pymes.png. --}}
@php
    $appTitle = \Illuminate\Support\Str::limit((string) config('app.name'), 12, '');
    $appleIcon = \App\Http\Controllers\PwaController::iconUrl(180);
@endphp

<link rel="manifest" href="{{ route('pwa.manifest', absolute: false) }}">
<meta name="theme-color" content="#059669">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="{{ $appTitle }}">
@if ($appleIcon)
    <link rel="apple-touch-icon" href="{{ $appleIcon }}">
@endif

<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/sw.js').catch(() => {});
        });
    }
</script>
