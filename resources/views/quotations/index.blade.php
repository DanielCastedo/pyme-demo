<x-app-layout>
    <div class="max-w-10xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        @if (session('success'))
            <x-ui.alert type="success" :message="session('success')" />
        @endif

        @if (session('error'))
            <x-ui.alert type="error" :message="session('error')" />
        @endif

        @php
            $buttons = [
                [
                    'route' => route('quotations.create'),
                    'can' => 'crear quotations',
                    'label' => 'Nueva Proforma',
                    'variant' => 'primary',
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />',
                ],
            ];
        @endphp

        <x-ui.page-header category="Ventas" title="Proformas y Cotizaciones"
            description="Presupuestos comerciales para clientes con cálculo de stock referencial." :buttons="$buttons" />

        <div class="mb-5 flex flex-col sm:flex-row gap-3 items-center justify-between">
            <div class="w-full sm:w-96">
                <x-ui.search-box placeholder="Buscar por N° proforma, cliente o notas..." filterName="buscar" />
            </div>
            <div class="w-full sm:w-auto flex items-center gap-2 flex-wrap">
                @php
                    $curStatus = request('filter.status');
                @endphp
                {{-- Todas --}}
                <a href="{{ route('quotations.index') }}"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg border transition-all duration-150 shadow-sm {{ empty($curStatus) ? 'bg-slate-900 text-white border-slate-900 dark:bg-emerald-600 dark:text-white dark:border-emerald-500 ring-2 ring-emerald-500/30' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50 dark:bg-slate-800 dark:text-gray-300 dark:border-slate-700 dark:hover:bg-slate-700 dark:hover:text-white' }}">
                    <svg class="w-3.5 h-3.5 {{ empty($curStatus) ? 'text-emerald-400 dark:text-white' : 'text-gray-400 dark:text-gray-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                    Todas
                </a>

                {{-- Pendientes --}}
                <a href="{{ route('quotations.index', ['filter' => ['status' => 'pending']]) }}"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg border transition-all duration-150 shadow-sm {{ $curStatus === 'pending' ? 'bg-amber-500 text-white border-amber-500 dark:bg-amber-500 dark:text-white dark:border-amber-400 ring-2 ring-amber-500/30' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50 dark:bg-slate-800 dark:text-gray-300 dark:border-slate-700 dark:hover:bg-slate-700 dark:hover:text-white' }}">
                    <span class="w-2 h-2 rounded-full {{ $curStatus === 'pending' ? 'bg-white' : 'bg-amber-500' }}"></span>
                    Pendientes
                </a>

                {{-- Aprobadas --}}
                <a href="{{ route('quotations.index', ['filter' => ['status' => 'approved']]) }}"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg border transition-all duration-150 shadow-sm {{ $curStatus === 'approved' ? 'bg-emerald-600 text-white border-emerald-600 dark:bg-emerald-600 dark:text-white dark:border-emerald-500 ring-2 ring-emerald-500/30' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50 dark:bg-slate-800 dark:text-gray-300 dark:border-slate-700 dark:hover:bg-slate-700 dark:hover:text-white' }}">
                    <span class="w-2 h-2 rounded-full {{ $curStatus === 'approved' ? 'bg-white' : 'bg-emerald-400' }}"></span>
                    Aprobadas
                </a>

                {{-- Rechazadas --}}
                <a href="{{ route('quotations.index', ['filter' => ['status' => 'rejected']]) }}"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg border transition-all duration-150 shadow-sm {{ $curStatus === 'rejected' ? 'bg-rose-600 text-white border-rose-600 dark:bg-rose-600 dark:text-white dark:border-rose-500 ring-2 ring-rose-500/30' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50 dark:bg-slate-800 dark:text-gray-300 dark:border-slate-700 dark:hover:bg-slate-700 dark:hover:text-white' }}">
                    <span class="w-2 h-2 rounded-full {{ $curStatus === 'rejected' ? 'bg-white' : 'bg-rose-500' }}"></span>
                    Rechazadas
                </a>
            </div>
        </div>

        @php
            $columns = [
                ['key' => 'number', 'label' => 'N° Proforma'],
                ['key' => 'customer.name', 'label' => 'Cliente'],
                ['key' => 'formatted_date', 'label' => 'Fecha'],
                ['key' => 'formatted_expires_at', 'label' => 'Vencimiento'],
                ['key' => 'formatted_amount', 'label' => 'Total'],
                [
                    'key' => 'status',
                    'label' => 'Estado',
                    'type' => 'badge',
                    'colorMap' => [
                        'pending' => 'yellow',
                        'approved' => 'emerald',
                        'rejected' => 'red',
                        'expired' => 'gray',
                    ],
                    'labelMap' => [
                        'pending' => 'Pendiente',
                        'approved' => 'Aprobada',
                        'rejected' => 'Rechazada',
                        'expired' => 'Vencida',
                    ],
                ],
                ['key' => 'user.name', 'label' => 'Emitido por'],
            ];

            $actions = [
                ['type' => 'view', 'route' => 'quotations.show'],
                [
                    'type' => 'edit',
                    'route' => 'quotations.edit',
                    'condition' => fn ($q) => $q->status !== 'approved',
                ],
                [
                    'type' => 'delete',
                    'route' => 'quotations.destroy',
                    'confirm' => '¿Estás seguro de eliminar esta proforma? Esta acción no se puede deshacer.',
                    'condition' => fn ($q) => $q->status !== 'approved',
                ],
            ];
        @endphp

        <x-ui.data-table :rows="$quotations" :columns="$columns" :actions="$actions" :paginator="$quotations"
            emptyText="No hay proformas registradas." />
    </div>
</x-app-layout>
