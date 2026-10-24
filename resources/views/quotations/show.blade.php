<x-app-layout>
    <div class="max-w-10xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        @if (session('success'))
            <x-ui.alert type="success" :message="session('success')" />
        @endif

        @if (session('error'))
            <x-ui.alert type="error" :message="session('error')" />
        @endif

        {{-- Encabezado con Botones de Acción al Lado Derecho --}}
        <div class="mb-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <span class="text-[10px] font-bold tracking-wider text-emerald-600 dark:text-emerald-400 uppercase">Ventas</span>
                <h2 class="text-xl font-bold text-gray-800 dark:text-white sm:mt-0.5">
                    Proforma {{ $quotation->number }}
                </h2>
            </div>

            <div class="flex items-center gap-2 flex-wrap sm:justify-end">
                <a href="{{ route('quotations.index') }}"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-gray-300 dark:bg-slate-800 dark:border-gray-600 rounded-lg text-xs font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-700 transition shadow-sm">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Volver
                </a>

                @can('proformas.exportar')
                <a href="{{ route('quotations.pdf', $quotation) }}" target="_blank"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white rounded-lg text-xs font-medium transition shadow-sm">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Imprimir / Descargar PDF
                </a>
                @endcan

                @if ($quotation->status === 'pending')
                    @can('update', $quotation)
                    <a href="{{ route('quotations.edit', $quotation) }}"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gray-600 hover:bg-gray-700 text-white rounded-lg text-xs font-medium transition shadow-sm">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        Editar
                    </a>
                    @endcan

                    @can('changeStatus', $quotation)
                    <form action="{{ route('quotations.status', $quotation) }}" method="POST" class="inline">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="approved">
                        <button type="submit"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-medium transition shadow-sm">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            Aprobar
                        </button>
                    </form>

                    <form action="{{ route('quotations.status', $quotation) }}" method="POST" class="inline">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="rejected">
                        <button type="submit"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gray-200 dark:bg-slate-700 hover:bg-gray-300 dark:hover:bg-slate-600 text-gray-700 dark:text-gray-200 rounded-lg text-xs font-medium transition shadow-sm">
                            Rechazar
                        </button>
                    </form>
                    @endcan
                @endif
            </div>
        </div>

        {{-- Tarjeta Principal de Detalles --}}
        <div class="bg-white dark:bg-slate-900 shadow-sm border border-gray-200 dark:border-gray-700 rounded-xl p-5 md:p-6 mb-6">
            {{-- Grid de Datos de Cabecera --}}
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-4 mb-6">
                <div class="p-3 bg-gray-50 dark:bg-slate-800/40 rounded-lg border border-gray-100 dark:border-gray-800">
                    <span class="block text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-0.5">Cliente</span>
                    <span class="text-xs font-semibold text-gray-900 dark:text-white">{{ $quotation->customer->name ?? '—' }}</span>
                </div>
                <div class="p-3 bg-gray-50 dark:bg-slate-800/40 rounded-lg border border-gray-100 dark:border-gray-800">
                    <span class="block text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-0.5">NIT / CI</span>
                    <span class="text-xs font-medium text-gray-700 dark:text-gray-300">{{ $quotation->customer->document ?? 'S/N' }}</span>
                </div>
                <div class="p-3 bg-gray-50 dark:bg-slate-800/40 rounded-lg border border-gray-100 dark:border-gray-800">
                    <span class="block text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-0.5">Teléfono</span>
                    <span class="text-xs font-medium text-gray-700 dark:text-gray-300">{{ $quotation->customer->phone ?? '—' }}</span>
                </div>
                <div class="p-3 bg-gray-50 dark:bg-slate-800/40 rounded-lg border border-gray-100 dark:border-gray-800">
                    <span class="block text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-0.5">Fecha Emisión</span>
                    <span class="text-xs font-medium text-gray-700 dark:text-gray-300">{{ $quotation->formatted_date }}</span>
                </div>
                <div class="p-3 bg-gray-50 dark:bg-slate-800/40 rounded-lg border border-gray-100 dark:border-gray-800">
                    <span class="block text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-0.5">Vencimiento</span>
                    <span class="text-xs font-medium text-gray-700 dark:text-gray-300">{{ $quotation->formatted_expires_at }}</span>
                </div>
                <div class="p-3 bg-gray-50 dark:bg-slate-800/40 rounded-lg border border-gray-100 dark:border-gray-800">
                    <span class="block text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-0.5">Estado</span>
                    @php
                        $badgeClasses = match($quotation->status) {
                            'approved' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-400',
                            'rejected' => 'bg-red-100 text-red-800 dark:bg-red-950/50 dark:text-red-400',
                            'expired' => 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-400',
                            default => 'bg-amber-100 text-amber-800 dark:bg-amber-950/50 dark:text-amber-400',
                        };
                    @endphp
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold {{ $badgeClasses }}">
                        {{ $quotation->status_label }}
                    </span>
                </div>
            </div>

            {{-- Tabla de Productos Cotizados (Componente Reutilizable) --}}
            <div class="mb-6">
                <span class="block text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">
                    Productos Cotizados ({{ $quotation->items->count() }})
                </span>
                <x-quotations.items-detail-table
                    :items="$quotation->items"
                    :amount="$quotation->amount"
                />
            </div>

            {{-- Observaciones y Vendedor --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                <div class="p-3 bg-gray-50 dark:bg-slate-800/40 rounded-lg border border-gray-100 dark:border-gray-800">
                    <span class="block text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-1">
                        Observaciones / Condiciones
                    </span>
                    <p class="text-gray-700 dark:text-gray-300 leading-relaxed">
                        {{ $quotation->notes ?: 'Ninguna observación registrada.' }}
                    </p>
                </div>
                <div class="p-3 bg-gray-50 dark:bg-slate-800/40 rounded-lg border border-gray-100 dark:border-gray-800">
                    <span class="block text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-1">
                        Registrado por
                    </span>
                    <p class="text-gray-700 dark:text-gray-300 font-medium">
                        {{ $quotation->user->name ?? 'Usuario del sistema' }} ({{ $quotation->user->email ?? '—' }})
                    </p>
                    <span class="text-[10px] text-gray-400">Creado el {{ $quotation->created_at->format('d/m/Y H:i') }}</span>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
