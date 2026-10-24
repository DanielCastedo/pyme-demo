<x-app-layout>
    <div class="max-w-10xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        @if (session('success'))
            <x-ui.alert type="success" :message="session('success')" />
        @endif

        @if (session('error'))
            <x-ui.alert type="error" :message="session('error')" />
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
                    'can' => 'crear product-units',
                    'label' => 'Nueva Unidad',
                    'variant' => 'primary',
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />',
                ],
            ];
        @endphp

        <x-ui.page-header category="Inventario" title="Unidades de Producto"
            description="Unidades de medida y presentaciones de venta por producto." :buttons="$buttons" />

        <div class="mb-5">
            <x-ui.search-box placeholder="Buscar por nombre, código de barras o producto..." filterName="buscar" />
        </div>

        @php
            $columns = [
                ['key' => 'product.name', 'label' => 'Producto'],
                ['key' => 'name', 'label' => 'Unidad'],
                ['key' => 'factor', 'label' => 'Factor'],
                ['key' => 'price', 'label' => 'Precio'],
                ['key' => 'barcode', 'label' => 'Código de Barras'],
            ];

            $actions = [
                ['type' => 'view', 'route' => 'product-units.show'],
                ['type' => 'edit', 'route' => 'product-units.edit'],
                ['type' => 'delete', 'route' => 'product-units.destroy', 'confirm' => '¿Eliminar esta unidad?'],
            ];
        @endphp

        <x-ui.data-table :rows="$productUnits" :columns="$columns" :actions="$actions" :paginator="$productUnits"
            emptyText="No hay unidades de producto registradas." />

        <x-ui.create-modal id="crud-modal" title="Registrar Nueva Unidad" :action="route('product-units.store')"
            submitText="Guardar Unidad" :columns="2" :fields="[
                [
                    'type' => 'select',
                    'name' => 'product_id',
                    'label' => 'Producto',
                    'options' => $products->pluck('name', 'id')->toArray(),
                    'required' => true,
                    'fullWidth' => true,
                ],
                [
                    'type' => 'select',
                    'name' => 'unit_id',
                    'label' => 'Unidad de medida',
                    'options' => $units->toArray(),
                    'required' => true,
                    'help' => 'Elige de la lista. Si no está, créala en Inventario → Unidades de medida.',
                ],
                [
                    'type' => 'number',
                    'name' => 'factor',
                    'label' => 'Factor de Conversión',
                    'placeholder' => 'Ej. 12',
                    'value' => 1,
                    'required' => true,
                    'help' => 'Cuántas unidades base equivalen a esta presentación.',
                ],
                [
                    'type' => 'number',
                    'name' => 'price',
                    'label' => 'Precio de Venta',
                    'placeholder' => 'Ej. 55.00',
                    'required' => true,
                ],
                [
                    'type' => 'text',
                    'name' => 'barcode',
                    'label' => 'Código de Barras',
                    'placeholder' => 'Opcional',
                ],
                [
                    'type' => 'checkbox',
                    'name' => 'is_base',
                    'label' => 'Es la unidad base del producto',
                ],
            ]" />
    </div>

    @push('scripts')
        <script>
            const crudModal = document.getElementById('crud-modal');
            document.getElementById('modal-toggle-button')?.addEventListener('click', () => crudModal.classList.remove('hidden'));
            document.querySelectorAll('[data-modal-close="crud-modal"]').forEach(el => {
                el.addEventListener('click', () => crudModal.classList.add('hidden'));
            });

            // Dinámica para Factor > 1 y cálculo de precio unitario vs total
            (function() {
                const modal = document.getElementById('crud-modal');
                if (!modal) return;

                const form = modal.querySelector('form');
                const factorInput = form?.querySelector('input[name="factor"]');
                const priceInput = form?.querySelector('input[name="price"]');
                if (!factorInput || !priceInput) return;

                const priceContainer = priceInput.closest('div');
                const priceLabel = priceContainer?.querySelector('label');

                // Crear contenedor para el campo calculado de precio total
                const totalContainer = document.createElement('div');
                totalContainer.id = 'total-price-wrapper';
                totalContainer.className = 'hidden mt-3';
                totalContainer.innerHTML = `
                    <label class="block text-xs font-semibold text-emerald-700 dark:text-emerald-400 mb-1.5 flex items-center justify-between">
                        <span>Precio Total de la Presentación (guardado en BD) <span class="text-red-500">*</span></span>
                        <span class="text-[10px] font-normal text-gray-500 dark:text-gray-400">Factor × Precio Unitario</span>
                    </label>
                    <div class="relative">
                        <input type="number" step="0.01" min="0" id="total_calculated_price" placeholder="0.00"
                            class="w-full px-3 h-10 text-sm font-bold rounded-lg border-2 border-emerald-500 dark:border-emerald-600 bg-emerald-50/50 dark:bg-emerald-950/20 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500">
                    </div>
                    <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-1">
                        Este es el monto total calculado que se guardará en la base de datos para esta presentación.
                    </p>
                `;

                priceContainer.parentNode.insertBefore(totalContainer, priceContainer.nextSibling);
                const totalInput = document.getElementById('total_calculated_price');

                let isUpdating = false;

                function updatePriceLogic() {
                    const factor = parseFloat(factorInput.value) || 1;
                    if (factor > 1) {
                        if (priceLabel) {
                            priceLabel.innerHTML = 'Precio Unitario (por pieza/unidad) <span class="text-red-500">*</span>';
                        }
                        totalContainer.classList.remove('hidden');

                        const unitVal = parseFloat(priceInput.value) || 0;
                        if (!isUpdating && unitVal > 0) {
                            totalInput.value = (unitVal * factor).toFixed(2);
                        }
                    } else {
                        if (priceLabel) {
                            priceLabel.innerHTML = 'Precio de Venta <span class="text-red-500">*</span>';
                        }
                        totalContainer.classList.add('hidden');
                    }
                }

                factorInput.addEventListener('input', updatePriceLogic);
                priceInput.addEventListener('input', function() {
                    const factor = parseFloat(factorInput.value) || 1;
                    if (factor > 1 && !isUpdating) {
                        const unitVal = parseFloat(priceInput.value) || 0;
                        totalInput.value = (unitVal * factor).toFixed(2);
                    }
                });

                totalInput.addEventListener('input', function() {
                    const factor = parseFloat(factorInput.value) || 1;
                    if (factor > 1) {
                        isUpdating = true;
                        const totVal = parseFloat(totalInput.value) || 0;
                        priceInput.value = (totVal / factor).toFixed(2);
                        isUpdating = false;
                    }
                });

                // Al enviar el formulario, el input con name="price" debe contener el TOTAL MULTIPLICADO
                form.addEventListener('submit', function() {
                    const factor = parseFloat(factorInput.value) || 1;
                    if (factor > 1) {
                        const totVal = parseFloat(totalInput.value);
                        if (!isNaN(totVal) && totVal > 0) {
                            priceInput.value = totVal.toFixed(2);
                        } else {
                            const unitVal = parseFloat(priceInput.value) || 0;
                            priceInput.value = (unitVal * factor).toFixed(2);
                        }
                    }
                });

                updatePriceLogic();
            })();
        </script>
    @endpush
</x-app-layout>
