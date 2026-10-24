@props([
    'rows' => collect(),
    'columns' => [],
    'actions' => [],
    'paginator' => null,
    'emptyText' => 'Sin registros',
    'rowKey' => 'id',
])

@php
    $actionDefaults = [
        'view' => [
            'label'  => 'Ver',
            'method' => 'GET',
            'class'  => 'inline-flex items-center gap-1 px-2 py-1 rounded border text-sky-700 border-sky-300 hover:bg-sky-50 dark:text-sky-300 dark:border-sky-700 dark:hover:bg-sky-900/20 text-[11px] font-medium transition-colors',
            'icon'   => '<svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-sky-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S3.732 16.057 2.458 12z"/></svg>',
        ],
        'edit' => [
            'label'  => 'Editar',
            'method' => 'GET',
            'class'  => 'inline-flex items-center gap-1 px-2 py-1 rounded border text-amber-700 border-amber-300 hover:bg-amber-50 dark:text-amber-300 dark:border-amber-700 dark:hover:bg-amber-900/20 text-[11px] font-medium transition-colors',
            'icon'   => '<svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>',
        ],
        'delete' => [
            'label'   => 'Eliminar',
            'method'  => 'DELETE',
            'confirm' => '¿Estás seguro?',
            'class'   => 'inline-flex items-center gap-1 px-2 py-1 rounded border text-red-600 border-red-300 hover:bg-red-50 dark:text-red-400 dark:border-red-700 dark:hover:bg-red-900/20 text-[11px] font-medium transition-colors',
            'icon'    => '<svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>',
        ],
        'manage' => [
            'label'  => 'Administrar',
            'method' => 'GET',
            'class'  => 'inline-flex items-center gap-1 px-2 py-1 rounded border text-amber-700 border-amber-300 hover:bg-amber-50 dark:text-amber-300 dark:border-amber-700 dark:hover:bg-amber-900/20 text-[11px] font-medium transition-colors',
            'icon'   => '<svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>',
        ],
    ];

    // Íconos predefinidos para icon-text
    $iconPresets = [
        'shield' => '<svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2l4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>',
        'user'   => '<svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-sky-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>',
        'tag'    => '<svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z"/></svg>',
        'key'    => '<svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>',
    ];

    $resolveAction = function ($action) use ($actionDefaults) {
        $type = $action['type'] ?? null;
        $base = $type && isset($actionDefaults[$type]) ? $actionDefaults[$type] : [];
        return array_merge($base, $action);
    };

    $badgeColors = [
        'emerald' => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-400 dark:border-emerald-800/50',
        'sky'     => 'bg-sky-50 text-sky-700 border-sky-200 dark:bg-sky-900/30 dark:text-sky-400 dark:border-sky-800/50',
        'violet'  => 'bg-violet-50 text-violet-700 border-violet-200 dark:bg-violet-900/30 dark:text-violet-400 dark:border-violet-800/50',
        'amber'   => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-900/30 dark:text-amber-400 dark:border-amber-800/50',
        'rose'    => 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-900/30 dark:text-rose-400 dark:border-rose-800/50',
    ];

    $renderCell = function ($col, $row) use ($badgeColors, $iconPresets) {
        $type      = $col['type'] ?? 'text';
        $value     = data_get($row, $col['key'] ?? '');
        $color     = $badgeColors[$col['color'] ?? 'emerald'];
        $labelKey  = $col['labelKey'] ?? 'name';
        $emptyText = $col['emptyText'] ?? 'Sin asignar';

        if ($type === 'badge-list') {
            $items = ($value instanceof \Illuminate\Support\Collection || is_array($value))
                ? collect($value)
                : collect();
            return ['type' => 'badge-list', 'items' => $items, 'labelKey' => $labelKey, 'color' => $color, 'emptyText' => $emptyText];
        }

        if ($type === 'avatar-text') {
            return [
                'type'  => 'avatar-text',
                'name'  => data_get($row, $col['nameKey'] ?? 'name'),
                'sub'   => data_get($row, $col['subKey'] ?? 'email'),
                'color' => $color,
            ];
        }

        // ── NUEVO: icon-text ──────────────────────────────────────────────
        // Muestra un ícono SVG (preset o custom) seguido del valor de texto
        // Uso: ['type' => 'icon-text', 'key' => 'name', 'icon' => 'shield']
        //      ['type' => 'icon-text', 'key' => 'name', 'iconSvg' => '<svg.../>']
        if ($type === 'icon-text') {
            $iconKey = $col['icon'] ?? null;
            $iconSvg = $col['iconSvg'] ?? ($iconKey && isset($iconPresets[$iconKey]) ? $iconPresets[$iconKey] : null);
            return [
                'type'    => 'icon-text',
                'value'   => $value,
                'iconSvg' => $iconSvg,
            ];
        }

        if ($type === 'image') {
            return [
                'type' => 'image',
                'value' => $value,
                'alt' => data_get($row, $col['altKey'] ?? 'name', ''),
            ];
        }

        return ['type' => 'text', 'value' => $value];
    };

    // Cache de nombres de permisos en memoria durante la petición
    $registeredPermissions = cache()->remember('permisos.all_names', 3600, function () {
        try {
            return \Spatie\Permission\Models\Permission::pluck('name')->flip()->toArray();
        } catch (\Throwable $e) {
            return [];
        }
    });

    $canPerformAction = function ($rawAction, $row) use ($registeredPermissions) {
        // 1. Condición personalizada de la acción
        if (isset($rawAction['condition']) && is_callable($rawAction['condition']) && !$rawAction['condition']($row)) {
            return false;
        }

        $user = auth()->user();
        if (!$user) {
            return false;
        }

        // Si el usuario es superadmin o tiene 'admin' (bypass total)
        if ($user->can('admin')) {
            return true;
        }

        // 2. Permiso explícito en la configuración de la acción
        if (!empty($rawAction['permission'])) {
            return $user->can($rawAction['permission']);
        }

        // 3. Ability de Policy / Gate explícito
        if (!empty($rawAction['can'])) {
            return $user->can($rawAction['can'], $row);
        }

        $type  = $rawAction['type'] ?? null;
        $route = $rawAction['route'] ?? '';

        // 4. Si la fila es un Modelo Eloquent, evaluar la Policy asociada
        if ($row instanceof \Illuminate\Database\Eloquent\Model) {
            if ($type === 'edit' || str_ends_with($route, '.edit') || str_ends_with($route, '.update')) {
                return $user->can('update', $row);
            }
            if ($type === 'delete' || str_ends_with($route, '.destroy') || str_ends_with($route, '.delete')) {
                return $user->can('delete', $row);
            }
            if ($type === 'view' || str_ends_with($route, '.show')) {
                return $user->can('view', $row);
            }
        }

        // 5. Inferencia por nombre de ruta: e.g. "products.edit" -> modulo "products", accion "editar"
        if (!empty($route) && is_string($route) && str_contains($route, '.')) {
            $parts = explode('.', $route);
            $module = $parts[0] ?? '';
            $actionName = $parts[1] ?? '';

            $actionMap = [
                'index'   => 'ver',
                'show'    => 'ver',
                'create'  => 'crear',
                'store'   => 'crear',
                'edit'    => 'editar',
                'update'  => 'editar',
                'destroy' => 'eliminar',
                'delete'  => 'eliminar',
            ];

            if (isset($actionMap[$actionName])) {
                $permissionName = "{$actionMap[$actionName]} {$module}";
                if (isset($registeredPermissions[$permissionName])) {
                    return $user->can($permissionName);
                }
            }
        }

        return true;
    };

    // Pre-calcular si hay al menos una acción permitida en las filas actuales
    $hasAnyAllowedAction = false;
    if (count($actions)) {
        if ($rows->isEmpty()) {
            $hasAnyAllowedAction = true;
        } else {
            foreach ($rows as $r) {
                foreach ($actions as $rawAction) {
                    if ($canPerformAction($rawAction, $r)) {
                        $hasAnyAllowedAction = true;
                        break 2;
                    }
                }
            }
        }
    }
