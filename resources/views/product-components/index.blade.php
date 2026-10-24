<x-app-layout>
    <div class="max-w-10xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        @if (session('success'))
            <x-ui.alert type="success" :message="session('success')" />
        @endif

        @if ($errors->any())
            <x-ui.alert type="error">
                <ul class="list-disc pl-4 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-ui.alert>
        @endif

        @php
            $buttons = [
                [
                    'id' => 'modal-toggle-button',
                    'can' => 'crear product-components',
                    'label' => 'Nuevo Componente',
                    'variant' => 'primary',
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />',
                ],
            ];
        @endphp

        <x-ui.page-header category="Inventario" title="Componentes de Producto (Kits)"
            description="Arma productos tipo kit a partir de otros productos ya existentes." :buttons="$buttons" />

        <div class="mb-5">
            <x-ui.search-box placeholder="Buscar por producto o componente..." filterName="buscar" />
        </div>

        @php
            $columns = [
                ['key' => 'product.name', 'label' => 'Producto (Kit)'],
                ['key' => 'component.name', 'label' => 'Componente'],
                ['key' => 'quantity', 'label' => 'Cantidad'],
            ];

            $actions = [
                ['type' => 'view', 'route' => 'product-components.show'],
                ['type' => 'edit', 'route' => 'product-components.edit'],
                ['type' => 'delete', 'route' => 'product-components.destroy', 'confirm' => '¿Eliminar este componente?'],
            ];
        @endphp

        <x-ui.data-table :rows="$productComponents" :columns="$columns" :actions="$actions" :paginator="$productComponents"
            emptyText="No hay componentes registrados." />

        {{-- Modal: un kit y varios componentes a la vez --}}
        <div x-data="kitForm(@js([
                "open" => $errors->any() && old("components") !== null,
                "kitId" => old("product_id") ? (int) old("product_id") : null,
                "rows" => collect(old("components", []))->map(fn ($c) => ["id" => (int) ($c["component_product_id"] ?? 0) ?: null, "qty" => (int) ($c["quantity"] ?? 1) ?: 1])->values(),
                "products" => $products->map(fn ($p) => ["id" => (int) $p->id, "name" => $p->name, "sku" => $p->sku])->values(),
                "kits" => $kitIds,
                "parts" => $partIds,
                "existing" => $existing,
            ]))"
            @open-kit-modal.window="openModal()" @keydown.escape.window="open = false">

            <div x-show="open" x-cloak id="crud-modal" tabindex="-1" aria-hidden="true"
                class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm sm:p-4 md:p-6 flex items-end sm:items-center justify-center">
                <div class="w-full sm:max-w-2xl sm:my-8 max-h-full sm:max-h-[90vh] flex flex-col">
                    <div class="bg-white dark:bg-slate-900 rounded-t-2xl sm:rounded-2xl border border-gray-200 dark:border-slate-700 shadow-2xl flex flex-col max-h-[95vh] sm:max-h-[90vh]">

                        <div class="px-5 sm:px-6 py-4 sm:py-5 border-b border-gray-100 dark:border-slate-800 shrink-0">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <h3 class="text-base sm:text-lg md:text-xl font-bold text-gray-900 dark:text-white">Agregar componentes a un kit</h3>
                                    <p class="text-xs md:text-sm text-gray-500 dark:text-gray-400 mt-1">Elige el kit y suma todas sus partes; se guardan juntas.</p>
                                </div>
                                <button type="button" @click="open = false" aria-label="Cerrar"
                                    class="inline-flex items-center justify-center w-9 h-9 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-slate-800 dark:hover:text-gray-200 transition shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                </button>
                            </div>
                        </div>

                        <form action="{{ route('product-components.store') }}" method="POST" class="flex flex-col overflow-hidden" @submit="if (! canSubmit) $event.preventDefault()">
                            @csrf
                            <div class="px-5 sm:px-6 py-4 sm:py-5 overflow-y-auto space-y-5">

                                {{-- Kit --}}
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Producto (Kit) <span class="text-red-500">*</span></label>
                                    <input type="hidden" name="product_id" :value="kitId ?? ''">
                                    <div class="relative">
                                        <input type="text" x-model="kitQ" placeholder="Buscar el producto que será el kit..." autocomplete="off"
                                            @focus="kitOpen = true" @input="kitId = null; kitOpen = true" @keydown.escape="if (kitOpen) { kitOpen = false; $event.stopPropagation(); }"
                                            class="w-full px-3 pr-9 h-10 text-sm rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                                        <button type="button" x-show="kitQ" @click="kitId = null; kitQ = ''; kitOpen = true" title="Limpiar" aria-label="Limpiar selección"
                                            class="absolute right-2 top-1/2 -translate-y-1/2 w-6 h-6 inline-flex items-center justify-center rounded-md text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-slate-700">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                        </button>
                                        <div x-show="kitOpen" x-cloak @click.outside="kitOpen = false"
                                            class="absolute z-20 mt-1 w-full max-h-56 overflow-y-auto rounded-lg border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-lg">
                                            <template x-for="p in kitOptions" :key="p.id">
                                                <button type="button" @click="chooseKit(p)" class="w-full text-left px-3 py-2 text-sm hover:bg-emerald-50 dark:hover:bg-slate-700 text-gray-900 dark:text-gray-100">
                                                    <span x-text="p.name"></span>
                                                    <span class="text-[11px] text-gray-400 ml-1" x-text="p.sku"></span>
                                                    <span x-show="kits.includes(p.id)" class="ml-1 text-[10px] font-semibold text-emerald-600 dark:text-emerald-400">ya es kit</span>
                                                </button>
                                            </template>
                                            <p x-show="kitOptions.length === 0" class="px-3 py-3 text-xs text-gray-500 dark:text-gray-400">Sin resultados. (Un producto que ya es parte de otro kit no puede ser kit.)</p>
                                        </div>
                                    </div>
                                    <template x-if="kitId && (existing[kitId] || []).length">
                                        <p class="mt-1.5 text-[11px] text-sky-700 dark:text-sky-400">
                                            Ya lleva: <span x-text="(existing[kitId] || []).map(e => e.qty + ' × ' + e.name).join(', ')"></span>. Aquí agregas más partes.
                                        </p>
                                    </template>
                                </div>

                                {{-- Componentes --}}
                                <div>
                                    <div class="flex items-center justify-between mb-1.5">
                                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300">Componentes <span class="text-red-500">*</span></label>
                                        <span class="text-[11px] text-gray-500 dark:text-gray-400">Cantidad = cuántas lleva 1 kit</span>
                                    </div>

                                    <div class="space-y-2">
                                        <template x-for="(row, i) in rows" :key="row.key">
                                            <div class="rounded-lg border border-gray-200 dark:border-slate-700 p-2.5 flex flex-col sm:flex-row sm:items-center gap-2">
                                                <div class="relative flex-1 min-w-0">
                                                    <input type="hidden" :name="`components[${i}][component_product_id]`" :value="row.id ?? ''">
                                                    <input type="text" x-model="row.q" placeholder="Buscar el componente..." autocomplete="off"
                                                        @focus="row.open = true" @input="row.id = null; row.open = true" @keydown.escape="if (row.open) { row.open = false; $event.stopPropagation(); }"
                                                        class="w-full px-3 h-10 text-sm rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                                                    <div x-show="row.open" x-cloak @click.outside="row.open = false"
                                                        class="absolute z-20 mt-1 w-full max-h-52 overflow-y-auto rounded-lg border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-lg">
                                                        <template x-for="p in partOptions(i)" :key="p.id">
                                                            <button type="button" @click="row.id = p.id; row.q = p.name; row.open = false"
                                                                class="w-full text-left px-3 py-2 text-sm hover:bg-emerald-50 dark:hover:bg-slate-700 text-gray-900 dark:text-gray-100">
                                                                <span x-text="p.name"></span> <span class="text-[11px] text-gray-400 ml-1" x-text="p.sku"></span>
                                                            </button>
                                                        </template>
                                                        <p x-show="partOptions(i).length === 0" class="px-3 py-3 text-xs text-gray-500 dark:text-gray-400">Sin resultados (no se muestran kits ni productos ya elegidos).</p>
                                                    </div>
                                                </div>

                                                <div class="flex items-center gap-2 justify-between sm:justify-end">
                                                    <div class="inline-flex items-center">
                                                        <button type="button" @click="row.qty = Math.max(1, (parseInt(row.qty) || 1) - 1)" aria-label="Menos"
                                                            class="w-9 h-10 rounded-l-lg border border-gray-300 dark:border-slate-600 bg-gray-50 dark:bg-slate-800 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-slate-700 font-bold">−</button>
                                                        <input type="number" step="1" min="1" inputmode="numeric" x-model.number="row.qty" :name="`components[${i}][quantity]`"
                                                            @keydown="if (['.', ',', 'e', 'E', '-', '+'].includes($event.key)) $event.preventDefault()"
                                                            @change="row.qty = Math.max(1, parseInt(row.qty) || 1)"
                                                            class="w-14 h-10 text-center text-sm border-y border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white focus:outline-none">
                                                        <button type="button" @click="row.qty = (parseInt(row.qty) || 0) + 1" aria-label="Más"
                                                            class="w-9 h-10 rounded-r-lg border border-gray-300 dark:border-slate-600 bg-gray-50 dark:bg-slate-800 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-slate-700 font-bold">+</button>
                                                    </div>
                                                    <button type="button" @click="removeRow(i)" x-show="rows.length > 1" title="Quitar" aria-label="Quitar componente"
                                                        class="w-9 h-10 inline-flex items-center justify-center rounded-lg text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                                    </button>
                                                </div>
                                            </div>
                                        </template>
                                    </div>

                                    <button type="button" @click="addRow()"
                                        class="mt-2 inline-flex items-center gap-1.5 px-3 h-9 text-xs font-semibold rounded-lg border border-dashed border-emerald-400 text-emerald-700 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-900/20">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                        Agregar otro componente
                                    </button>
                                </div>

                                {{-- Resumen --}}
                                <div x-show="summary" x-cloak class="rounded-lg bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 px-3 py-2 text-xs text-emerald-800 dark:text-emerald-300">
                                    <span class="font-semibold" x-text="kitName || 'El kit'"></span> se arma con: <span x-text="summary"></span>.
                                </div>
                            </div>

                            <div class="px-5 sm:px-6 py-3 sm:py-4 border-t border-gray-100 dark:border-slate-800 flex items-center justify-end gap-2 shrink-0">
                                <button type="button" @click="open = false"
                                    class="px-4 h-10 text-sm font-semibold rounded-lg text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-800">Cancelar</button>
                                <button type="submit" :disabled="! canSubmit"
                                    class="px-5 h-10 text-sm font-semibold rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed">
                                    <span x-text="validRows.length > 1 ? 'Guardar ' + validRows.length + ' componentes' : 'Guardar'"></span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            // Abre el modal desde el botón "Nuevo Componente" del encabezado.
            document.getElementById('modal-toggle-button')?.addEventListener('click', () => window.dispatchEvent(new CustomEvent('open-kit-modal')));

            // Formulario de varios componentes: un kit y una lista de filas (producto + cantidad).
            function kitForm(cfg) {
                let seq = 0;
                const newRow = (row = {}) => ({ key: ++seq, id: row.id ?? null, qty: row.qty ?? 1, q: '', open: false });
                const nameOf = (products, id) => products.find(p => p.id === id)?.name ?? '';
                const norm = (t) => (t || '').toString().toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
                const matches = (p, q) => !q || norm(p.name).includes(norm(q)) || norm(p.sku).includes(norm(q));

                return {
                    open: cfg.open, products: cfg.products, kits: cfg.kits, parts: cfg.parts, existing: cfg.existing,
                    kitId: cfg.kitId, kitQ: nameOf(cfg.products, cfg.kitId), kitOpen: false,
                    rows: (cfg.rows.length ? cfg.rows : [{}]).map(r => { const row = newRow(r); row.q = nameOf(cfg.products, row.id); return row; }),

                    openModal() { this.open = true; },
                    addRow() { this.rows.push(newRow()); },
                    removeRow(i) { this.rows.splice(i, 1); },
                    chooseKit(p) {
                        this.kitId = p.id; this.kitQ = p.name; this.kitOpen = false;
                        // Si algún componente elegido ya no es válido para este kit, se limpia esa fila.
                        this.rows.forEach(r => { if (r.id && !this.isPartAllowed(r.id, r)) { r.id = null; r.q = ''; } });
                    },
                    get kitName() { return nameOf(this.products, this.kitId); },
                    // Un producto que ya es parte de otro kit no puede ser kit (un kit no tiene stock propio).
                    get kitOptions() { return this.products.filter(p => !this.parts.includes(p.id) && matches(p, this.kitQ)).slice(0, 60); },
                    // Un componente no puede ser el propio kit, otro kit, ni repetirse ni estar ya en este kit.
                    isPartAllowed(id, row) {
                        return id !== this.kitId && !this.kits.includes(id)
                            && !(this.existing[this.kitId] || []).some(e => e.id === id)
                            && !this.rows.some(r => r !== row && r.id === id);
                    },
                    partOptions(i) {
                        const row = this.rows[i];
                        return this.products.filter(p => this.isPartAllowed(p.id, row) && matches(p, row.q)).slice(0, 60);
                    },
                    get validRows() { return this.rows.filter(r => r.id && (parseInt(r.qty) || 0) >= 1); },
                    get canSubmit() { return !!this.kitId && this.validRows.length > 0 && this.validRows.length === this.rows.length; },
                    get summary() { return this.validRows.map(r => (parseInt(r.qty) || 1) + ' × ' + nameOf(this.products, r.id)).join(' + '); },
                };
            }
        </script>
    @endpush
</x-app-layout>
