@php
    use App\Models\Sidebar;
    use Illuminate\Support\Facades\Route;

    $user = auth()->user();

    $puedeVerMenu = function ($menu) use ($user) {
        if ($menu->ruta === 'terms.index') {
            return $user && ($user->hasRole('superadmin') || $user->hasRole('admin') || $user->is_designated_admin);
        }
        if ($menu->rol_requerido && (!$user || !$user->hasRole($menu->rol_requerido))) {
            return false;
        }
        if ($menu->permiso_requerido && (!$user || !$user->can($menu->permiso_requerido))) {
            return false;
        }
        return true;
    };

    $menusPrincipales = Sidebar::whereNull('parent_id')
        ->with(['children' => fn($q) => $q->orderBy('orden')])
        ->orderBy('orden')
        ->get()
        ->filter($puedeVerMenu)
        ->map(function ($menu) use ($puedeVerMenu) {
            $menu->setRelation(
                'children',
                $menu->children->filter($puedeVerMenu)->values()
            );
            return $menu;
        })
        // Si un padre no tiene ruta propia y se quedó sin children visibles, se oculta
        ->filter(fn($menu) => $menu->ruta || $menu->children->isNotEmpty());

    $routeIsActive = function (?string $routeName): bool {
        if (! $routeName) {
            return false;
        }

        if (request()->routeIs($routeName)) {
            return true;
        }

        $parts = explode('.', $routeName);
        $lastPart = end($parts);

        if ($lastPart === 'index') {
            $resourceBase = implode('.', array_slice($parts, 0, -1));
            $currentRouteName = request()->route()?->getName();

            if ($currentRouteName && preg_match('/^'.preg_quote($resourceBase, '/').'\.(show|edit|create|store|update|destroy)$/', $currentRouteName)) {
                return true;
            }
        }

        return false;
    };

    $sidebarCompany = \App\Models\Company::find(1);
@endphp