@endphp

<div class="rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden bg-white dark:bg-slate-900">

    {{-- ── Tabla Desktop ───────────────────────────────────────────────── --}}
    <div class="hidden md:block overflow-x-auto">
        <table class="w-full text-xs text-left">
            <thead class="uppercase bg-gray-200 dark:bg-slate-800/60 text-gray-500 dark:text-gray-400">
                <tr>
                    @foreach ($columns as $col)
                        <th class="px-3 py-2 font-semibold">{{ $col['label'] ?? 'Columna' }}</th>
                    @endforeach
                    @if($hasAnyAllowedAction)
                        <th class="px-3 py-2 text-right font-semibold">Acciones</th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                @forelse($rows as $row)
                    <tr class="hover:bg-gray-50 dark:hover:bg-slate-800/40 transition">
                        @foreach ($columns as $col)
                            @php $cell = $renderCell($col, $row); @endphp
                            <td class="px-3 py-2 text-gray-800 dark:text-gray-200">

                                @if($cell['type'] === 'badge-list')
                                    <div class="flex flex-wrap gap-1">
                                        @forelse($cell['items'] as $item)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-semibold uppercase tracking-wide border {{ $cell['color'] }}">
                                                {{ is_string($item) ? $item : data_get($item, $cell['labelKey']) }}
                                            </span>
                                        @empty
                                            <span class="text-xs text-gray-400 dark:text-gray-500 italic">{{ $cell['emptyText'] }}</span>
                                        @endforelse
                                    </div>

                                @elseif($cell['type'] === 'avatar-text')
                                    <div class="flex items-center gap-2.5">
                                        <div class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center border {{ $cell['color'] }}">
                                            <span class="font-bold text-sm uppercase">{{ substr($cell['name'], 0, 1) }}</span>
                                        </div>
                                        <div>
                                            <div class="font-semibold text-gray-900 dark:text-white text-xs">{{ $cell['name'] }}</div>
                                            <div class="text-[11px] text-gray-500 dark:text-gray-400">{{ $cell['sub'] }}</div>
                                        </div>
                                    </div>

                                @elseif($cell['type'] === 'icon-text')
                                    <div class="flex items-center gap-1.5">
                                        @if($cell['iconSvg'])
                                            {!! $cell['iconSvg'] !!}
                                        @endif
                                        <span class="font-semibold text-gray-900 dark:text-white">{{ $cell['value'] }}</span>
                                    </div>

                                @elseif($cell['type'] === 'image')
                                    <div class="flex items-center">
                                        @if($cell['value'])
                                            <div class="relative group cursor-pointer" onclick="openImagePreview('{{ $cell['value'] }}', '{{ addslashes($cell['alt']) }}')">
                                                <img src="{{ $cell['value'] }}" alt="{{ $cell['alt'] }}"
                                                    class="w-14 h-14 object-cover rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-slate-800 shadow-sm group-hover:ring-2 group-hover:ring-emerald-500 group-hover:scale-105 transition-all shrink-0">
                                                <div class="absolute inset-0 bg-black/25 opacity-0 group-hover:opacity-100 rounded-xl flex items-center justify-center transition-opacity">
                                                    <svg class="w-5 h-5 text-white drop-shadow" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m3-3H7" />
                                                    </svg>
                                                </div>
                                            </div>
                                        @else
                                            <div class="w-14 h-14 rounded-xl border border-dashed border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-slate-800 flex items-center justify-center text-gray-400 dark:text-gray-500 shrink-0">
                                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                            </div>
                                        @endif
                                    </div>

                                @else
                                    {{ $cell['value'] }}
                                @endif

                            </td>
                        @endforeach

                        @if($hasAnyAllowedAction)
                            @php
                                $rowAllowedActions = array_filter($actions, fn($act) => $canPerformAction($act, $row));
                            @endphp
                            <td class="px-3 py-2">
                                <div class="flex justify-end flex-wrap gap-1.5">
                                    @foreach($rowAllowedActions as $rawAction)
                                        @php
                                            $action   = $resolveAction($rawAction);
                                            $method   = strtoupper($action['method'] ?? 'GET');
                                            $id       = data_get($row, $rowKey);
                                            $hasRoute = !empty($action['route']) || isset($action['url']);
                                        @endphp
                                        @if($method === 'GET' && $hasRoute)
                                            <a href="{{ isset($action['url']) ? $action['url']($row) : route($action['route'], $id) }}" class="{{ $action['class'] }}">
                                                {!! $action['icon'] ?? '' !!}
                                                <span>{{ $action['label'] ?? 'Acción' }}</span>
                                            </a>
                                        @elseif($hasRoute)
                                            <form action="{{ route($action['route'], $id) }}" method="POST" class="inline">
                                                @csrf @method($method)
                                                <button type="submit"
                                                    @if(!empty($action['confirm'])) onclick="return confirm('{{ $action['confirm'] }}')" @endif
                                                    class="{{ $action['class'] }}">
                                                    {!! $action['icon'] ?? '' !!}
                                                    <span>{{ $action['label'] ?? 'Acción' }}</span>
                                                </button>
                                            </form>
                                        @endif
                                    @endforeach
                                </div>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($columns) + ($hasAnyAllowedAction ? 1 : 0) }}"
                            class="px-4 py-10 text-center text-gray-500 dark:text-gray-400">
                            {{ $emptyText }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ── Cards Mobile ────────────────────────────────────────────────── --}}
    <div class="md:hidden divide-y divide-gray-200 dark:divide-gray-800">
        @forelse($rows as $row)
            <div class="p-4 space-y-2">
                @foreach ($columns as $col)
                    @php $cell = $renderCell($col, $row); @endphp
                    <div class="flex justify-between items-start gap-2">
                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 shrink-0">{{ $col['label'] ?? '' }}</span>

                        @if($cell['type'] === 'badge-list')
                            <div class="flex flex-wrap justify-end gap-1">
                                @forelse($cell['items'] as $item)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-semibold uppercase tracking-wide border {{ $cell['color'] }}">
                                        {{ is_string($item) ? $item : data_get($item, $cell['labelKey']) }}
                                    </span>
                                @empty
                                    <span class="text-xs text-gray-400 italic">{{ $cell['emptyText'] }}</span>
                                @endforelse
                            </div>

                        @elseif($cell['type'] === 'avatar-text')
                            <div class="flex items-center gap-2 justify-end">
                                <div class="flex-shrink-0 w-7 h-7 rounded-full flex items-center justify-center border {{ $cell['color'] }}">
                                    <span class="font-bold text-xs uppercase">{{ substr($cell['name'], 0, 1) }}</span>
                                </div>
                                <div class="text-right">
                                    <div class="font-semibold text-gray-900 dark:text-white text-xs">{{ $cell['name'] }}</div>
                                    <div class="text-[11px] text-gray-500 dark:text-gray-400">{{ $cell['sub'] }}</div>
                                </div>
                            </div>

                        @elseif($cell['type'] === 'icon-text')
                            <div class="flex items-center justify-end gap-1.5">
                                @if($cell['iconSvg'])
                                    {!! $cell['iconSvg'] !!}
                                @endif
                                <span class="font-semibold text-gray-900 dark:text-white text-xs">{{ $cell['value'] }}</span>
                            </div>

                        @elseif($cell['type'] === 'image')
                            <div class="flex justify-end">
                                @if($cell['value'])
                                    <div class="relative group cursor-pointer" onclick="openImagePreview('{{ $cell['value'] }}', '{{ addslashes($cell['alt']) }}')">
                                        <img src="{{ $cell['value'] }}" alt="{{ $cell['alt'] }}" class="w-14 h-14 object-cover rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-slate-800 shadow-sm">
                                        <div class="absolute inset-0 bg-black/25 opacity-0 group-hover:opacity-100 rounded-xl flex items-center justify-center transition-opacity">
                                            <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m3-3H7" />
                                            </svg>
                                        </div>
                                    </div>
                                @else
                                    <div class="w-14 h-14 rounded-xl border border-dashed border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-slate-800 flex items-center justify-center text-gray-400">
                                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                @endif
                            </div>

                        @else
                            <span class="text-xs text-gray-800 dark:text-gray-200 text-right">{{ $cell['value'] }}</span>
                        @endif
                    </div>
                @endforeach

                @if($hasAnyAllowedAction)
                    @php
                        $rowAllowedActions = array_filter($actions, fn($act) => $canPerformAction($act, $row));
                    @endphp
                    @if(count($rowAllowedActions))
                        <div class="pt-2 flex gap-1.5 flex-wrap">
                            @foreach($rowAllowedActions as $rawAction)
                                @php
                                    $action   = $resolveAction($rawAction);
                                    $method   = strtoupper($action['method'] ?? 'GET');
                                    $id       = data_get($row, $rowKey);
                                    $hasRoute = !empty($action['route']) || isset($action['url']);
                                @endphp
                                @if($method === 'GET' && $hasRoute)
                                    <a href="{{ isset($action['url']) ? $action['url']($row) : route($action['route'], $id) }}" class="{{ $action['class'] }}">
                                        {!! $action['icon'] ?? '' !!}
                                        <span>{{ $action['label'] ?? 'Acción' }}</span>
                                    </a>
                                @elseif($hasRoute)
                                    <form action="{{ route($action['route'], $id) }}" method="POST" class="inline">
                                        @csrf @method($method)
                                        <button type="submit"
                                            @if(!empty($action['confirm'])) onclick="return confirm('{{ $action['confirm'] }}')" @endif
                                            class="{{ $action['class'] }}">
                                            {!! $action['icon'] ?? '' !!}
                                            <span>{{ $action['label'] ?? 'Acción' }}</span>
                                        </button>
                                    </form>
                                @endif
                            @endforeach
                        </div>
                    @endif
                @endif
            </div>
        @empty
            <div class="px-4 py-8 text-center text-gray-500 dark:text-gray-400 text-sm">{{ $emptyText }}</div>
        @endforelse
    </div>
