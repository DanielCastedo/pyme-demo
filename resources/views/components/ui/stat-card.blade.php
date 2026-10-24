@props([
    'label' => '',
    'value' => 0,
    'color' => 'gray', // gray, emerald, red, sky, amber, violet
])

@php
    $colors = [
        'gray'    => ['border' => 'border-gray-200 dark:border-gray-700', 'text' => 'text-gray-400', 'value' => 'text-gray-900 dark:text-white'],
        'emerald' => ['border' => 'border-emerald-200 dark:border-emerald-800', 'text' => 'text-emerald-600', 'value' => 'text-emerald-600'],
        'red'     => ['border' => 'border-red-200 dark:border-red-800', 'text' => 'text-red-600', 'value' => 'text-red-600'],
        'sky'     => ['border' => 'border-sky-200 dark:border-sky-800', 'text' => 'text-sky-600', 'value' => 'text-sky-600'],
        'amber'   => ['border' => 'border-amber-200 dark:border-amber-800', 'text' => 'text-amber-600', 'value' => 'text-amber-600'],
        'violet'  => ['border' => 'border-violet-200 dark:border-violet-800', 'text' => 'text-violet-600', 'value' => 'text-violet-600'],
    ];

    $c = $colors[$color] ?? $colors['gray'];
@endphp

<div class="p-4 bg-white dark:bg-slate-900 border {{ $c['border'] }} rounded-xl">
    <span class="block text-[10px] font-bold {{ $c['text'] }} uppercase tracking-wider mb-1">{{ $label }}</span>
    <span class="text-2xl font-bold {{ $c['value'] }}">{{ $value }}</span>
</div>