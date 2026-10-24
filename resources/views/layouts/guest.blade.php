<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <x-pwa-head />

        <title>{{ $title ?? config('app.name', 'Ferretería') }}</title>

        <!-- Fonts -->
        <link rel="icon" type="image/png" href="/{{ config('app.logo') }}" />
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400..700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- Styles -->
        @livewireStyles

        <script>
            if (localStorage.getItem('dark-mode') === 'false' || !('dark-mode' in localStorage)) {
                document.querySelector('html').classList.remove('dark');
                document.querySelector('html').style.colorScheme = 'light';
            } else {
                document.querySelector('html').classList.add('dark');
                document.querySelector('html').style.colorScheme = 'dark';
            }
        </script>
    </head>
    <body class="font-inter antialiased bg-slate-100 dark:bg-slate-900 text-slate-600 dark:text-slate-400">

        <x-page-loader />

        <main class="bg-white dark:bg-slate-900">

            <!-- Content -->
            <div class="w-full">

                <div class="min-h-[100dvh] h-full">

                    <!-- Header -->
                    <div>

                    </div>

                    <div class="w-full max-w-3xl mx-auto px-4 py-8">
                        {{ $slot }}
                    </div>

                </div>

            </div>

        </main>

        @livewireScripts
        <x-pwa-install />
    </body>
</html>