</div>

@if ($paginator && $paginator->hasPages())
    <div class="flex items-center justify-between mt-4 px-1">
        {{-- Móvil: solo Anterior / Siguiente + indicador --}}
        <div class="flex sm:hidden items-center justify-between w-full">
            @if ($paginator->onFirstPage())
                <span class="px-3 py-1.5 text-xs font-medium text-gray-300 border border-gray-200 rounded-lg dark:border-slate-700 dark:text-gray-600">Anterior</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="px-3 py-1.5 text-xs font-medium text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 dark:border-slate-600 dark:text-gray-300 dark:hover:bg-slate-800">Anterior</a>
            @endif

            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</span>

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="px-3 py-1.5 text-xs font-medium text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 dark:border-slate-600 dark:text-gray-300 dark:hover:bg-slate-800">Siguiente</a>
            @else
                <span class="px-3 py-1.5 text-xs font-medium text-gray-300 border border-gray-200 rounded-lg dark:border-slate-700 dark:text-gray-600">Siguiente</span>
            @endif
        </div>

        {{-- Desktop: rango + números completos --}}
        <div class="hidden sm:flex sm:items-center sm:justify-between w-full">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ __('Showing') }} <span class="font-medium">{{ $paginator->firstItem() }}</span>
                {{ __('to') }} <span class="font-medium">{{ $paginator->lastItem() }}</span>
                {{ __('of') }} <span class="font-medium">{{ $paginator->total() }}</span> {{ __('results') }}
            </p>

            <div class="flex gap-1">
                {{ $paginator->links() }}
            </div>
        </div>
    </div>
@endif

<x-ui.image-preview-modal />