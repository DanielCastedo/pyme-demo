@props([
    'id' => 'crud-modal',
    'title' => 'Modal Crear',
    'subtitle' => 'Completa la información para crear un nuevo registro.',
    'action',
    'fields' => [],
    'submitText' => 'Guardar',
    'columns' => 2,
])

<div id="{{ $id }}" tabindex="-1" aria-hidden="true"
    class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm p-4 md:p-6 overflow-y-auto flex items-center justify-center">
    <div class="w-full max-w-2xl">
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-200 dark:border-slate-700 shadow-2xl overflow-hidden">

            {{-- Header --}}
            <div class="px-6 py-5 border-b border-gray-100 dark:border-slate-800">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg md:text-xl font-bold text-gray-900 dark:text-white">{{ $title }}</h3>
                        <p class="text-xs md:text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $subtitle }}</p>
                    </div>
                    <button type="button" data-modal-close="{{ $id }}"
                        class="inline-flex items-center justify-center w-9 h-9 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-slate-800 dark:hover:text-gray-200 transition shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Form --}}
            <form action="{{ $action }}" method="POST" class="px-6 py-5">
                @csrf

                <div class="grid grid-cols-1 @if($columns === 2) md:grid-cols-2 @elseif($columns === 3) md:grid-cols-3 @endif gap-4 md:gap-5">
                    @foreach ($fields as $field)
                        @php
                            $fullWidth = $field['fullWidth'] ?? false;
                            $span = $fullWidth ? 'md:col-span-2' : '';
                        @endphp
                        <div class="{{ $span }}">
                            <x-ui.form-sidebar
                                :type="$field['type'] ?? 'text'"
                                :name="$field['name'] ?? ''"
                                :label="$field['label'] ?? ''"
                                :value="$field['value'] ?? ''"
                                :placeholder="$field['placeholder'] ?? ''"
                                :required="$field['required'] ?? false"
                                :options="$field['options'] ?? []"
                                :rows="$field['rows'] ?? 3"
                                :help="$field['help'] ?? ''"
                            />
                        </div>
                    @endforeach
                </div>

                {{-- Footer --}}
                <div class="mt-6 pt-4 border-t border-gray-100 dark:border-slate-800 flex justify-end gap-2">
                    <x-ui.button
                        type="button"
                        variant="outline"
                        size="md"
                        data-modal-close="{{ $id }}"
                    >
                        Cancelar
                    </x-ui.button>

                    <x-ui.button
                        type="submit"
                        variant="primary"
                        size="md"
                    >
                        {{ $submitText }}
                    </x-ui.button>
                </div>
            </form>
        </div>
    </div>
</div>
