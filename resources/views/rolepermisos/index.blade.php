<x-app-layout>
    <div class="max-w-10xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

        {{-- Alertas --}}
        @if (session('success'))
            <x-ui.alert type="success" :message="session('success')" />
        @endif
        @if (session('error'))
            <x-ui.alert type="error" :message="session('error')" />
        @endif
        @if ($errors->any())
            <x-ui.alert type="error" message="Ocurrió un error de validación. Revisa los datos ingresados." />
        @endif

        <!-- Encabezado -->
        <div class="mb-5 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div>
                <span
                    class="text-[10px] font-bold tracking-wider text-emerald-600 dark:text-emerald-400 uppercase">Seguridad</span>
                <h2 class="text-xl font-bold text-gray-800 dark:text-white mt-0.5">Asignación de Permisos</h2>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Administra qué puede hacer cada rol dentro
                    del sistema.</p>
            </div>
        </div>
        @php
            // Total de permisos por módulo, para mostrar "asignados/total"
            $catalog = \App\Support\PermissionGroups::group(\Spatie\Permission\Models\Permission::pluck('name'));
            $totalPermissions = collect($catalog)->flatten(1)->sum(fn ($m) => count($m['permissions']));
            $canManage = auth()->user()?->can('editar role-permisos');
        @endphp

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
            @forelse ($roles as $role)
                @php
                    $assigned = $role->permissions->pluck('name')->all();
                    $count = count($assigned);
                    $percent = $totalPermissions ? round($count / $totalPermissions * 100) : 0;
                @endphp

                <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-slate-900 shadow-sm overflow-hidden">
                    {{-- Cabecera del rol --}}
                    <div class="flex items-center justify-between gap-3 px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                        <div class="min-w-0">
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wide truncate">{{ $role->name }}</h3>
                            <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">
                                <span class="font-semibold text-emerald-600 dark:text-emerald-400">{{ $count }}</span> de {{ $totalPermissions }} permisos
                            </p>
                        </div>
                        @if ($canManage)
                            <a href="{{ route('role-permisos.edit', $role->id) }}"
                                class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-md border text-[11px] font-medium text-amber-700 border-amber-300 hover:bg-amber-50 dark:text-amber-300 dark:border-amber-700 dark:hover:bg-amber-900/20 transition-colors shrink-0">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                Administrar
                            </a>
                        @endif
                    </div>

                    {{-- Barra de avance --}}
                    <div class="h-1 bg-gray-100 dark:bg-slate-800">
                        <div class="h-1 bg-emerald-500" style="width: {{ $percent }}%"></div>
                    </div>

                    {{-- Resumen por área (se despliega para ver los módulos) --}}
                    @if ($count === 0)
                        <p class="px-4 py-4 text-xs text-gray-400 dark:text-gray-500 italic">Sin permisos asignados.</p>
                    @else
                        <div class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach ($catalog as $area => $modules)
                                @php
                                    $areaAssigned = collect($modules)->sum(fn ($m) => count(array_intersect(array_keys($m['permissions']), $assigned)));
                                    $areaTotal = collect($modules)->sum(fn ($m) => count($m['permissions']));
                                @endphp
                                @continue($areaAssigned === 0)

                                <details class="group">
                                    <summary class="flex items-center justify-between gap-2 px-4 py-2 cursor-pointer select-none hover:bg-gray-50 dark:hover:bg-slate-800/50 list-none">
                                        <span class="flex items-center gap-2 text-xs font-semibold text-gray-700 dark:text-gray-200">
                                            <svg class="w-3 h-3 text-gray-400 transition-transform group-open:rotate-90" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" /></svg>
                                            {{ $area }}
                                        </span>
                                        <span class="text-[11px] font-medium {{ $areaAssigned === $areaTotal ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-500 dark:text-gray-400' }}">
                                            {{ $areaAssigned }}/{{ $areaTotal }}
                                        </span>
                                    </summary>

                                    <div class="px-4 pb-3 pt-1 flex flex-wrap gap-1.5">
                                        @foreach ($modules as $module)
                                            @php
                                                $have = array_intersect_key($module['permissions'], array_flip($assigned));
                                            @endphp
                                            @continue(empty($have))
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md border border-emerald-200 dark:border-emerald-800/50 bg-emerald-50 dark:bg-emerald-900/20 text-[10px] text-emerald-800 dark:text-emerald-300"
                                                title="{{ implode(', ', $have) }}">
                                                <span class="font-semibold">{{ $module['label'] }}</span>
                                                <span class="text-emerald-600/80 dark:text-emerald-400/80">{{ implode(' · ', array_values($have)) }}</span>
                                            </span>
                                        @endforeach
                                    </div>
                                </details>
                            @endforeach
                        </div>
                    @endif
                </div>
            @empty
                <div class="col-span-full p-6 text-center text-xs text-gray-500 dark:text-gray-400 bg-white dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-gray-700">
                    No hay roles registrados en el sistema.
                </div>
            @endforelse
        </div>

        @if ($roles->hasPages())
            <div class="mt-5">{{ $roles->links() }}</div>
        @endif

    </div>
</x-app-layout>
