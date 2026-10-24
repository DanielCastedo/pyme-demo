<x-app-layout>
    <div class="max-w-10xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

        <!-- Encabezado -->
        <div class="mb-5 flex flex-col sm:flex-row sm:items-center gap-3">
            <a href="{{ route('role-permisos.index') }}" class="inline-flex items-center justify-center p-1.5 rounded-md bg-white dark:bg-slate-800 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors shadow-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none" title="Volver">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
            </a>
            <div>
                <h2 class="text-xl font-bold text-gray-800 dark:text-white leading-tight">
                    Asignar Permisos
                </h2>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                    Privilegios para el rol: <strong class="text-emerald-600 dark:text-emerald-400 uppercase tracking-wide">{{ $role->name }}</strong>
                </p>
            </div>
            <div class="sm:ml-auto flex flex-col-reverse sm:flex-row gap-2">
                <a href="{{ route('role-permisos.index') }}" class="inline-flex items-center justify-center px-4 py-2 rounded-md border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 bg-white dark:bg-slate-800 hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors text-xs font-medium shadow-sm focus:ring-2 focus:ring-gray-200 dark:focus:ring-slate-700">
                    Descartar Cambios
                </a>
                <button type="submit" form="permissions-form" class="inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-md bg-emerald-600 hover:bg-emerald-700 text-white font-bold transition-colors text-xs shadow-sm focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Guardar Permisos
                </button>
            </div>
        </div>

        <!-- Formulario -->
        <form action="{{ route('role-permisos.update', $role->id) }}" method="POST" id="permissions-form">
            @csrf
            @method('PUT')

            <!-- Barra de herramientas -->
            <div class="mb-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 p-3 bg-white dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm">
                <div class="text-xs text-gray-600 dark:text-gray-300">
                    <span class="font-bold text-emerald-600 dark:text-emerald-400" id="perm-selected">0</span>
                    de <span id="perm-total">0</span> permisos seleccionados
                </div>
                <div class="flex items-center gap-2">
                    <input type="search" id="perm-filter" placeholder="Buscar módulo..."
                        class="w-full sm:w-52 px-2.5 py-1.5 text-xs rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-slate-800 text-gray-800 dark:text-gray-200 focus:ring-emerald-500 focus:border-emerald-500">
                    <button type="button" id="perm-all" class="px-3 py-1.5 text-xs font-medium rounded-md border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-800 whitespace-nowrap">Marcar todo</button>
                    <button type="button" id="perm-none" class="px-3 py-1.5 text-xs font-medium rounded-md border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-800 whitespace-nowrap">Quitar todo</button>
                </div>
            </div>

            @forelse ($groups as $area => $modules)
                <section class="mb-7" data-area>
                    <div class="flex items-center gap-2 mb-3">
                        <h3 class="text-[11px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">{{ $area }}</h3>
                        <div class="flex-1 h-px bg-gray-200 dark:bg-gray-700"></div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-3">
                        @foreach ($modules as $module => $data)
                            <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-slate-900 shadow-sm overflow-hidden" data-module data-label="{{ mb_strtolower($data['label'].' '.$module) }}">
                                <label class="flex items-center justify-between gap-2 px-3 py-2 bg-gray-50 dark:bg-slate-800/60 border-b border-gray-200 dark:border-gray-700 cursor-pointer">
                                    <span class="flex items-center gap-2 min-w-0">
                                        <input type="checkbox" data-module-toggle
                                            class="w-3.5 h-3.5 text-emerald-600 border-gray-300 rounded focus:ring-emerald-500 dark:bg-slate-800 dark:border-gray-600 cursor-pointer">
                                        <span class="font-bold text-xs text-gray-800 dark:text-gray-100 truncate">{{ $data['label'] }}</span>
                                    </span>
                                    <span class="text-[10px] font-semibold text-gray-400 dark:text-gray-500 shrink-0" data-module-count></span>
                                </label>

                                <div class="p-2.5 flex flex-wrap gap-1.5">
                                    @foreach ($data['permissions'] as $name => $label)
                                        @php $checked = in_array($name, $rolePermissions); @endphp
                                        <label title="{{ $name }}" data-chip
                                            class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md border text-[11px] font-medium cursor-pointer transition-colors select-none {{ $checked ? 'border-emerald-500 bg-emerald-50 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300 dark:border-emerald-500/50' : 'border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300' }}">
                                            <input type="checkbox" name="permissions[]" value="{{ $name }}" {{ $checked ? 'checked' : '' }}
                                                class="w-3 h-3 text-emerald-600 border-gray-300 rounded focus:ring-emerald-500 dark:bg-slate-800 dark:border-gray-600 cursor-pointer">
                                            {{ $label }}
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @empty
                <div class="p-6 mb-6 text-center text-xs text-gray-500 dark:text-gray-400 bg-white dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-gray-700">
                    No hay permisos creados en el sistema. Debes crear permisos primero.
                </div>
            @endforelse

        </form>

    </div>

    @push('scripts')
        <script>
            (function () {
                const form = document.getElementById('permissions-form');
                if (!form) return;

                const on = ['border-emerald-500', 'bg-emerald-50', 'text-emerald-800', 'dark:bg-emerald-900/30', 'dark:text-emerald-300', 'dark:border-emerald-500/50'];
                const off = ['border-gray-200', 'dark:border-gray-700', 'text-gray-600', 'dark:text-gray-300'];
                const boxes = () => [...form.querySelectorAll('input[name="permissions[]"]')];

                function paint(box) {
                    const chip = box.closest('[data-chip]');
                    on.forEach(c => chip.classList.toggle(c, box.checked));
                    off.forEach(c => chip.classList.toggle(c, !box.checked));
                }

                function refresh() {
                    const all = boxes();
                    document.getElementById('perm-total').textContent = all.length;
                    document.getElementById('perm-selected').textContent = all.filter(b => b.checked).length;

                    form.querySelectorAll('[data-module]').forEach(mod => {
                        const inner = [...mod.querySelectorAll('input[name="permissions[]"]')];
                        const done = inner.filter(b => b.checked).length;
                        const toggle = mod.querySelector('[data-module-toggle]');
                        toggle.checked = done === inner.length;
                        toggle.indeterminate = done > 0 && done < inner.length;
                        mod.querySelector('[data-module-count]').textContent = done + '/' + inner.length;
                    });
                }

                form.addEventListener('change', e => {
                    if (e.target.matches('[data-module-toggle]')) {
                        e.target.closest('[data-module]').querySelectorAll('input[name="permissions[]"]').forEach(b => {
                            b.checked = e.target.checked;
                            paint(b);
                        });
                    } else if (e.target.matches('input[name="permissions[]"]')) {
                        paint(e.target);
                    }
                    refresh();
                });

                const setAll = value => { boxes().forEach(b => { b.checked = value; paint(b); }); refresh(); };
                document.getElementById('perm-all').addEventListener('click', () => setAll(true));
                document.getElementById('perm-none').addEventListener('click', () => setAll(false));

                document.getElementById('perm-filter').addEventListener('input', e => {
                    const q = e.target.value.trim().toLowerCase();
                    form.querySelectorAll('[data-module]').forEach(m => m.classList.toggle('hidden', q !== '' && !m.dataset.label.includes(q)));
                    form.querySelectorAll('[data-area]').forEach(a => a.classList.toggle('hidden', !a.querySelector('[data-module]:not(.hidden)')));
                });

                refresh();
            })();
        </script>
    @endpush
</x-app-layout>
