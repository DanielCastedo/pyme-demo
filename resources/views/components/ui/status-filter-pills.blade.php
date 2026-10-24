@props([
    'statuses' => collect(),
    'route' => '',
    'filterKey' => 'currentStatus',
    'labelKey' => 'name',
])

@php
    $current = request("filter.$filterKey");

    $base = 'inline-flex items-center gap-1 h-7 px-2.5 rounded-full text-[11px] sm:text-xs font-semibold border leading-none transition-all duration-150';

    $activeBase = 'shadow-sm ring-1';
    $inactiveBase = 'hover:shadow-sm';

    $stateStyles = function ($name) {
        $n = mb_strtolower((string) $name);

        return match (true) {
            str_contains($n, 'recep') => [
                'active'   => 'bg-teal-600 text-white border-teal-600 ring-teal-300',
                'inactive' => 'bg-teal-50 text-teal-700 border-teal-200 hover:bg-teal-100 dark:bg-teal-900/20 dark:text-teal-300 dark:border-teal-700',
                'icon'     => '<svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6M7 4h10a2 2 0 012 2v12a2 2 0 01-2 2H7a2 2 0 01-2-2V6a2 2 0 012-2z"/></svg>',
            ],
            str_contains($n, 'proces') => [
                'active'   => 'bg-cyan-600 text-white border-cyan-600 ring-cyan-300',
                'inactive' => 'bg-cyan-50 text-cyan-700 border-cyan-200 hover:bg-cyan-100 dark:bg-cyan-900/20 dark:text-cyan-300 dark:border-cyan-700',
                'icon'     => '<svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6l4 2m6-2a10 10 0 11-20 0 10 10 0 0120 0z"/></svg>',
            ],
            str_contains($n, 'emit') => [
                'active'   => 'bg-slate-800 text-white border-slate-800 ring-slate-300',
                'inactive' => 'bg-slate-100 text-slate-700 border-slate-300 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-600',
                'icon'     => '<svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h10M7 11h10M7 15h6M5 3h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2z"/></svg>',
            ],
            str_contains($n, 'aprob') => [
                'active'   => 'bg-emerald-600 text-white border-emerald-600 ring-emerald-300',
                'inactive' => 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100 dark:bg-emerald-900/20 dark:text-emerald-300 dark:border-emerald-700',
                'icon'     => '<svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>',
            ],
            str_contains($n, 'observ') => [
                'active'   => 'bg-amber-500 text-white border-amber-500 ring-amber-300',
                'inactive' => 'bg-amber-50 text-amber-700 border-amber-200 hover:bg-amber-100 dark:bg-amber-900/20 dark:text-amber-300 dark:border-amber-700',
                'icon'     => '<svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86l-7.5 13A1 1 0 003.67 18h16.66a1 1 0 00.88-1.5l-7.5-13a1 1 0 00-1.74 0z"/></svg>',
            ],
            str_contains($n, 'pag') => [
                'active'   => 'bg-lime-600 text-white border-lime-600 ring-lime-300',
                'inactive' => 'bg-lime-50 text-lime-700 border-lime-200 hover:bg-lime-100 dark:bg-lime-900/20 dark:text-lime-300 dark:border-lime-700',
                'icon'     => '<svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a5 5 0 00-10 0v2m-2 0h14a2 2 0 012 2v7a2 2 0 01-2 2H5a2 2 0 01-2-2v-7a2 2 0 012-2z"/></svg>',
            ],
            str_contains($n, 'ejecut') => [
                'active'   => 'bg-sky-600 text-white border-sky-600 ring-sky-300',
                'inactive' => 'bg-sky-50 text-sky-700 border-sky-200 hover:bg-sky-100 dark:bg-sky-900/20 dark:text-sky-300 dark:border-sky-700',
                'icon'     => '<svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>',
            ],
            str_contains($n, 'archiv') => [
                'active'   => 'bg-green-600 text-white border-green-600 ring-green-300',
                'inactive' => 'bg-green-50 text-green-700 border-green-200 hover:bg-green-100 dark:bg-green-900/20 dark:text-green-300 dark:border-green-700',
                'icon'     => '<svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8l1 12h12l1-12M9 8V5h6v3"/></svg>',
            ],
            default => [
                'active'   => 'bg-gray-700 text-white border-gray-700 ring-gray-300',
                'inactive' => 'bg-gray-100 text-gray-700 border-gray-300 hover:bg-gray-200 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-600',
                'icon'     => '<svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="12" cy="12" r="8" stroke-width="2"/></svg>',
            ],
        };
    };

    $allActive = empty($current);
@endphp

<div class="flex flex-wrap justify-center items-center gap-1.5 sm:gap-2">
    {{-- Ver todo --}}
    <a href="{{ route($route, request()->except('filter')) }}"
       class="{{ $base }} {{ $allActive ? "$activeBase bg-gray-800 text-white border-gray-800 ring-gray-300" : "$inactiveBase bg-white text-gray-700 border-gray-300 hover:bg-gray-50 dark:bg-slate-800 dark:text-gray-200 dark:border-slate-600 dark:hover:bg-slate-700" }}">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
        </svg>
        Ver todo
    </a>

    @foreach($statuses as $status)
        @php
            $name = data_get($status, $labelKey);
            $isActive = (string)$current === (string)$status->id;
            $cfg = $stateStyles($name);

            $query = request()->query();
            $query['filter'][$filterKey] = $status->id;

            $classes = $isActive
                ? "$base $activeBase {$cfg['active']}"
                : "$base $inactiveBase {$cfg['inactive']}";
        @endphp

        <a href="{{ route($route, $query) }}" class="{{ $classes }}">
            {!! $cfg['icon'] !!}
            <span>{{ $name }}</span>
        </a>
    @endforeach
</div>