@props([
    'type' => 'success', // success, error, warning, info
    'message' => null,
    'dismissible' => true,
])

@php
    $types = [
        'success' => [
            'bg' => 'bg-emerald-50 dark:bg-emerald-900/30',
            'border' => 'border-emerald-200 dark:border-emerald-800',
            'text' => 'text-emerald-800 dark:text-emerald-300',
            'icon' => 'text-emerald-500',
            'path' => 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
        ],
        'error' => [
            'bg' => 'bg-red-50 dark:bg-red-900/30',
            'border' => 'border-red-200 dark:border-red-800',
            'text' => 'text-red-800 dark:text-red-300',
            'icon' => 'text-red-500',
            'path' => 'm9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
        ],
        'warning' => [
            'bg' => 'bg-yellow-50 dark:bg-yellow-900/30',
            'border' => 'border-yellow-200 dark:border-yellow-800',
            'text' => 'text-yellow-800 dark:text-yellow-300',
            'icon' => 'text-yellow-500',
            'path' => 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z',
        ],
        'info' => [
            'bg' => 'bg-blue-50 dark:bg-blue-900/30',
            'border' => 'border-blue-200 dark:border-blue-800',
            'text' => 'text-blue-800 dark:text-blue-300',
            'icon' => 'text-blue-500',
            'path' => 'm11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z',
        ],
    ];

    $colors = $types[$type] ?? $types['success'];

    // Se anota lo que ya se mostró en esta petición: el aviso general del layout (x-ui.flash-messages) no lo duplica.
    $shownAlerts = request()->attributes->get('alerts_shown', []);
    $shownAlerts[] = ['type' => $type, 'text' => trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags((string) ($message ?? $slot)), ENT_QUOTES)))];
    request()->attributes->set('alerts_shown', $shownAlerts);
@endphp

<div class="alert-{{ $type }} mb-5 px-4 py-2.5 rounded-lg {{ $colors['bg'] }} border {{ $colors['border'] }} {{ $colors['text'] }} font-medium text-xs flex items-center gap-2 shadow-sm"
    @if($dismissible) data-alert-dismissible @endif>

    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0 {{ $colors['icon'] }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="{{ $colors['path'] }}" />
    </svg>

    <span class="flex-1">
        {{ $message ?? $slot }}
    </span>

    @if($dismissible)
        <button type="button" class="ml-2 inline-flex text-current opacity-70 hover:opacity-100 transition"
            onclick="this.closest('.alert-{{ $type }}').remove()">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    @endif
</div>
