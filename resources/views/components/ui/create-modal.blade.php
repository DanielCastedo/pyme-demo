@props([
    'id' => 'crud-modal',
    'title' => 'Modal Crear',
    'subtitle' => 'Completa la información para crear un nuevo registro.',
    'action',
    'fields' => [],
    'submitText' => 'Guardar',
    'columns' => 2,
    'hasFile' => false,
    'size' => 'lg', // sm (~28rem, 1-2 campos) | md (~40rem) | lg (grande, por defecto: formularios largos)
])

@php
    $maxWidth = ['sm' => '28rem', 'md' => '40rem'][$size] ?? null;

    $gridCols = match ($columns) {
        3 => 'sm:grid-cols-2 lg:grid-cols-3',
        2 => 'sm:grid-cols-2',
        default => '',
    };

    $spanCols = match ($columns) {
        3 => 'sm:col-span-2 lg:col-span-3',
        2 => 'sm:col-span-2',
        default => '',
    };
@endphp

<div id="{{ $id }}" tabindex="-1" aria-hidden="true"
    class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm sm:p-4 md:p-6 flex items-end sm:items-center justify-center">

    <div class="w-full sm:max-w-4xl sm:my-8 max-h-full sm:max-h-[90vh] flex flex-col" @if($maxWidth) style="max-width: {{ $maxWidth }};" @endif>
        <div class="bg-white dark:bg-slate-900 rounded-t-2xl sm:rounded-2xl border border-gray-200 dark:border-slate-700 shadow-2xl flex flex-col max-h-[95vh] sm:max-h-[90vh]">

            {{-- Header --}}
            <div class="px-5 sm:px-6 py-4 sm:py-5 border-b border-gray-100 dark:border-slate-800 shrink-0">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-base sm:text-lg md:text-xl font-bold text-gray-900 dark:text-white">{{ $title }}</h3>
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
            <form action="{{ $action }}" method="POST"
                @if($hasFile) enctype="multipart/form-data" @endif
                class="flex flex-col overflow-hidden" @if($maxWidth) style="overflow: visible;" @endif>
                @csrf

                {{-- Body con scroll --}}
                {{-- En el tamaño chico no se recorta el contenido: las listas desplegables (ej. Sucursal) pueden salir del modal --}}
                <div class="px-5 sm:px-6 py-4 sm:py-5 overflow-y-auto" @if($maxWidth) style="overflow: visible;" @endif>
                    <div class="grid grid-cols-1 {{ $gridCols }} gap-4 md:gap-5">
                        @foreach ($fields as $field)
                            @php
                                $span = ($field['fullWidth'] ?? false) ? $spanCols : '';
                            @endphp
                            <div class="{{ $span }} {{ $field['wrapperClass'] ?? '' }}">
                                <x-ui.form-field
                                    :type="$field['type'] ?? 'text'"
                                    :name="$field['name'] ?? ''"
                                    :label="$field['label'] ?? ''"
                                    :value="$field['value'] ?? ''"
                                    :placeholder="$field['placeholder'] ?? ''"
                                    :required="$field['required'] ?? false"
                                    :options="$field['options'] ?? []"
                                    :rows="$field['rows'] ?? 3"
                                    :help="$field['help'] ?? ''"
                                    :actionButton="$field['actionButton'] ?? null"
                                />
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Footer fijo --}}
                <div class="px-5 sm:px-6 py-4 border-t border-gray-100 dark:border-slate-800 flex justify-end gap-2 shrink-0">
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