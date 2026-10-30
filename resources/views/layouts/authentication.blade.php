@php
    // sw.js sirve /images/ desde su caché: con ?v=<fecha del archivo>, al reemplazar una imagen cambia la URL y se baja la nueva.
    $versionedAsset = function (string $path): string {
        $file = public_path($path);

        return asset($path).(is_file($file) ? '?v='.filemtime($file) : '');
    };
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <x-pwa-head />

        <title>{{ config('app.name', 'Bioquimica') }}</title>

        <!-- Fonts -->
        <link rel="icon" type="image/png" href="{{ $versionedAsset(config('app.logo')) }}" />
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

            <div class="relative flex">

                <!-- Content -->
                <div class="w-full md:w-7/12 lg:w-1/2">

                    <div class="min-h-[100dvh] h-full flex flex-col">

                        <!-- Header -->
                        <div class="flex-1">
                            <div class="flex items-center h-16 px-4 sm:px-6 lg:px-8">
                                <a class="flex items-center gap-2" href="{{ route('dashboard') }}">
                                    <img src="{{ $versionedAsset(config('app.logo')) }}" alt="" width="32" height="32" class="w-8 h-8 rounded-md object-contain" />
                                    <span class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ config('app.name') }}</span>
                                </a>
                            </div>
                        </div>

                        <div class="max-w-sm mx-auto w-full px-4 py-6">
                            {{ $slot }}
                        </div>

                        <!-- Footer -->
                        <div class="flex-1 flex items-end">
                            <p class="w-full px-4 sm:px-6 lg:px-8 py-4 text-xs text-gray-400 dark:text-gray-500">© {{ date('Y') }} {{ config('app.name') }}</p>
                        </div>

                    </div>

                </div>

                <!-- Image -->
                <div class="hidden md:block absolute top-0 bottom-0 right-0 md:w-5/12 lg:w-1/2 border-l border-gray-200 dark:border-gray-800" aria-hidden="true">
                    <img class="object-cover object-top w-full h-full" src="{{ $versionedAsset('images/hero.webp') }}" width="1100" height="1150" alt="" />
                </div>

            </div>

        </main>

        @livewireScripts
        <x-pwa-install />
    </body>
</html>
