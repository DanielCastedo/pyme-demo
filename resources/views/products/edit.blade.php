<x-ui.edit-page title="Editar Producto" category="Inventario" :backRoute="route('products.index')"
    :action="route('products.update', $product)" method="PUT" submitText="Guardar Cambios" :columns="2"
    :hasFile="true" :fields="[
        [
            'type' => 'text',
            'name' => 'sku',
            'label' => 'SKU / Código',
            'value' => $product->sku,
            'placeholder' => 'Ej. HM-0001',
            'required' => true,
        ],
        [
            'type' => 'text',
            'name' => 'name',
            'label' => 'Nombre',
            'value' => $product->name,
            'placeholder' => 'Ej. Martillo de Uña 16oz',
            'required' => true,
        ],
        [
            'type' => 'select',
            'name' => 'category_id',
            'label' => 'Categoría',
            'value' => $product->category_id,
            'options' => $categories->pluck('name', 'id')->toArray(),
            'required' => true,
            'actionButton' => [
                'id' => 'btn-quick-category-create',
                'label' => '+ Nueva categoría',
            ],
        ],
        [
            'type' => 'text',
            'name' => 'marca',
            'label' => 'Marca',
            'value' => $product->marca,
            'placeholder' => 'Ej. Stanley',
        ],
        [
            'type' => 'text',
            'name' => 'color',
            'label' => 'Color',
            'value' => $product->color,
            'placeholder' => 'Ej. Rojo',
        ],
        [
            'type' => 'text',
            'name' => 'medida',
            'label' => 'Medida',
            'value' => $product->medida,
            'placeholder' => 'Ej. 16oz, 1/2 pulgada',
        ],
        [
            'type' => 'textarea',
            'name' => 'description',
            'label' => 'Descripción',
            'value' => $product->description,
            'placeholder' => 'Ej. Mango de fibra de vidrio.',
            'fullWidth' => true,
            'rows' => 3,
        ],
        [
            'type' => 'file',
            'name' => 'photo',
            'label' => 'Foto del Producto',
            'fullWidth' => true,
            'preview' => $product->photo_url,
            'help' => 'Formatos admitidos: JPG, PNG o WEBP. Máx 10MB (se optimiza automáticamente). Deja vacío para conservar la actual.',
        ],
    ]">
    <label class="flex items-center gap-2 cursor-pointer">
        <input type="checkbox" name="is_active" value="1" @checked($product->is_active)
            class="w-4 h-4 rounded border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-emerald-600 focus:ring-emerald-500 cursor-pointer">
        <span class="text-sm text-gray-700 dark:text-gray-300">Producto activo</span>
    </label>

    {{-- Unidad de venta: igual que al crear. Solo se ofrece si el producto todavía no tiene unidad base. --}}
    @php $baseUnit = $product->units->firstWhere('is_base', true); @endphp
    @if ($baseUnit)
        <div class="mt-4 p-3 rounded-lg bg-sky-50 dark:bg-sky-900/20 border border-sky-200 dark:border-sky-800 text-xs text-sky-800 dark:text-sky-300">
            Este producto ya tiene su unidad de venta base: <strong>{{ $baseUnit->name }}</strong> a {{ number_format((float) $baseUnit->price, 2) }}.
            Para cambiar su precio o agregar otras presentaciones (caja, docena...) usa
            <a href="{{ route('product-units.index') }}" class="font-bold underline">Unidades de Producto</a>.
        </div>
    @else
        <div id="edit-unit-section" class="mt-4 p-4 rounded-lg border border-gray-200 dark:border-slate-700 space-y-4">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="create_unit" id="edit-create-unit" value="1" @checked(old('create_unit'))
                    class="w-4 h-4 rounded border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-emerald-600 focus:ring-emerald-500 cursor-pointer">
                <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Crear también su unidad de venta (unidad base, factor 1)</span>
            </label>

            <div class="edit-unit-field hidden grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">Unidad de medida</label>
                    <x-ui.form-field type="select" name="unit_id" :value="old('unit_id', $units->firstWhere('name', 'Unidad')?->id)"
                        :options="$units->pluck('name', 'id')->toArray()" />
                    <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-1">Elige de la lista (se administra en Inventario → Unidades de medida).</p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">Precio de venta</label>
                    <x-ui.form-field type="number" name="unit_price" placeholder="0.00" :value="old('unit_price')" />
                    <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-1">Es el precio con el que se vende esta unidad. Su código de barras será el SKU del producto.</p>
                </div>
            </div>
        </div>

        <script>
            // Los datos de la unidad solo aparecen (y son obligatorios) si se marca "crear también su unidad".
            (() => {
                const toggle = document.getElementById('edit-create-unit');
                const section = document.getElementById('edit-unit-section');
                if (!toggle || !section) return;
                const fields = section.querySelectorAll('.edit-unit-field');
                const price = section.querySelector('input[name="unit_price"]');
                const unitField = section.querySelector('[name="unit_id"]');
                const unitText = unitField?.closest('[data-ac-wrapper]')?.querySelector('[data-ac-input]') ?? unitField;
                const sync = () => {
                    fields.forEach((el) => el.classList.toggle('hidden', !toggle.checked));
                    if (price) { price.required = toggle.checked; price.step = '0.01'; price.min = '0'; }
                    if (unitText) unitText.required = toggle.checked;
                };
                toggle.addEventListener('change', sync);
                sync();
            })();
        </script>
    @endif
