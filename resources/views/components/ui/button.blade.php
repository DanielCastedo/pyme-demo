@props([
    'type' => 'button',
    'id' => null,
    'variant' => 'primary',
    'size' => 'md',
    'icon' => null,
    'iconPosition' => 'left',
    'fullWidth' => false,
    'disabled' => false,
    'class' => '',
])

@php
    $baseClasses = 'inline-flex items-center justify-center font-semibold rounded-lg transition-colors gap-1.5 disabled:opacity-50 disabled:cursor-not-allowed';

    $variants = [
        'primary' => 'bg-gray-500 dark:bg-slate-700 hover:bg-gray-700 dark:hover:bg-slate-600 text-white shadow-sm',
        'secondary' => 'bg-gray-200 hover:bg-gray-300 dark:bg-slate-700 dark:hover:bg-slate-600 text-gray-900 dark:text-white',
        'danger' => 'bg-red-600 hover:bg-red-700 text-white shadow-sm',
        'warning' => 'bg-yellow-600 hover:bg-yellow-700 text-white shadow-sm',
        'success' => 'bg-green-600 hover:bg-green-700 text-white shadow-sm',
        'outline' => 'border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-800',
    ];

    $sizes = [
        'sm' => 'px-3 py-1.5 text-xs',
        'md' => 'px-4 py-2 text-xs',
        'lg' => 'px-6 py-3 text-sm',
    ];

    $variantClass = $variants[$variant] ?? $variants['primary'];
    $sizeClass = $sizes[$size] ?? $sizes['md'];
    $widthClass = $fullWidth ? 'w-full' : 'w-auto';

    $finalClass = "$baseClasses $variantClass $sizeClass $widthClass";
    if ($class) {
        $finalClass .= " $class";
    }
@endphp

<button
    type="{{ $type }}"
    @if($id) id="{{ $id }}" @endif
    {{ $attributes->merge(['class' => $finalClass]) }}
    @if($disabled) disabled @endif
>
    @if($icon && $iconPosition === 'left')
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            {!! $icon !!}
        </svg>
    @endif

    <span>{{ $slot }}</span>

    @if($icon && $iconPosition === 'right')
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            {!! $icon !!}
        </svg>
    @endif
</button>