<div>
    {{-- Backdrop mobile --}}
    <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-[2px] z-40 lg:hidden transition-opacity duration-200"
        :class="sidebarOpen ? 'opacity-100' : 'opacity-0 pointer-events-none'"
        aria-hidden="true" x-cloak>
    </div>

    {{-- Sidebar --}}
    <div id="sidebar"
        class="flex flex-col absolute z-40 left-0 top-0 lg:static lg:left-auto lg:top-auto lg:translate-x-0
               h-screen overflow-y-auto no-scrollbar w-64 lg:w-20 lg:sidebar-expanded:!w-64 2xl:!w-64
               shrink-0 transition-all duration-200 ease-in-out
               bg-[linear-gradient(180deg,rgb(51_65_85),rgb(30_41_59))] dark:bg-[linear-gradient(180deg,rgb(15_23_42),rgb(2_6_23))]
               shadow-xl shadow-slate-900/20 border-r border-white/5"
        :class="sidebarOpen ? 'translate-x-0' : '-translate-x-64'"
        @click.outside="sidebarOpen = false"
        @keydown.escape.window="sidebarOpen = false"
        x-cloak="lg">

        {{-- Header: logo (clickeable) + nombre + hamburguesa + expandir/contraer --}}
        <div class="flex items-center gap-2.5 px-4 py-4 border-b border-white/10 shrink-0">
            <button type="button"
                @click="sidebarOpen = true; sidebarExpanded = true"
                class="shrink-0 w-9 h-9 rounded-lg overflow-hidden bg-white/10 ring-1 ring-white/10 flex items-center justify-center hover:ring-emerald-400/50 transition cursor-pointer">
                <img src="{{ $appLogoUrl }}" alt="{{ config('app.name') }}" class="w-full h-full object-contain">
            </button>

            <div class="min-w-0 flex-1 lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">
                <h3 class="text-sm font-bold text-white truncate leading-tight">
                    {{ config('app.name') }}
                </h3>
                @if ($sidebarCompany?->city)
                    <p class="text-[10px] text-slate-400 truncate">{{ $sidebarCompany->city }}</p>
                @endif
            </div>

            {{-- Hamburguesa: solo visible en móvil, junto al logo --}}
            <button class="lg:hidden shrink-0 text-slate-300 hover:text-white p-1.5 rounded-lg hover:bg-white/10 transition"
                @click.stop="sidebarOpen = !sidebarOpen"
                aria-controls="sidebar"
                :aria-expanded="sidebarOpen">
                <span class="sr-only">Cerrar sidebar</span>
                <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24">
                    <path d="M10.7 18.7l1.4-1.4L7.8 13H20v-2H7.8l4.3-4.3-1.4-1.4L4 12z" />
                </svg>
            </button>

            {{-- Expandir/contraer: solo visible en desktop colapsado, junto al logo --}}
            <button @click="sidebarExpanded = !sidebarExpanded"
                class="hidden lg:flex 2xl:hidden shrink-0 text-slate-300 hover:text-white p-1.5 rounded-lg hover:bg-white/10 transition">
                <span class="sr-only">Expandir / contraer sidebar</span>
                <svg class="w-5 h-5 fill-current transition-transform duration-200" :class="sidebarExpanded ? 'rotate-180' : ''" viewBox="0 0 24 24">
                    <path d="M19.586 11l-5-5L16 4.586 23.414 12 16 19.414 14.586 18l5-5H7v-2z" />
                    <path class="opacity-40" d="M3 23H1V1h2z" />
                </svg>
            </button>
        </div>

        {{-- Navegación --}}
        <div class="flex-1 px-2.5 py-3">
            <ul class="space-y-0.5">
                @forelse ($menusPrincipales as $menu)
                    @php
                        $isActive       = $routeIsActive($menu->ruta);
                        $hasActiveChild = $menu->children->contains(
                            fn($child) => $routeIsActive($child->ruta)
                        );
                        $url = $menu->ruta
                            ? (Route::has($menu->ruta) ? route($menu->ruta) : url($menu->ruta))
                            : '#';
                    @endphp

                    {{-- Menú con submenús --}}
                    @if ($menu->children->count() > 0)
                        <li x-data="{ open: {{ $hasActiveChild ? 'true' : 'false' }} }"
                            class="rounded-lg overflow-hidden transition-colors
                                {{ $hasActiveChild ? 'bg-gradient-to-r from-emerald-500/15 to-transparent' : '' }}">

                            <a href="#"
                                @click.prevent="if (!sidebarExpanded) { sidebarExpanded = true; open = true } else { open = !open }"
                                class="group flex items-center justify-between pl-2.5 pr-2.5 py-2 rounded-lg text-slate-200 hover:text-white hover:bg-white/5 transition truncate">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <span class="shrink-0 w-7 h-7 rounded-md flex items-center justify-center transition-colors
                                        {{ $hasActiveChild ? 'bg-emerald-500/20' : 'bg-white/5 group-hover:bg-white/10' }}">
                                        <svg class="w-3.5 h-3.5 fill-current {{ $hasActiveChild ? 'text-emerald-300' : 'text-slate-300' }}"
                                            viewBox="0 0 24 24">
                                            {!! $menu->icono !!}
                                        </svg>
                                    </span>
                                    <span class="text-[13px] font-medium truncate lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">
                                        {{ $menu->nombre }}
                                    </span>
                                </div>
                                <svg class="w-2.5 h-2.5 shrink-0 fill-current text-slate-400 transition-transform duration-200 lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100"
                                    :class="open ? 'rotate-180' : ''"
                                    viewBox="0 0 12 12">
                                    <path d="M5.9 11.4L.5 6l1.4-1.4 4 4 4-4L11.3 6z" />
                                </svg>
                            </a>

                            <div class="lg:hidden lg:sidebar-expanded:block 2xl:block" x-show="open" x-collapse x-cloak>
                                <ul class="pl-10 pr-1.5 pb-1 pt-0.5 space-y-0">
                                    @foreach ($menu->children as $child)
                                    @php
                                        $childUrl      = $child->ruta ? (Route::has($child->ruta) ? route($child->ruta) : url($child->ruta)) : '#';
                                        $isChildActive = $routeIsActive($child->ruta);
                                    @endphp
                                        <li>
                                            <a href="{{ $childUrl }}"
                                                class="flex items-center gap-2 py-1.5 text-[13px] font-medium truncate transition relative
                                                   {{ $isChildActive ? 'text-emerald-300' : 'text-slate-400 hover:text-white' }}">
                                                <span class="w-1 h-1 rounded-full shrink-0 {{ $isChildActive ? 'bg-emerald-400' : 'bg-slate-600' }}"></span>
                                                {{ $child->nombre }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </li>

                    {{-- Menú simple --}}
                    @else
                        <li>
                            <a href="{{ $url }}"
                                class="group flex items-center gap-2.5 pl-2.5 pr-2.5 py-2 rounded-lg truncate transition relative
                                    {{ $isActive
                                        ? 'text-white bg-gradient-to-r from-emerald-500/20 to-transparent'
                                        : 'text-slate-200 hover:text-white hover:bg-white/5' }}">
                                @if ($isActive)
                                    <span class="absolute left-0 top-1 bottom-1 w-0.5 rounded-full bg-emerald-400"></span>
                                @endif
                                <span class="shrink-0 w-7 h-7 rounded-md flex items-center justify-center transition-colors
                                    {{ $isActive ? 'bg-emerald-500/25' : 'bg-white/5 group-hover:bg-white/10' }}">
                                    <svg class="w-3.5 h-3.5 fill-current {{ $isActive ? 'text-emerald-300' : 'text-slate-300' }}"
                                        viewBox="0 0 24 24">
                                        {!! $menu->icono !!}
                                    </svg>
                                </span>
                                <span class="text-[13px] font-medium truncate lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">
                                    {{ $menu->nombre }}
                                </span>
                            </a>
                        </li>
                    @endif

                @empty
                    <li class="pl-3 pr-2.5 py-2 text-xs text-slate-500 italic">
                        No hay menús configurados.
                    </li>
                @endforelse
            </ul>
        </div>
    </div>
</div>