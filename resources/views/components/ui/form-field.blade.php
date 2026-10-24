@props([
    'type' => 'text',
    'id' => null,
    'name' => '',
    'label' => '',
    'value' => '',
    'placeholder' => '',
    'required' => false,
    'options' => [],
    'rows' => 3,
    'help' => '',
    'actionButton' => null,
])

@php
    $fieldId = $id ?? $name;

    // Campos de cantidad: solo números enteros (sin decimales). El resto de campos numéricos (precios, montos) admite decimales.
    $integerMin = ['quantity' => 1, 'min_quantity' => 1, 'stock' => 0, 'factor' => 1][$name] ?? null;
@endphp

<div>
    @if ($label)
        <div class="flex items-center justify-between mb-1.5">
            <label @if ($fieldId) for="{{ $fieldId }}" @endif class="block text-xs font-semibold text-gray-700 dark:text-gray-300">
                {{ $label }}
                @if ($required)
                    <span class="text-red-500">*</span>
                @endif
            </label>
            @if ($actionButton)
                <button type="button" id="{{ $actionButton['id'] ?? '' }}" class="text-xs font-semibold text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 dark:hover:text-emerald-300 flex items-center gap-1 transition">
                    {!! $actionButton['icon'] ?? '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>' !!}
                    <span>{{ $actionButton['label'] ?? '+ Nueva' }}</span>
                </button>
            @endif
        </div>
    @endif

    @switch($type)
        @case('text')
        @case('email')

        @case('password')
        @case('number')
            <input type="{{ $type }}" id="{{ $fieldId }}" name="{{ $name }}" value="{{ $value }}"
                placeholder="{{ $placeholder }}" @if ($required) required @endif
                @if ($integerMin !== null) step="1" min="{{ $integerMin }}" inputmode="numeric" onkeydown="if (['.', ',', 'e', 'E', '-', '+'].includes(event.key)) event.preventDefault()" @else step="any" @endif
                class="w-full px-3 h-10 text-sm rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 @error($name) border-red-500 @enderror">
        @break

        @case('textarea')
            <textarea id="{{ $fieldId }}" name="{{ $name }}" placeholder="{{ $placeholder }}" rows="{{ $rows }}"
                @if ($required) required @endif
                class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 @error($name) border-red-500 @enderror">{{ $value }}</textarea>
        @break

        @case('select')
            @php
                $selectedLabel = $options[$value] ?? '';
                $uid = 'ac_' . md5($name . uniqid());
            @endphp

            <div class="relative w-full" data-ac-wrapper="{{ $uid }}">
                <input type="hidden" id="{{ $fieldId }}" name="{{ $name }}" value="{{ $value }}" data-ac-value>

                <input type="text" value="{{ $selectedLabel }}" placeholder="Buscar..." autocomplete="off" data-ac-input
                    @if ($required) required @endif
                    class="w-full pl-3 pr-9 h-10 text-sm rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 @error($name) border-red-500 @enderror">

                {{-- Limpiar: borra el texto elegido para poder ver y buscar entre todas las opciones --}}
                <button type="button" data-ac-clear tabindex="-1" title="Limpiar" aria-label="Limpiar selección"
                    class="{{ $selectedLabel === '' ? 'hidden' : '' }} absolute right-2 top-1/2 -translate-y-1/2 w-6 h-6 inline-flex items-center justify-center rounded-md text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:text-gray-200 dark:hover:bg-slate-700 transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>

                <div data-ac-list
                    class="hidden absolute left-0 top-full mt-1 z-20 w-full max-h-56 overflow-y-auto rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 shadow-lg">
                </div>
            </div>

            <script>
                (function() {
                    const wrapper = document.querySelector('[data-ac-wrapper="{{ $uid }}"]');
                    if (!wrapper) return;

                    const input = wrapper.querySelector('[data-ac-input]');
                    const hidden = wrapper.querySelector('[data-ac-value]');
                    const list = wrapper.querySelector('[data-ac-list]');
                    const clearBtn = wrapper.querySelector('[data-ac-clear]');
                    const options = @json($options);
                    const entries = Object.entries(options);

                    let filtered = [];
                    let activeIndex = -1;

                    const norm = s => (s || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim();

                    const getItems = () => Array.from(list.querySelectorAll('[data-ac-item]'));

                    function highlight(index) {
                        const items = getItems();
                        items.forEach((el, i) => {
                            el.classList.toggle('bg-emerald-50', i === index);
                            el.classList.toggle('dark:bg-slate-700', i === index);
                        });
                        if (index >= 0 && items[index]) {
                            items[index].scrollIntoView({
                                block: 'nearest'
                            });
                        }
                    }

                    // La X solo se ve cuando hay algo escrito o elegido
                    const syncClear = () => clearBtn.classList.toggle('hidden', input.value === '');

                    function selectOption(val, lab) {
                        input.value = lab;
                        hidden.value = val;
                        hidden.dispatchEvent(new Event('change', { bubbles: true }));
                        list.classList.add('hidden');
                        activeIndex = -1;
                        syncClear();
                    }

                    function render(q = '') {
                        const qq = norm(q);
                        filtered = entries.filter(([v, l]) => !qq || norm(l).includes(qq)).slice(0, 30);
                        activeIndex = -1;

                        list.innerHTML = '';

                        if (!filtered.length) {
                            list.innerHTML =
                                `<div class="px-3 py-2 text-sm text-gray-500 dark:text-gray-400">Sin resultados</div>`;
                            list.classList.remove('hidden');
                            return;
                        }

                        filtered.forEach(([val, lab], i) => {
                            const btn = document.createElement('button');
                            btn.type = 'button';
                            btn.setAttribute('data-ac-item', '1');
                            btn.setAttribute('data-index', i);
                            btn.className =
                                'w-full text-left px-3 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-emerald-50 dark:hover:bg-slate-700';
                            btn.textContent = lab;

                            btn.addEventListener('mouseenter', () => {
                                activeIndex = i;
                                highlight(activeIndex);
                            });

                            btn.addEventListener('click', () => selectOption(val, lab));
                            list.appendChild(btn);
                        });

                        list.classList.remove('hidden');
                    }

                    // Al entrar con una opción ya elegida se muestran TODAS las opciones (no solo la elegida);
                    // en cuanto se escribe, se filtra por lo escrito.
                    input.addEventListener('focus', () => {
                        const showingChosen = hidden.value !== '' && input.value === (options[hidden.value] ?? '');
                        render(showingChosen ? '' : input.value);
                    });

                    input.addEventListener('input', () => {
                        hidden.value = '';
                        render(input.value);
                        syncClear();
                    });

                    clearBtn.addEventListener('click', () => {
                        const hadValue = hidden.value !== '';
                        input.value = '';
                        hidden.value = '';
                        if (hadValue) hidden.dispatchEvent(new Event('change', { bubbles: true }));
                        syncClear();
                        render('');
                        input.focus();
                    });

                    input.addEventListener('keydown', (e) => {
                        const isOpen = !list.classList.contains('hidden');
                        const hasItems = filtered.length > 0;

                        if (e.key === 'ArrowDown') {
                            e.preventDefault();
                            if (!isOpen) render(input.value);
                            if (!hasItems) return;
                            activeIndex = (activeIndex + 1) % filtered.length;
                            highlight(activeIndex);
                        }

                        if (e.key === 'ArrowUp') {
                            e.preventDefault();
                            if (!isOpen) render(input.value);
                            if (!hasItems) return;
                            activeIndex = activeIndex <= 0 ? filtered.length - 1 : activeIndex - 1;
                            highlight(activeIndex);
                        }

                        if (e.key === 'Enter') {
                            if (isOpen && activeIndex >= 0 && filtered[activeIndex]) {
                                e.preventDefault();
                                const [val, lab] = filtered[activeIndex];
                                selectOption(val, lab);
                            }
                        }

                        if (e.key === 'Escape') {
                            list.classList.add('hidden');
                            activeIndex = -1;
                        }
                    });

                    document.addEventListener('click', (e) => {
                        if (!wrapper.contains(e.target)) list.classList.add('hidden');
                    });

                    syncClear();
                })();
            </script>
        @break

        @case('checkbox-grid')
            <div
                class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3 max-h-48 overflow-y-auto p-2 border border-gray-200 dark:border-gray-700 rounded-lg">
                @foreach ($options as $optionValue => $optionLabel)
                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input type="checkbox" name="{{ $name }}[]" value="{{ $optionValue }}"
                            class="rounded border-gray-300 text-emerald-600 shadow-sm focus:border-emerald-300 focus:ring focus:ring-emerald-200 focus:ring-opacity-50">
                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ $optionLabel }}</span>
                    </label>
                @endforeach
            </div>
        @break

        @case('checkbox')
            <input type="checkbox" id="{{ $fieldId }}" name="{{ $name }}" value="1" @if ($value) checked @endif
                class="w-4 h-4 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 cursor-pointer">
        @break

        @case('radio')
            <div class="space-y-2">
                @foreach ($options as $optionValue => $optionLabel)
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="{{ $name }}" value="{{ $optionValue }}"
                            @if ($value == $optionValue) checked @endif
                            class="w-4 h-4 border-gray-300 text-emerald-600 focus:ring-emerald-500">
                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ $optionLabel }}</span>
                    </label>
                @endforeach
            </div>
        @break

        @case('date')
        @case('datetime-local')

        @case('time')
            <input type="{{ $type }}" id="{{ $fieldId }}" name="{{ $name }}" value="{{ $value }}"
                @if ($required) required @endif
                class="w-full px-3 h-10 text-sm rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 @error($name) border-red-500 @enderror">
        @break

        @case('file')
            <input type="file" id="{{ $fieldId }}" name="{{ $name }}" @if ($required) required @endif
                class="w-full px-3 h-10 text-sm rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 @error($name) border-red-500 @enderror">
        @break

    @endswitch

    @if ($help)
        <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-1">{{ $help }}</p>
    @endif

    @error($name)
        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
    @enderror
</div>