</x-ui.edit-page>

    {{-- Modal del lector de código para el campo SKU --}}
    <div id="sku-scanner-modal" tabindex="-1" aria-hidden="true"
        class="hidden fixed inset-0 z-[60] bg-slate-900/70 backdrop-blur-sm p-4 flex items-center justify-center">
        <div class="w-full max-w-sm bg-white dark:bg-slate-900 rounded-2xl border border-gray-200 dark:border-slate-700 shadow-2xl overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 dark:border-slate-800 flex items-center justify-between">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white">Escanear Código</h3>
                <button type="button" id="sku-scanner-close" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="p-4">
                <div id="sku-reader" class="w-full rounded-lg overflow-hidden"></div>
                <div id="sku-scan-feedback"
                    class="hidden mt-3 p-2.5 rounded-lg text-xs font-medium text-center bg-emerald-50 text-emerald-700">
                </div>
                <p class="text-[11px] text-gray-500 dark:text-gray-400 text-center mt-3">
                    Apunta la cámara al código de barras o QR del producto.
                </p>
            </div>
        </div>
    </div>
    {{-- Modal rápido para registrar categoría sin salir de la vista --}}
    <div id="quick-category-modal" tabindex="-1" aria-hidden="true"
        class="hidden fixed inset-0 z-[70] bg-slate-900/60 backdrop-blur-sm p-4 flex items-center justify-center">
        <div class="w-full max-w-md bg-white dark:bg-slate-900 rounded-2xl border border-gray-200 dark:border-slate-700 shadow-2xl overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 dark:border-slate-800 flex items-center justify-between">
                <h3 class="text-base font-bold text-gray-900 dark:text-white">Nueva Categoría</h3>
                <button type="button" id="quick-category-close" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 text-xl leading-none">&times;</button>
            </div>
            <form id="quick-category-form" class="p-5 space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">
                        Nombre de la Categoría <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="quick-category-name" required placeholder="Ej. Grifería, Herramientas, etc."
                        class="w-full px-3 h-10 text-sm rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-emerald-500">
                    <p id="quick-category-error" class="hidden text-xs text-red-500 mt-1"></p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Descripción (opcional)</label>
                    <textarea id="quick-category-description" rows="2" placeholder="Descripción breve..."
                        class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-emerald-500"></textarea>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" id="quick-category-cancel"
                        class="px-4 py-2 rounded-lg border border-gray-300 dark:border-slate-600 text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-800 transition">
                        Cancelar
                    </button>
                    <button type="submit" id="quick-category-submit"
                        class="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold transition">
                        Guardar Categoría
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            // Gestión rápida de creación de categoría
            (function() {
                const quickCategoryModal = document.getElementById('quick-category-modal');
                const quickCategoryBtn = document.getElementById('btn-quick-category-create');
                const quickCategoryClose = document.getElementById('quick-category-close');
                const quickCategoryCancel = document.getElementById('quick-category-cancel');
                const quickCategoryForm = document.getElementById('quick-category-form');
                const quickCategoryName = document.getElementById('quick-category-name');
                const quickCategoryDesc = document.getElementById('quick-category-description');
                const quickCategoryError = document.getElementById('quick-category-error');
                const quickCategorySubmit = document.getElementById('quick-category-submit');

                function openQuickCategory() {
                    if (!quickCategoryModal) return;
                    quickCategoryName.value = '';
                    quickCategoryDesc.value = '';
                    quickCategoryError.classList.add('hidden');
                    quickCategoryError.textContent = '';
                    quickCategoryModal.classList.remove('hidden');
                    setTimeout(() => quickCategoryName.focus(), 50);
                }

                function closeQuickCategory() {
                    if (quickCategoryModal) quickCategoryModal.classList.add('hidden');
                }

                quickCategoryBtn?.addEventListener('click', openQuickCategory);
                quickCategoryClose?.addEventListener('click', closeQuickCategory);
                quickCategoryCancel?.addEventListener('click', closeQuickCategory);

                quickCategoryForm?.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    const name = quickCategoryName.value.trim();
                    if (!name) return;

                    quickCategoryError.classList.add('hidden');
                    quickCategorySubmit.disabled = true;
                    quickCategorySubmit.textContent = 'Guardando...';

                    try {
                        const res = await fetch("{{ route('categories.store') }}", {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': "{{ csrf_token() }}",
                            },
                            body: JSON.stringify({
                                name: name,
                                description: quickCategoryDesc.value.trim() || null,
                            })
                        });

                        const data = await res.json();
                        if (!res.ok) {
                            throw new Error(data.message || 'Error al crear la categoría.');
                        }

                        // Seleccionar la nueva categoría en el autocompletado del formulario
                        const categoryHidden = document.querySelector('input[name="category_id"]');
                        if (categoryHidden) {
                            categoryHidden.value = data.category.id;
                            const wrapper = categoryHidden.closest('[data-ac-wrapper]');
                            if (wrapper) {
                                const acInput = wrapper.querySelector('[data-ac-input]');
                                if (acInput) acInput.value = data.category.name;
                            }
                            categoryHidden.dispatchEvent(new Event('change', { bubbles: true }));
                        }

                        closeQuickCategory();
                    } catch (err) {
                        quickCategoryError.textContent = err.message;
                        quickCategoryError.classList.remove('hidden');
                    } finally {
                        quickCategorySubmit.disabled = false;
                        quickCategorySubmit.textContent = 'Guardar Categoría';
                    }
                });
            })();
        </script>
        <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
        <script>
            // Caja de escaneo ancha (para que entren códigos de barra largos) pero
            // con alto generoso también (para que un QR, que es cuadrado, entre
            // cómodo sin tener que alejarlo demasiado). No hace falta que la caja
            // tenga la misma forma del código — solo que lo contenga completo.
            function buildScannerConfig() {
                const config = {
                    fps: 15,
                    qrbox: function(viewfinderWidth, viewfinderHeight) {
                        const width = Math.floor(viewfinderWidth * 0.85);
                        const height = Math.max(200, Math.floor(viewfinderHeight * 0.7));
                        return {
                            width,
                            height
                        };
                    },
                    disableFlip: false,
                };

                const formats = getSupportedFormats();
                if (formats) {
                    config.formatsToSupport = formats;
                }

                return config;
            }

            // Restringe los formatos a QR + códigos de barra de catálogo reales — el
            // decodificador tarda menos y acierta más si no tiene que probar todos los
            // formatos posibles en cada cuadro. Si el enum no trae alguno de estos
            // nombres (según la versión que sirva el CDN), simplemente se lo salta en
            // vez de romper.
            function getSupportedFormats() {
                if (typeof Html5QrcodeSupportedFormats === 'undefined') return undefined;

                const wanted = ['QR_CODE', 'EAN_13', 'EAN_8', 'UPC_A', 'UPC_E', 'CODE_128', 'CODE_39', 'CODABAR', 'ITF'];
                const formats = wanted
                    .map((name) => Html5QrcodeSupportedFormats[name])
                    .filter((value) => value !== undefined);

                return formats.length ? formats : undefined;
            }

            // Intenta activar el autoenfoque continuo DESPUÉS de que la cámara ya está
            // funcionando (no al pedirla, como antes) — así, si el navegador/cámara no
            // lo soporta, simplemente no hace nada en vez de tumbar todo el acceso a
            // la cámara. Esto es lo que más ayuda a que no salga borroso al acercar
            // el código.
            function tryEnableContinuousFocus(instance) {
                if (!instance || typeof instance.applyVideoConstraints !== 'function') return;

                try {
                    instance.applyVideoConstraints({
                        advanced: [{
                            focusMode: 'continuous'
                        }]
                    }).catch(() => {});
                } catch (e) {
                    // Mejora opcional: si falla, no debe afectar el escaneo.
                }
            }

            // Intenta enfocar lo más cerca posible: consulta el rango de distancia de
            // enfoque que soporta la cámara y, si existe, fuerza un enfoque manual en
            // el extremo más cercano (focusDistance mínimo). Si la cámara no expone
            // esa capacidad, cae de vuelta al autoenfoque continuo de siempre.
            function tryFocusAsCloseAsPossible(instance) {
                if (!instance) return;

                try {
                    if (typeof instance.getRunningTrackCameraCapabilities === 'function') {
                        const capabilities = instance.getRunningTrackCameraCapabilities();
                        const focusDistanceRange = typeof capabilities?.focusDistance === 'function' ?
                            capabilities.focusDistance() :
                            capabilities?.focusDistance;

                        if (focusDistanceRange && typeof focusDistanceRange.min === 'number') {
                            instance.applyVideoConstraints({
                                advanced: [{
                                    focusMode: 'manual',
                                    focusDistance: focusDistanceRange.min
                                }]
                            }).then(() => {}).catch(() => {
                                tryEnableContinuousFocus(instance);
                            });
                            return;
                        }
                    }
                } catch (e) {
                    // Si la cámara no soporta consultar capacidades, seguimos con el plan B.
                }

                tryEnableContinuousFocus(instance);
            }

            // Valida el dígito verificador de códigos de barra tipo EAN-13/UPC-A/EAN-8
            // (el número que va justo debajo de las líneas) para descartar lecturas
            // borrosas o mal decodificadas antes de aceptarlas como buenas.
            function isValidBarcodeChecksum(code) {
                if (!/^\d+$/.test(code)) return true;

                let digits = code;
                if (digits.length === 12) digits = '0' + digits; // UPC-A -> EAN-13
                if (digits.length === 13) return checkEanChecksum(digits, 12, 1, 3);
                if (digits.length === 8) return checkEanChecksum(digits, 7, 3, 1);

                return true;
            }

            function checkEanChecksum(digits, dataLength, weightEven, weightOdd) {
                const nums = digits.split('').map(Number);
                let sum = 0;
                for (let i = 0; i < dataLength; i++) {
                    sum += nums[i] * (i % 2 === 0 ? weightEven : weightOdd);
                }
                const check = (10 - (sum % 10)) % 10;
                return check === nums[dataLength];
            }

            function isEanLikeFormat(formatName) {
                return ['EAN_13', 'UPC_A', 'EAN_8'].includes(formatName);
            }

            function getDecodedFormatName(decodedResult) {
                try {
                    const format = decodedResult && decodedResult.result && decodedResult.result.format;
                    return (format && (format.formatName || format)) || null;
                } catch (e) {
                    return null;
                }
            }

            // Enumera las cámaras del dispositivo y arranca con la que el navegador
            // identifique como trasera (por nombre); si no hay ninguna así (típico en
            // PC/laptop), usa la primera disponible. No se pide directamente
            // {facingMode: 'environment'} porque en varias combinaciones de
            // navegador/driver eso hace fallar getUserMedia entero en vez de
            // ignorarse — con getCameras() alcanza pedir permiso genérico de video y
            // elegir el dispositivo a mano.
            //
            // El id de cámara elegido se cachea para no volver a enumerar cámaras
            // cada vez que se abre el modal — eso hacía sentir la apertura más lenta
            // de lo normal. Si el id cacheado deja de servir, se limpia solo y se
            // vuelve a enumerar una vez.
            let cachedCameraId = null;

            function startScannerAnyCamera(instance, onSuccess) {
                if (cachedCameraId) {
                    return instance.start(cachedCameraId, buildScannerConfig(), onSuccess, () => {})
                        .catch((err) => {
                            console.warn('El id de cámara cacheado ya no sirve, reintentando desde cero...', err);
                            cachedCameraId = null;
                            return startScannerAnyCamera(instance, onSuccess);
                        });
                }

                return Html5Qrcode.getCameras().then((cameras) => {
                    if (!cameras || !cameras.length) {
                        return Promise.reject(new Error('No se detectó ninguna cámara en este dispositivo.'));
                    }

                    const back = cameras.find(c => /back|rear|trasera|environment/i.test(c.label || ''));
                    const cameraId = (back || cameras[0]).id;
                    cachedCameraId = cameraId;

                    return instance.start(cameraId, buildScannerConfig(), onSuccess, () => {});
                });
            }

            function describeScannerError(err) {
                if (!err) return 'Error desconocido.';
                return err.name ? `${err.name}: ${err.message || ''}` : String(err);
            }
        </script>
        <script>
            (function() {
                const skuInput = document.querySelector('input[name="sku"]');
                if (!skuInput) return;

                const wrapper = document.createElement('div');
                wrapper.className = 'flex items-center gap-2';

                skuInput.parentNode.insertBefore(wrapper, skuInput);
                wrapper.appendChild(skuInput);
                skuInput.classList.add('flex-1');

                const scanBtn = document.createElement('button');
                scanBtn.type = 'button';
                scanBtn.className =
                    'shrink-0 w-10 h-10 flex items-center justify-center rounded-lg border border-sky-300 dark:border-sky-700 text-sky-600 dark:text-sky-400 hover:bg-sky-50 dark:hover:bg-sky-900/20 transition';
                scanBtn.title = 'Escanear código con la cámara';
                scanBtn.innerHTML = `
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 13a3 3 0 100 6 3 3 0 000-6z" />
                    </svg>
                `;
                wrapper.appendChild(scanBtn);

                const modal = document.getElementById('sku-scanner-modal');
                const closeBtn = document.getElementById('sku-scanner-close');
                const reader = document.getElementById('sku-reader');
                const feedback = document.getElementById('sku-scan-feedback');
                let html5QrCode = null;
                let scannerActive = false;

                function startScanner() {
                    reader.innerHTML = '';
                    html5QrCode = new Html5Qrcode('sku-reader');
                    startScannerAnyCamera(html5QrCode, (decodedText, decodedResult) => {
                        const formatName = getDecodedFormatName(decodedResult);
                        if (isEanLikeFormat(formatName) && !isValidBarcodeChecksum(decodedText)) {
                            return; // dígito verificador no cierra: probable lectura borrosa, sigue escaneando
                        }

                        skuInput.value = decodedText;
                        feedback.textContent = '✓ Escaneado correctamente: ' + decodedText;
                        feedback.classList.remove('hidden');
                        if (navigator.vibrate) navigator.vibrate(100);

                        setTimeout(() => {
                            stopScanner();
                            modal.classList.add('hidden');
                        }, 900);
                    }).then(() => {
                        scannerActive = true;
                        tryFocusAsCloseAsPossible(html5QrCode);
                    }).catch((err) => {
                        console.error('No se pudo iniciar la cámara', err);
                        reader.innerHTML =
                            '<p class="text-xs text-red-500 p-4 text-center">No se pudo acceder a la cámara.<br><span class="font-mono text-[10px]">' +
                            describeScannerError(err) + '</span></p>';
                    });
                }

                function stopScanner() {
                    if (!scannerActive || !html5QrCode) return;
                    html5QrCode.stop().then(() => {
                        scannerActive = false;
                        html5QrCode.clear();
                    }).catch(() => {});
                }

                scanBtn.addEventListener('click', () => {
                    feedback.classList.add('hidden');
                    modal.classList.remove('hidden');
                    startScanner();
                });

                closeBtn.addEventListener('click', () => {
                    stopScanner();
                    modal.classList.add('hidden');
                });
            })();
        </script>
    @endpush
