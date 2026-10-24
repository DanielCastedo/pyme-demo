@props([
    'placeholder' => 'Buscar...',
    'filterName' => 'buscar',
    'value' => request('filter.buscar'),
    'icon' => true,
    'showButton' => true,
])

@php
    $inputName = "filter[{$filterName}]";
@endphp

<form method="GET" action="{{ request()->url() }}" class="w-full" id="searchForm-{{ $filterName }}">
    @foreach (request()->query() as $key => $val)
        @if ($key !== 'page')
            @if ($key === 'filter' && is_array($val))
                @foreach ($val as $subKey => $subVal)
                    @if ($subKey !== $filterName)
                        <input type="hidden" name="filter[{{ $subKey }}]" value="{{ $subVal }}">
                    @endif
                @endforeach
            @elseif (!is_array($val) && $key !== $inputName)
                <input type="hidden" name="{{ $key }}" value="{{ $val }}">
            @endif
        @endif
    @endforeach

    <div class="relative flex items-center">
        <input type="text" name="{{ $inputName }}" value="{{ $value }}" placeholder="{{ $placeholder }}" id="searchInput-{{ $filterName }}"
            class="w-full @if ($icon) pl-3@else px-3 @endif @if ($showButton || $value) pr-12@else pr-4 @endif py-2.5 text-sm rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition">

        @if ($value)
            <button type="button" id="clearBtn-{{ $filterName }}"
                class="absolute right-9 top-1/2 -translate-y-1/2 p-1.5 text-gray-400 hover:text-red-600 dark:hover:text-red-400 transition rounded hover:bg-gray-100 dark:hover:bg-slate-700"
                title="Limpiar búsqueda">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        @endif

        @if ($showButton)
            <button type="submit"
                class="absolute right-1.5 top-1/2 -translate-y-1/2 p-1.5 text-gray-400 hover:text-emerald-600 dark:hover:text-emerald-400 transition rounded hover:bg-gray-100 dark:hover:bg-slate-700"
                title="Buscar">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </button>
        @endif
    </div>
</form>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const clearBtn = document.getElementById('clearBtn-{{ $filterName }}');
        const searchInput = document.getElementById('searchInput-{{ $filterName }}');
        const searchForm = document.getElementById('searchForm-{{ $filterName }}');

        if (clearBtn) {
            clearBtn.addEventListener('click', function(e) {
                e.preventDefault();
                searchInput.value = '';
                searchForm.submit();
            });
        }
    });
</script>
