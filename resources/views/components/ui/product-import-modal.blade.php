{{-- resources/views/components/ui/product-import-modal.blade.php --}}
<div id="product-import-modal" tabindex="-1" aria-hidden="true"
    class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm p-0 sm:p-4 flex items-end sm:items-center justify-center">
    <div class="w-full sm:max-w-lg sm:my-8">
        <div class="bg-white dark:bg-slate-900 rounded-t-2xl sm:rounded-2xl border border-gray-200 dark:border-slate-700 shadow-2xl overflow-hidden max-h-[90vh] flex flex-col">
            <div class="px-6 py-5 border-b border-gray-100 dark:border-slate-800 shrink-0 flex items-start justify-between">
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Importación General de Productos</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Carga masiva desde Excel (crea y actualiza por Código/SKU), con foto, unidad de venta y stock inicial.</p>
                </div>
                <button type="button" data-modal-close="product-import-modal"
                    class="inline-flex items-center justify-center w-9 h-9 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-slate-800 transition shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form action="{{ route('products.import') }}" method="POST" enctype="multipart/form-data" class="px-6 py-5 overflow-y-auto">
                @csrf

                <div class="mb-4 p-3 bg-sky-50 dark:bg-sky-900/20 border border-sky-200 dark:border-sky-800 rounded-lg text-xs text-sky-800 dark:text-sky-300 flex items-center justify-between gap-2">
                    <span>¿No tienes el formato? Descarga la plantilla (trae las unidades de medida y categorías actuales, y las instrucciones).</span>
                    <a href="{{ route('products.template') }}" class="font-bold underline shrink-0">Descargar</a>
                </div>

                <x-ui.form-field type="select" name="warehouse_id" label="Almacén" required
                    :options="\App\Models\Warehouse::where('is_active', true)->orderBy('name')->pluck('name', 'id')->toArray()"
                    help="A este almacén entra el stock inicial (columna Cantidad) de todo el archivo." />

                <x-ui.form-field type="file" name="file" label="Archivo Excel" required
                    help="Solo .xlsx — máx 20MB. Columnas: Codigo, Item, Cantidad, Precio Venta U, Precio Compra U, Unidad, Color, Marca, Foto (imagen pegada en la celda), Medida, Categoria, Importado. Si el Código ya existe se actualiza el producto; si no, se crea. La Categoria es opcional: si la escribes se usa (se crea si no existe); vacía en un producto nuevo cae en «Importación General». Si una fila (no marcada «Importado») falla, no se importa nada." />

                <div class="mt-6 pt-4 border-t border-gray-100 dark:border-slate-800 flex justify-end gap-2">
                    <x-ui.button type="button" variant="outline" data-modal-close="product-import-modal">Cancelar</x-ui.button>
                    <x-ui.button type="submit" variant="primary">Importar</x-ui.button>
                </div>
            </form>
        </div>
    </div>
</div>
