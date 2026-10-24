@props([
    'title' => 'Editar',
    'category' => 'Configuración',
    'backRoute' => '',
    'action' => '',
    'method' => 'POST',
    'fields' => [],
    'submitText' => 'Guardar',
    'columns' => 2,
    'hasFile' => false, // ← nuevo prop
])

<x-app-layout>
    <div class="max-w-10xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- Encabezado --}}
        <div class="mb-6 flex items-center gap-3">
            @if ($backRoute)
                <a href="{{ $backRoute }}"
                    class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-white dark:bg-slate-800 border border-gray-200 dark:border-gray-700 text-gray-500 hover:text-emerald-600 hover:bg-emerald-50 dark:hover:bg-slate-700 transition-colors shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
            @endif
            <div>
                @if ($category)
                    <span
                        class="text-[10px] font-bold tracking-wider text-emerald-600 dark:text-emerald-400 uppercase">{{ $category }}</span>
                @endif
                <h2 class="text-lg font-bold text-gray-800 dark:text-white">{{ $title }}</h2>
            </div>
        </div>

        {{-- Alertas de error --}}
        @if ($errors->any())
            <x-ui.alert type="error" class="mb-5">
                <ul class="list-disc pl-5 space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-ui.alert>
        @endif

        {{-- Formulario --}}
        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800">
            <form action="{{ $action }}" method="POST"
                @if ($hasFile) enctype="multipart/form-data" @endif class="p-5 md:p-6">
                @csrf
                @if ($method === 'PUT')
                    @method('PUT')
                @elseif($method === 'PATCH')
                    @method('PATCH')
                @endif

                <div class="grid grid-cols-1 gap-6">
                    @foreach ($fields as $field)
                        @php
                            $type = $field['type'] ?? 'text';
                            $name = $field['name'] ?? '';
                            $label = $field['label'] ?? ucfirst($name);
                            $required = $field['required'] ?? false;
                            $placeholder = $field['placeholder'] ?? '';
                            $value = $field['value'] ?? '';
                            $options = $field['options'] ?? [];
                            $help = $field['help'] ?? '';
                            $gridCols = $field['grid_cols'] ?? 'grid-cols-1 sm:grid-cols-2 md:grid-cols-3';
                            $preview = $field['preview'] ?? null; // URL de previsualización
                        @endphp

                        <div class="w-full">
                            @if (!empty($field['actionButton']))
                                <div class="flex items-center justify-between mb-2">
                                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300">
                                        {{ $label }}
                                        @if ($required)
                                            <span class="text-red-500">*</span>
                                        @endif
                                    </label>
                                    <button type="button" id="{{ $field['actionButton']['id'] ?? '' }}" class="text-xs font-semibold text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 dark:hover:text-emerald-300 flex items-center gap-1 transition">
                                        {!! $field['actionButton']['icon'] ?? '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>' !!}
                                        <span>{{ $field['actionButton']['label'] ?? '+ Nueva' }}</span>
                                    </button>
                                </div>
                            @else
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                    {{ $label }}
                                    @if ($required)
                                        <span class="text-red-500">*</span>
                                    @endif
                                </label>
                            @endif

                            @switch($type)
                                @case('text')
                                @case('email')

                                @case('password')
                                @case('number')
                                    <input type="{{ $type }}" name="{{ $name }}"
                                        value="{{ old($name, $value) }}" placeholder="{{ $placeholder }}"
                                        @if ($required) required @endif
                                        @if ($type === 'number') @php $intMin = ['quantity' => 1, 'min_quantity' => 1, 'stock' => 0, 'factor' => 1][$name] ?? null; @endphp @if ($intMin !== null) step="1" min="{{ $intMin }}" inputmode="numeric" onkeydown="if (['.', ',', 'e', 'E', '-', '+'].includes(event.key)) event.preventDefault()" @else step="any" @endif @endif
                                        class="w-full h-9 px-3 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-emerald-500 @error($name) border-red-500 @enderror">
                                @break

                                @case('textarea')
                                    <textarea name="{{ $name }}" placeholder="{{ $placeholder }}" rows="{{ $field['rows'] ?? 3 }}"
                                        @if ($required) required @endif
                                        class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-emerald-500 @error($name) border-red-500 @enderror">{{ old($name, $value) }}</textarea>
                                @break

                                @case('select')
                                    @php
                                        $currentValue = old($name, $value);
                                        $selectedLabel = $options[$currentValue] ?? '';
                                        $emptyText = $field['emptyText'] ?? '-- Seleccionar --';
                                        $uid = 'ac_' . md5($name . uniqid());
                                    @endphp

                                    <div class="relative w-full" data-ac-wrapper="{{ $uid }}">
                                        <input type="hidden" name="{{ $name }}" value="{{ $currentValue }}"
                                            data-ac-value>

                                        <input type="text" value="{{ $selectedLabel }}" placeholder="Buscar..."
                                            autocomplete="off" data-ac-input @if ($required) required @endif
                                            class="w-full h-9 px-3 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-emerald-500 @error($name) border-red-500 @enderror">

                                        <div data-ac-list
                                            class="hidden absolute left-0 top-full mt-1 z-20 w-full max-h-56 overflow-y-auto rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-slate-800 shadow-lg">
                                        </div>
                                    </div>

                                    <script>
                                        (function() {
                                            const wrapper = document.querySelector('[data-ac-wrapper="{{ $uid }}"]');
                                            if (!wrapper) return;

                                            const input = wrapper.querySelector('[data-ac-input]');
                                            const hidden = wrapper.querySelector('[data-ac-value]');
                                            const list = wrapper.querySelector('[data-ac-list]');
                                            const options = @json($options);
                                            const entries = Object.entries(options);
                                            const emptyText = @json($emptyText);

                                            const norm = s => (s || '')
                                                .toLowerCase()
                                                .normalize('NFD')
                                                .replace(/[\u0300-\u036f]/g, '')
                                                .trim();

                                            function render(q = '') {
                                                const qq = norm(q);
                                                const filtered = entries
                                                    .filter(([v, l]) => !qq || norm(l).includes(qq))
                                                    .slice(0, 30);

                                                list.innerHTML = '';

                                                // Opción vacía
                                                const clearBtn = document.createElement('button');
                                                clearBtn.type = 'button';
                                                clearBtn.className =
                                                    'w-full text-left px-3 py-2 text-sm text-gray-500 dark:text-gray-300 hover:bg-emerald-50 dark:hover:bg-slate-700';
                                                clearBtn.textContent = emptyText;
                                                clearBtn.onclick = () => {
                                                    input.value = '';
                                                    hidden.value = '';
                                                    list.classList.add('hidden');
                                                };
                                                list.appendChild(clearBtn);

                                                if (!filtered.length) {
                                                    const noRes = document.createElement('div');
                                                    noRes.className = 'px-3 py-2 text-sm text-gray-500 dark:text-gray-400';
                                                    noRes.textContent = 'Sin resultados';
                                                    list.appendChild(noRes);
                                                    list.classList.remove('hidden');
                                                    return;
                                                }

                                                filtered.forEach(([val, lab]) => {
                                                    const btn = document.createElement('button');
                                                    btn.type = 'button';
                                                    btn.className =
                                                        'w-full text-left px-3 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-emerald-50 dark:hover:bg-slate-700';
                                                    btn.textContent = lab;
                                                    btn.onclick = () => {
                                                        input.value = lab;
                                                        hidden.value = val;
                                                        list.classList.add('hidden');
                                                    };
                                                    list.appendChild(btn);
                                                });

                                                list.classList.remove('hidden');
                                            }

                                            input.addEventListener('focus', () => render(input.value));
                                            input.addEventListener('input', () => {
                                                hidden.value = '';
                                                render(input.value);
                                            });

                                            document.addEventListener('click', (e) => {
                                                if (!wrapper.contains(e.target)) list.classList.add('hidden');
                                            });
                                        })();
                                    </script>
                                @break

                                @case('checkbox_group')
                                    <div
                                        class="grid {{ $gridCols }} gap-3 p-4 bg-gray-50 dark:bg-slate-800/50 rounded-lg border border-gray-200 dark:border-gray-700">
                                        @foreach ($options as $optionValue => $optionLabel)
                                            @php
                                                $checked =
                                                    is_array(old($name, $value)) &&
                                                    in_array($optionValue, old($name, $value));
                                            @endphp
                                            <label
                                                class="flex items-start gap-2.5 cursor-pointer p-1.5 hover:bg-white dark:hover:bg-slate-800 rounded transition-colors">
                                                <input type="checkbox" name="{{ $name }}[]"
                                                    value="{{ $optionValue }}"
                                                    @if ($checked) checked @endif
                                                    class="mt-0.5 w-4 h-4 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 cursor-pointer">
                                                <span
                                                    class="text-sm text-gray-700 dark:text-gray-300">{{ $optionLabel }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                @break

                                @case('file')
                                    {{-- Previsualización actual --}}
                                    @if ($preview)
                                        <div class="mb-3" id="preview-container-{{ $name }}">
                                            @php $ext = strtolower(pathinfo($preview, PATHINFO_EXTENSION)); @endphp

                                            @if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']))
                                                {{-- Imagen --}}
                                                <div class="relative group inline-block cursor-zoom-in" onclick="openImagePreview(document.getElementById('preview-img-{{ $name }}').src, '{{ $label }}')">
                                                    <img id="preview-img-{{ $name }}" src="{{ $preview }}"
                                                        alt="Previsualización"
                                                        class="w-full max-w-sm rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm object-cover max-h-56 group-hover:ring-2 group-hover:ring-emerald-500 group-hover:opacity-95 transition-all">
                                                    <div class="absolute bottom-2 right-2 px-2.5 py-1 rounded-lg bg-slate-900/80 text-white text-xs font-semibold backdrop-blur-sm flex items-center gap-1 shadow transition pointer-events-none">
                                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m3-3H7" />
                                                        </svg>
                                                        Ver más grande
                                                    </div>
                                                </div>
                                            @elseif($ext === 'pdf')
                                                {{-- PDF --}}
                                                <div
                                                    class="flex items-center gap-3 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                                                    <span class="text-2xl">📄</span>
                                                    <div>
                                                        <p class="text-xs font-semibold text-red-700 dark:text-red-400">Archivo
                                                            PDF actual</p>
                                                        <a href="{{ $preview }}" target="_blank"
                                                            class="text-xs text-red-600 dark:text-red-400 hover:underline">
                                                            Ver PDF →
                                                        </a>
                                                    </div>
                                                </div>
                                            @elseif(in_array($ext, ['doc', 'docx']))
                                                {{-- Word --}}
                                                <div
                                                    class="flex items-center gap-3 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                                                    <span class="text-2xl">📝</span>
                                                    <div>
                                                        <p class="text-xs font-semibold text-blue-700 dark:text-blue-400">
                                                            Archivo Word actual</p>
                                                        <a href="{{ $preview }}" target="_blank"
                                                            class="text-xs text-blue-600 dark:text-blue-400 hover:underline">
                                                            Descargar →
                                                        </a>
                                                    </div>
                                                </div>
                                            @elseif(in_array($ext, ['xls', 'xlsx', 'csv']))
                                                {{-- Excel --}}
                                                <div
                                                    class="flex items-center gap-3 p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                                                    <span class="text-2xl">📊</span>
                                                    <div>
                                                        <p class="text-xs font-semibold text-green-700 dark:text-green-400">
                                                            Archivo Excel actual</p>
                                                        <a href="{{ $preview }}" target="_blank"
                                                            class="text-xs text-green-600 dark:text-green-400 hover:underline">
                                                            Descargar →
                                                        </a>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    @endif

                                    {{-- Input file --}}
                                    <input type="file" name="{{ $name }}" id="input-{{ $name }}"
                                        @if ($required) required @endif
                                        class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 @error($name) border-red-500 @enderror">

                                    {{-- Preview en tiempo real para imágenes --}}
                                    @if ($preview)
                                        <script>
                                            document.getElementById('input-{{ $name }}').addEventListener('change', function(e) {
                                                const file = e.target.files[0];
                                                if (!file) return;
                                                if (file.type.startsWith('image/')) {
                                                    const reader = new FileReader();
                                                    reader.onload = function(ev) {
                                                        const img = document.getElementById('preview-img-{{ $name }}');
                                                        if (img) img.src = ev.target.result;
                                                    };
                                                    reader.readAsDataURL(file);
                                                }
                                            });
                                        </script>
                                    @endif
                                @break
                            @endswitch

                            @if ($help)
                                <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-1">{{ $help }}</p>
                            @endif
                            @error($name)
                                <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    @endforeach
                </div>

                {{-- Slot extra (archivo actual, etc.) --}}
                {{ $slot }}

                {{-- Botones --}}
                <div
                    class="mt-6 flex flex-col-reverse sm:flex-row justify-end gap-2 pt-4 border-t border-gray-100 dark:border-gray-800">
                    @if ($backRoute)
                        <a href="{{ $backRoute }}"
                            class="inline-flex justify-center items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-xs font-semibold rounded-lg text-gray-700 dark:text-gray-300 bg-white dark:bg-slate-800 hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors w-full sm:w-auto">
                            Cancelar
                        </a>
                    @endif
                    <button type="submit"
                        class="inline-flex justify-center items-center gap-2 px-5 py-2 bg-gray-500 hover:bg-gray-600 text-white text-xs font-semibold rounded-lg shadow-sm transition-colors w-full sm:w-auto">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        {{ $submitText }}
                    </button>
                </div>
            </form>
        </div>

    </div>
</x-app-layout>

<x-ui.image-preview-modal />
