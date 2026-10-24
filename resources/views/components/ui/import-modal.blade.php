@props([
    'id' => 'import-modal',
    'action',
    'templateRoute' => null,
    'triggerButtonId' => 'modal-import-toggle',
])

<div id="{{ $id }}" tabindex="-1" aria-hidden="true"
    class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm p-0 sm:p-4 flex items-end sm:items-center justify-center">
    <div class="w-full sm:max-w-lg sm:my-8">
        <div class="bg-white dark:bg-slate-900 rounded-t-2xl sm:rounded-2xl border border-gray-200 dark:border-slate-700 shadow-2xl overflow-hidden max-h-[90vh] flex flex-col">
            <div class="px-6 py-5 border-b border-gray-100 dark:border-slate-800 shrink-0 flex items-start justify-between">
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Importar Movimientos</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Carga masiva de entradas o salidas desde un archivo Excel.</p>
                </div>
                <button type="button" data-modal-close="{{ $id }}"
                    class="inline-flex items-center justify-center w-9 h-9 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-slate-800 transition shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form action="{{ $action }}" method="POST" enctype="multipart/form-data" class="px-6 py-5 overflow-y-auto">
                @csrf

                @if ($templateRoute)
                    <div class="mb-4 p-3 bg-sky-50 dark:bg-sky-900/20 border border-sky-200 dark:border-sky-800 rounded-lg text-xs text-sky-800 dark:text-sky-300 flex items-center justify-between gap-2">
                        <span>¿No tienes el formato? Descarga la plantilla de ejemplo.</span>
                        <a href="{{ $templateRoute }}" class="font-bold underline shrink-0">Descargar</a>
                    </div>
                @endif

                <div class="space-y-4">
                    <x-ui.form-field type="select" name="type" label="Tipo de Movimiento" required
                        :options="['entrada' => 'Entrada', 'salida' => 'Salida']" />

                    <x-ui.form-field type="select" name="reason" label="Motivo" required
                        :options="['compra' => 'Compra', 'venta' => 'Venta', 'ajuste' => 'Ajuste', 'devolucion' => 'Devolución', 'merma' => 'Merma', 'consignacion' => 'Consignación','inicial' => 'Inicial']" />

                    <x-ui.form-field type="file" name="file" label="Archivo Excel" required
                        help="Formatos: .xlsx, .xls, .csv — máx 5MB. Columnas requeridas: sku, cantidad. Opcionales: costo_unitario, fecha, referencia, notas." />
                </div>

                <div class="mt-6 pt-4 border-t border-gray-100 dark:border-slate-800 flex justify-end gap-2">
                    <x-ui.button type="button" variant="outline" data-modal-close="{{ $id }}">Cancelar</x-ui.button>
                    <x-ui.button type="submit" variant="primary">Importar</x-ui.button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        document.getElementById('{{ $triggerButtonId }}')?.addEventListener('click', () => {
            document.getElementById('{{ $id }}')?.classList.remove('hidden');
        });
    </script>
@endpush
