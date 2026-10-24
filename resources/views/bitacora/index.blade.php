<x-app-layout>
    <div class="max-w-10xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        @php
            $headerButtons = [
                [
                    'route' => route('bitacora.pdf', request()->query()),
                    'label' => 'Imprimir / Exportar PDF',
                    'variant' => 'danger',
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />',
                    'target' => '_blank',
                ],
            ];
        @endphp

        <x-ui.page-header category="Seguridad y Auditoría" title="Bitácora de Actividades"
            description="Supervisión cronológica y auditoría forense de todas las operaciones realizadas por los usuarios en el sistema." :buttons="$headerButtons" />

        {{-- ── 1. Filtros avanzados tipo reporte ── --}}
        @php
            $fieldClass = 'w-full h-10 px-3 text-sm rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500';
            $labelClass = 'block text-[11px] font-semibold text-gray-600 dark:text-gray-400 mb-1';
            $selected = fn ($a, $b) => (string) $a === (string) $b ? 'selected' : '';
        @endphp

        <form method="GET" action="{{ route('bitacora.index') }}" id="bitacora-filters"
            class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-gray-700 rounded-xl p-4 mb-5 shadow-sm">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                {{-- Desde --}}
                <div>
                    <label class="{{ $labelClass }}">Desde</label>
                    <input type="date" name="from" value="{{ $f['from'] ?? '' }}" class="{{ $fieldClass }}">
                </div>

                {{-- Hasta --}}
                <div>
                    <label class="{{ $labelClass }}">Hasta</label>
                    <input type="date" name="to" value="{{ $f['to'] ?? '' }}" class="{{ $fieldClass }}">
                </div>

                {{-- Usuario --}}
                <div>
                    <label class="{{ $labelClass }}">Usuario</label>
                    <select name="user_id" class="{{ $fieldClass }}">
                        <option value="">Todos los usuarios</option>
                        @foreach ($filterData['users'] as $id => $name)
                            <option value="{{ $id }}" {{ $selected($f['user_id'] ?? '', $id) }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Módulo --}}
                <div>
                    <label class="{{ $labelClass }}">Módulo / Entidad</label>
                    <select name="log_name" class="{{ $fieldClass }}">
                        <option value="">Todos los módulos</option>
                        @foreach ($filterData['logNames'] as $log)
                            <option value="{{ $log }}" {{ $selected($f['log_name'] ?? '', $log) }}>{{ ucfirst($log) }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Tipo de Evento --}}
                <div>
                    <label class="{{ $labelClass }}">Acción / Evento</label>
                    <select name="event" class="{{ $fieldClass }}">
                        <option value="">Todas las acciones</option>
                        @foreach ($filterData['events'] as $key => $label)
                            <option value="{{ $key }}" {{ $selected($f['event'] ?? '', $key) }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Buscar término libre con sugerencias interactivas al clic o escribir --}}
                <div class="relative" x-data="{
                    open: false,
                    query: @js($f['buscar'] ?? ''),
                    allSuggestions: @js($filterData['suggestions'] ?? []),
                    get filtered() {
                        if (!this.query || !this.query.trim()) {
                            return this.allSuggestions.slice(0, 15);
                        }
                        const q = this.query.toLowerCase().trim();
                        return this.allSuggestions.filter(s => s.text.toLowerCase().includes(q)).slice(0, 15);
                    },
                    selectSuggestion(item) {
                        this.query = item.text;
                        this.open = false;
                        $nextTick(() => {
                            document.getElementById('bitacora-filters').submit();
                        });
                    }
                }" @click.outside="open = false">
                    <label class="{{ $labelClass }}">Buscar texto</label>
                    <div class="relative">
                        <input type="text" name="buscar" x-model="query"
                            @focus="open = true"
                            @click="open = true"
                            @input="open = true"
                            @keydown.enter="$el.closest('form').submit()"
                            placeholder="Buscar cliente, producto, usuario, acción..."
                            autocomplete="off"
                            class="{{ $fieldClass }} pr-8">

                        <button type="button" x-show="query" @click="query = ''; open = true;"
                            class="absolute right-2 top-2.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    {{-- Menú de sugerencias automáticas previas y al escribir --}}
                    <div x-show="open && filtered.length > 0" x-transition
                        class="absolute z-50 left-0 right-0 mt-1 bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-lg shadow-xl overflow-hidden max-h-60 overflow-y-auto">
                        <div class="p-1.5 text-[10px] uppercase font-bold text-gray-400 dark:text-gray-500 tracking-wider border-b border-gray-100 dark:border-slate-700/60 flex justify-between items-center bg-gray-50 dark:bg-slate-900/60">
                            <span>Sugerencias de búsqueda</span>
                            <span class="text-[9px] font-normal lowercase">clic para aplicar</span>
                        </div>
                        <template x-for="(item, index) in filtered" :key="index">
                            <div @click="selectSuggestion(item)"
                                class="px-3 py-2 text-xs text-gray-800 dark:text-gray-200 hover:bg-emerald-50 dark:hover:bg-slate-700/60 cursor-pointer flex items-center justify-between border-b border-gray-50 dark:border-slate-700/30 last:border-0 transition">
                                <span class="truncate font-medium" x-text="item.text"></span>
                                <span class="ml-2 px-1.5 py-0.5 rounded text-[10px] font-semibold bg-gray-100 text-gray-600 dark:bg-slate-900 dark:text-gray-400" x-text="item.type"></span>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Orden --}}
                <div>
                    <label class="{{ $labelClass }}">Ordenar por</label>
                    <select name="order" class="{{ $fieldClass }}">
                        <option value="desc" {{ $selected($f['order'] ?? 'desc', 'desc') }}>Más recientes primero</option>
                        <option value="asc" {{ $selected($f['order'] ?? 'desc', 'asc') }}>Más antiguos primero</option>
                    </select>
                </div>

                {{-- Registros por página --}}
                <div>
                    <label class="{{ $labelClass }}">Mostrar por página</label>
                    <select name="per_page" class="{{ $fieldClass }}">
                        <option value="20" {{ $selected($f['per_page'] ?? 20, 20) }}>20 registros</option>
                        <option value="50" {{ $selected($f['per_page'] ?? 20, 50) }}>50 registros</option>
                        <option value="100" {{ $selected($f['per_page'] ?? 20, 100) }}>100 registros</option>
                    </select>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap items-center gap-2 pt-3 border-t border-gray-100 dark:border-slate-800">
                <x-ui.button type="submit" variant="primary">
                    <svg class="w-4 h-4 mr-1.5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                    Aplicar filtros
                </x-ui.button>

                <a href="{{ route('bitacora.index') }}">
                    <x-ui.button type="button" variant="outline">Limpiar</x-ui.button>
                </a>

                <a href="{{ route('bitacora.pdf', request()->query()) }}" target="_blank">
                    <x-ui.button type="button" variant="danger">
                        <svg class="w-4 h-4 mr-1.5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Exportar Bitácora PDF
                    </x-ui.button>
                </a>

                {{-- Presets rápidos de fechas al estilo del reporte financiero --}}
                <div class="flex flex-wrap gap-1.5 ml-auto text-[11px]">
                    <a href="{{ route('bitacora.index', array_merge(request()->except(['page']), ['from' => now()->toDateString(), 'to' => now()->toDateString()])) }}"
                        class="px-2.5 py-1 rounded-full border border-gray-300 dark:border-slate-600 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-800">
                        Hoy
                    </a>
                    <a href="{{ route('bitacora.index', array_merge(request()->except(['page']), ['from' => now()->subDays(6)->toDateString(), 'to' => now()->toDateString()])) }}"
                        class="px-2.5 py-1 rounded-full border border-gray-300 dark:border-slate-600 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-800">
                        7 días
                    </a>
                    <a href="{{ route('bitacora.index', array_merge(request()->except(['page']), ['from' => now()->startOfMonth()->toDateString(), 'to' => now()->toDateString()])) }}"
                        class="px-2.5 py-1 rounded-full border border-gray-300 dark:border-slate-600 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-800">
                        Este mes
                    </a>
                    <a href="{{ route('bitacora.index', array_merge(request()->except(['page']), ['from' => now()->startOfYear()->toDateString(), 'to' => now()->toDateString()])) }}"
                        class="px-2.5 py-1 rounded-full border border-gray-300 dark:border-slate-600 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-800">
                        Este año
                    </a>
                    <a href="{{ route('bitacora.index', array_merge(request()->except(['page']), ['from' => '', 'to' => ''])) }}"
                        class="px-2.5 py-1 rounded-full border border-gray-300 dark:border-slate-600 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-800">
                        Histórico completo
                    </a>
                </div>
            </div>
        </form>

        {{-- ── 2. Tarjetas de Resumen KPI ── --}}
        <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-3 mb-5">
            <x-ui.stat-card label="Total Eventos" :value="$summary['total']" color="sky" />
            <x-ui.stat-card label="Creaciones" :value="$summary['created']" color="emerald" />
            <x-ui.stat-card label="Modificaciones" :value="$summary['updated']" color="amber" />
            <x-ui.stat-card label="Eliminaciones" :value="$summary['deleted']" color="red" />
            <x-ui.stat-card label="Accesos y Sesiones" :value="$summary['auth']" color="violet" />
            <x-ui.stat-card label="Usuarios Activos" :value="$summary['users_count']" color="gray" />
        </div>

        <p class="-mt-2 mb-5 text-[11px] text-gray-500 dark:text-gray-400">
            Mostrando <strong>{{ $activities->total() }}</strong> registro(s) encontrados
            @if(!empty($f['from']) || !empty($f['to']))
                entre <strong>{{ $f['from'] ?: 'el inicio' }}</strong> y <strong>{{ $f['to'] ?: 'hoy' }}</strong>
            @endif
            · Las eliminaciones son acciones de especial atención que remueven registros del sistema.
        </p>

        {{-- ── 3. Tabla inferior mediante componente reutilizable x-ui.data-table ── --}}
        @php
            $eventBadges = [
                'created' => 'Creación',
                'updated' => 'Modificación',
                'deleted' => 'Eliminación',
                'login'   => 'Inicio de Sesión',
                'logout'  => 'Cierre de Sesión',
                'failed'  => 'Acceso Fallido',
            ];

            $tableRows = $activities->through(function ($act) use ($eventBadges) {
                $propsText = '—';
                if (!empty($act->properties) && count($act->properties) > 0) {
                    $props = $act->properties->toArray();
                    if (isset($props['attributes'])) {
                        $attrs = [];
                        foreach ($props['attributes'] as $k => $v) {
                            if (is_scalar($v)) {
                                $attrs[] = "$k: $v";
                            }
                        }
                        $propsText = implode(' | ', array_slice($attrs, 0, 4));
                    } elseif (isset($props['ip'])) {
                        $propsText = "IP: {$props['ip']}";
                    } else {
                        $propsText = \Illuminate\Support\Str::limit(json_encode($props, JSON_UNESCAPED_UNICODE), 70);
                    }
                }

                return (object) [
                    'id' => '#' . $act->id,
                    'date_label' => $act->created_at->format('d/m/Y H:i:s'),
                    'user_name' => $act->causer?->name ?? 'Sistema / Anónimo',
                    'module_label' => ucfirst($act->log_name ?: 'General'),
                    'event_label' => $eventBadges[$act->event] ?? ucfirst($act->event ?: 'Acción'),
                    'description' => $act->description,
                    'details' => $propsText,
                ];
            });
        @endphp

        <div class="mb-8">
            <x-ui.data-table
                :rows="$tableRows"
                :paginator="$tableRows"
                rowKey="id"
                :columns="[
                    ['key' => 'id', 'label' => '#'],
                    ['key' => 'date_label', 'label' => 'Fecha y Hora'],
                    ['key' => 'user_name', 'label' => 'Usuario'],
                    ['key' => 'module_label', 'label' => 'Módulo'],
                    ['key' => 'event_label', 'label' => 'Acción'],
                    ['key' => 'description', 'label' => 'Descripción del Evento'],
                    ['key' => 'details', 'label' => 'Detalle / Propiedades'],
                ]"
                emptyText="No se encontraron registros en la bitácora para los filtros seleccionados."
            />
        </div>

    </div>
</x-app-layout>
