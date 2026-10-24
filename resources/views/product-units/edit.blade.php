<x-ui.edit-page title="Editar Unidad de Producto" category="Inventario" :backRoute="route('product-units.index')"
    :action="route('product-units.update', $productUnit)" method="PUT" submitText="Guardar Cambios" :columns="2" :fields="[
        [
            'type' => 'select',
            'name' => 'product_id',
            'label' => 'Producto',
            'value' => $productUnit->product_id,
            'options' => $products->pluck('name', 'id')->toArray(),
            'required' => true,
            'fullWidth' => true,
        ],
        [
            'type' => 'select',
            'name' => 'unit_id',
            'label' => 'Unidad de medida',
            'value' => $productUnit->unit_id,
            'options' => $units->toArray(),
            'required' => true,
            'help' => 'Elige de la lista. Si no está, créala en Inventario → Unidades de medida.',
        ],
        [
            'type' => 'number',
            'name' => 'factor',
            'label' => 'Factor de Conversión',
            'value' => (int) $productUnit->factor,
            'placeholder' => 'Ej. 12',
            'required' => true,
            'help' => 'Cuántas unidades base equivalen a esta presentación.',
        ],
        [
            'type' => 'number',
            'name' => 'price',
            'label' => 'Precio de Venta',
            'value' => $productUnit->price,
            'placeholder' => 'Ej. 55.00',
            'required' => true,
        ],
        [
            'type' => 'text',
            'name' => 'barcode',
            'label' => 'Código de Barras',
            'value' => $productUnit->barcode,
            'placeholder' => 'Opcional',
        ],
    ]">
    <label class="flex items-center gap-2 cursor-pointer">
        <input type="checkbox" name="is_base" value="1" @checked($productUnit->is_base)
            class="w-4 h-4 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 cursor-pointer">
        <span class="text-sm text-gray-700 dark:text-gray-300">Es la unidad base del producto</span>
    </label>

    @push('scripts')
        <script>
            (function() {
                const form = document.querySelector('form[action*="product-units"]');
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

                // Inicializar valores si factor > 1
                const initialFactor = parseFloat(factorInput.value) || 1;
                const initialSavedPrice = parseFloat(priceInput.value) || 0;

                if (initialFactor > 1 && initialSavedPrice > 0) {
                    totalInput.value = initialSavedPrice.toFixed(2);
                    priceInput.value = (initialSavedPrice / initialFactor).toFixed(2);
                }

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
</x-ui.edit-page>
