@props([
    'category' => '',
    'title' => '',
    'description' => '',
    'buttons' => [],
])

<div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    {{-- Contenido de texto --}}
    <div>
        @if ($category)
            <span class="text-[10px] font-bold tracking-wider text-emerald-600 dark:text-emerald-400 uppercase">
                {{ $category }}
            </span>
        @endif

        @if ($title)
            <h2 class="text-xl font-bold text-gray-800 dark:text-white mt-0.5">
                {{ $title }}
            </h2>
        @endif

        @if ($description)
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                {{ $description }}
            </p>
        @endif

        @if ($slot->isNotEmpty())
            <div class="mt-2">
                {{ $slot }}
            </div>
        @endif
    </div>

    {{-- Botones --}}
    @php
        $user = auth()->user();
        $filteredButtons = collect($buttons)->filter(function ($button) use ($user) {
            if (!$user) return false;
            if ($user->can('admin')) return true;
            if (isset($button['condition']) && is_callable($button['condition']) && !$button['condition']()) {
                return false;
            }
            if (!empty($button['permission']) && !$user->can($button['permission'])) {
                return false;
            }
            if (!empty($button['can']) && !$user->can($button['can'])) {
                return false;
            }
            return true;
        })->values();
    @endphp

    @if ($filteredButtons->isNotEmpty())
        <div class="grid grid-cols-1 sm:grid-flow-col sm:auto-cols-fr gap-2">
            @foreach ($filteredButtons as $button)
                @if (!empty($button['route']))
                    <a href="{{ $button['route'] }}" @if(!empty($button['target'])) target="{{ $button['target'] }}" @endif class="block">
                        <x-ui.button :id="$button['id'] ?? null" :type="$button['type'] ?? 'button'" :variant="$button['variant'] ?? 'primary'" :size="$button['size'] ?? 'md'"
                            :icon="$button['icon'] ?? null" :iconPosition="$button['iconPosition'] ?? 'left'" :fullWidth="true" class="h-10 whitespace-nowrap">
                            {{ $button['label'] ?? 'Botón' }}
                        </x-ui.button>
                    </a>
                @else
                    <x-ui.button :id="$button['id'] ?? null" :type="$button['type'] ?? 'button'" :variant="$button['variant'] ?? 'primary'" :size="$button['size'] ?? 'md'"
                        :icon="$button['icon'] ?? null" :iconPosition="$button['iconPosition'] ?? 'left'" :fullWidth="true" class="h-10 whitespace-nowrap">
                        {{ $button['label'] ?? 'Botón' }}
                    </x-ui.button>
                @endif
            @endforeach
        </div>
    @endif
</div>
