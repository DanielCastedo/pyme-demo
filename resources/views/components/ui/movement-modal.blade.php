@props([
    'id' => 'movement-modal',
    'action',
    'products' => collect(),
    'triggerButtonId' => 'modal-movement-toggle',
])

<div id="{{ $id }}" tabindex="-1" aria-hidden="true"
    class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm p-0 sm:p-4 flex items-end sm:items-center justify-center">
    <div class="w-full sm:max-w-lg sm:my-8">
        <div class="bg-white dark:bg-slate-900 rounded-t-2xl sm:rounded-2xl border border-gray-200 dark:border-slate-700 shadow-2xl overflow-hidden max-h-[90vh] flex flex-col">
            <div class="px-6 py-5 border-b border-gray-100 dark:border-slate-800 shrink-0 flex items-start justify-between">
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Registrar Movimiento</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Registra una entrada o salida de inventario.</p>
                </div>
                <button type="button" data-modal-close="{{ $id }}"
                    class="inline-flex items-center justify-center w-9 h-9 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-slate-800 transition shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form action="{{ $action }}" method="POST" class="px-6 py-5 overflow-y-auto">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <x-ui.form-field type="select" name="product_id" label="Producto" required
                            :options="$products->pluck('name', 'id')->toArray()" />
                    </div>

                    <x-ui.form-field type="select" name="type" label="Tipo de Movimiento" required
                        :options="['entrada' => 'Entrada', 'salida' => 'Salida']" />

                    <x-ui.form-field type="select" name="reason" label="Motivo" required
                        :options="['compra' => 'Compra', 'venta' => 'Venta', 'ajuste' => 'Ajuste', 'devolucion' => 'Devolución', 'merma' => 'Merma','consignacion' => 'Consignación','inicial' => 'Inicial']" />

                    <x-ui.form-field type="number" name="quantity" label="Cantidad" required placeholder="0" />
                    <x-ui.form-field type="number" name="unit_cost" label="Costo Unitario" placeholder="Opcional" />
                    <x-ui.form-field type="date" name="movement_date" label="Fecha" required :value="date('Y-m-d')" />
                    <x-ui.form-field type="text" name="reference" label="Referencia" placeholder="N° factura / orden" />

                    <div class="sm:col-span-2">
                        <x-ui.form-field type="textarea" name="notes" label="Notas" :rows="2" />
                    </div>
                </div>

                <div class="mt-6 pt-4 border-t border-gray-100 dark:border-slate-800 flex justify-end gap-2">
                    <x-ui.button type="button" variant="outline" data-modal-close="{{ $id }}">Cancelar</x-ui.button>
                    <x-ui.button type="submit" variant="primary">Registrar</x-ui.button>
                </div>
            </form>
        </div>
    </div>
</div>

@once
    @push('scripts')
        <script>
            document.querySelectorAll('[data-modal-close]').forEach(el => {
                el.addEventListener('click', () => {
                    document.getElementById(el.dataset.modalClose)?.classList.add('hidden');
                });
            });
        </script>
    @endpush
@endonce

@push('scripts')
    <script>
        document.getElementById('{{ $triggerButtonId }}')?.addEventListener('click', () => {
            document.getElementById('{{ $id }}')?.classList.remove('hidden');
        });
    </script>
@endpush
