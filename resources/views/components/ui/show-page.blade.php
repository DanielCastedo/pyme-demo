@props([
    'title' => 'Detalles',
    'category' => 'Configuración',
    'backRoute' => '',
    'editRoute' => '',
    'editText' => 'Editar',
    'pdfRoute' => '',
    'pdfText' => 'Imprimir / Descargar PDF',
    'items' => [],
    'columns' => 2,
    'canEdit' => null,
])

@php
    $showEditButton = false;
    if (!empty($editRoute)) {
        if ($canEdit !== null) {
            $showEditButton = (bool) $canEdit;
        } else {
            $user = auth()->user();
            if ($user) {
                if ($user->can('admin')) {
                    $showEditButton = true;
                } else {
                    try {
                        $req = \Illuminate\Http\Request::create($editRoute);
                        $matchedRoute = app('router')->getRoutes()->match($req);
                        $routeName = $matchedRoute->getName();
                        if ($routeName && str_contains($routeName, '.')) {
                            $parts = explode('.', $routeName);
                            $module = $parts[0] ?? '';
                            $permission = "editar {$module}";
                            if (\Spatie\Permission\Models\Permission::where('name', $permission)->exists()) {
                                $showEditButton = $user->can($permission);
                            } else {
                                $showEditButton = true;
                            }
                        } else {
                            $showEditButton = true;
                        }
                    } catch (\Throwable $e) {
                        $showEditButton = true;
                    }
                }
            }
        }
    }
@endphp

<x-app-layout>
    <div class="max-w-10xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {{-- Encabezado compacto --}}
        <div class="mb-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                @if ($category)
                    <span class="text-[10px] font-bold tracking-wider text-emerald-600 dark:text-emerald-400 uppercase">{{ $category }}</span>
                @endif
                <h2 class="text-xl font-bold text-gray-800 dark:text-white sm:mt-0.5">{{ $title }}</h2>
            </div>

            <div class="flex items-center gap-2 flex-wrap sm:justify-end">
                @if ($backRoute)
                    <a href="{{ $backRoute }}"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-gray-300 dark:bg-slate-800 dark:border-gray-600 rounded-lg text-xs font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Volver
                    </a>
                @endif

                @if ($pdfRoute)
                    <a href="{{ $pdfRoute }}" target="_blank"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white rounded-lg text-xs font-medium transition shadow-sm">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        {{ $pdfText }}
                    </a>
                @endif

                @if ($showEditButton)
                    <a href="{{ $editRoute }}"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gray-500 hover:bg-gray-600 text-white rounded-lg text-xs font-medium transition-colors shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        {{ $editText }}
                    </a>
                @endif

                {{ $extraActions ?? '' }}
            </div>
        </div>

        {{-- Contenido de la Tarjeta --}}
        <div
            class="bg-white dark:bg-slate-900 shadow-sm border border-gray-200 dark:border-gray-700 rounded-xl p-4 sm:p-5">

            {{-- Bloque de Información Principal (Grid dinámico) --}}
            @if (!empty($items))
                <div
                    class="grid grid-cols-1 @if ($columns == 2) md:grid-cols-2 @elseif($columns == 3) md:grid-cols-3 @elseif($columns == 4) md:grid-cols-4 @endif gap-6 mb-8">
                    @foreach ($items as $item)
                        @php
                            $span = $item['fullWidth'] ?? false ? "md:col-span-{$columns}" : '';
                            $isMono = $item['mono'] ?? false;
                        @endphp
                        <div
                            class="p-3 bg-gray-50 dark:bg-slate-800/40 rounded-lg border border-gray-100 dark:border-gray-800/60 {{ $span }}">
                            <span
                                class="block text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-0.5">
                                {{ $item['label'] }}
                            </span>
                            <span
                                class="text-sm font-medium {{ $isMono ? 'font-mono text-emerald-600 dark:text-emerald-400' : 'text-gray-900 dark:text-white' }}">
                                {{ $item['value'] }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Slot para relaciones complejas o listados (como los Permisos) --}}
            @if ($slot->isNotEmpty())
                <div class="mt-6">
                    {{ $slot }}
                </div>
            @endif


        </div>
    </div>
</x-app-layout>
