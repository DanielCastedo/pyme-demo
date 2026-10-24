@props([
    'action' => '',
    'fields' => [],
    'resetRoute' => null,
    'collapsible' => false,
])

@php
    $hasActiveFilters = collect($fields)->contains(fn ($f) => filled($f['value'] ?? null));
    $uid = 'filters_' . md5($action);
@endphp

<div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-gray-700 rounded-xl p-4 mb-5">
    @if ($collapsible)
        <button type="button" id="{{ $uid }}-toggle"
            class="flex items-center gap-1.5 text-xs font-semibold text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">
            <svg id="{{ $uid }}-icon" class="w-3.5 h-3.5 transition-transform duration-150 -rotate-90" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
            <span>Filtros</span>
            @if ($hasActiveFilters)
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500" title="Hay filtros aplicados"></span>
            @endif
        </button>
    @endif

    <form method="GET" action="{{ $action }}" id="{{ $uid }}-form"
        class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-{{ min(count($fields) + 1, 5) }} gap-4 items-end {{ $collapsible ? 'mt-4 hidden' : '' }}">
        @foreach ($fields as $field)
            <x-ui.form-field
                :type="$field['type'] ?? 'select'"
                :name="$field['name'] ?? ''"
                :label="$field['label'] ?? ''"
                :value="$field['value'] ?? ''"
                :options="$field['options'] ?? []"
                :placeholder="$field['placeholder'] ?? ''"
            />
        @endforeach

        <div class="flex gap-2 flex-wrap">
            <x-ui.button type="submit" variant="primary">Filtrar</x-ui.button>
            @if ($resetRoute && $hasActiveFilters)
                <a href="{{ $resetRoute }}">
                    <x-ui.button type="button" variant="outline">Limpiar</x-ui.button>
                </a>
            @endif
            {{ $slot ?? '' }}
        </div>
    </form>
</div>

@if ($collapsible)
    <script>
        (function () {
            const KEY = 'filters-collapsed:{{ $uid }}';
            const form = document.getElementById('{{ $uid }}-form');
            const icon = document.getElementById('{{ $uid }}-icon');
            const toggle = document.getElementById('{{ $uid }}-toggle');
            if (!form || !toggle) return;

            const apply = (collapsed) => {
                form.classList.toggle('hidden', collapsed);
                icon.classList.toggle('-rotate-90', collapsed);
            };

            // Empieza siempre cerrado (ya lo marca así el propio HTML, para no mostrarlo abierto
            // un instante antes de que cargue este script). Solo se abre si la última vez que
            // esta pantalla se usó, quedó abierto a propósito.
            let collapsed = true;
            try {
                if (localStorage.getItem(KEY) === '0') collapsed = false;
            } catch (e) {}
            apply(collapsed);

            toggle.addEventListener('click', () => {
                collapsed = !form.classList.contains('hidden');
                apply(collapsed);
                try { localStorage.setItem(KEY, collapsed ? '1' : '0'); } catch (e) {}
            });
        })();
    </script>
@endif
