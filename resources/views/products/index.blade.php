<x-app-layout>
    <div class="max-w-10xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        @if (session('success'))
            <x-ui.alert type="success" :message="session('success')" />
        @endif

        @if (session('error'))
            <x-ui.alert type="error">
                {{ session('error') }}
                @if (session('import_errors'))
                    <ul class="list-disc pl-4 mt-2 space-y-0.5 text-xs max-h-48 overflow-y-auto">
                        @foreach (session('import_errors') as $importError)
                            <li>{{ $importError }}</li>
                        @endforeach
                    </ul>
                @endif
            </x-ui.alert>
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
            $buttons = [];

            if (auth()->user()->can('productos.importar')) {
                $buttons[] = [
                    'id' => 'product-import-toggle',
                    'label' => 'Importar',
                    'variant' => 'outline',
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h12a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM4 10h16M4 15h16M10 4v16" />',
                ];
            }

            if (auth()->user()->can('productos.exportar')) {
                $buttons[] = [
                    'route' => route('products.export', request()->query()),
                    'label' => 'Exportar',
                    'variant' => 'outline',
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1M8 12l4 4m0 0l4-4m-4 4V4" />',
                ];
            }

            if (auth()->user()->can('create', \App\Models\Product::class)) {
                $buttons[] = [
                    'id' => 'modal-toggle-button',
                    'label' => 'Nuevo Producto',
                    'variant' => 'primary',
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />',
                ];
            }
        @endphp

        <x-ui.page-header category="Inventario" title="Productos"
            description="Gestiona el catálogo de productos." :buttons="$buttons" />

        <div class="mb-5 flex items-center gap-2">
            <div class="flex-1">
                <x-ui.search-box placeholder="Buscar por SKU, nombre o categoría..." filterName="buscar" />
            </div>
            <button type="button" id="product-scan-btn" title="Buscar producto escaneando su código"
                class="shrink-0 w-11 h-11 flex items-center justify-center rounded-lg border border-sky-300 dark:border-sky-700 text-sky-600 dark:text-sky-400 hover:bg-sky-50 dark:hover:bg-sky-900/20 transition">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 13a3 3 0 100 6 3 3 0 000-6z" />
                </svg>
            </button>
        </div>

        @php
            $columns = [
                ['key' => 'photo_url', 'label' => 'Foto', 'type' => 'image'],
                ['key' => 'sku', 'label' => 'SKU'],
                ['key' => 'name', 'label' => 'Nombre'],
                ['key' => 'marca', 'label' => 'Marca'],
                ['key' => 'category.name', 'label' => 'Categoría'],
                ['key' => 'stock_label', 'label' => 'Stock Total'],
            ];

            $actions = [
                ['type' => 'view', 'route' => 'products.show'],
                ['type' => 'edit', 'route' => 'products.edit'],
                ['type' => 'delete', 'route' => 'products.destroy', 'confirm' => '¿Eliminar este producto?'],
            ];
        @endphp

        <x-ui.data-table :rows="$products" :columns="$columns" :actions="$actions" :paginator="$products"
            emptyText="No hay productos registrados." />

        @can('productos.importar')
            <x-ui.product-import-modal />
        @endcan

        @can('create', \App\Models\Product::class)
            <x-ui.create-modal id="crud-modal" title="Registrar Nuevo Producto" :action="route('products.store')"
                submitText="Guardar Producto" :columns="2" :hasFile="true" :fields="[
                    [
                        'type' => 'text',
                        'name' => 'sku',
                        'label' => 'SKU / Código',
                        'placeholder' => 'Ej. HM-0001',
                        'required' => true,
                    ],
                    [
                        'type' => 'text',
                        'name' => 'name',
                        'label' => 'Nombre',
                        'placeholder' => 'Ej. Martillo de Uña 16oz',
                        'required' => true,
                    ],
                    [
                        'type' => 'select',
                        'name' => 'category_id',
                        'label' => 'Categoría',
                        'options' => $categories->pluck('name', 'id')->toArray(),
                        'required' => true,
                        'actionButton' => [
                            'id' => 'btn-quick-category-create',
                            'label' => '+ Nueva categoría',
                        ],
                    ],
                    [
                        'type' => 'checkbox',
                        'name' => 'is_active',
                        'label' => 'Producto activo',
                        'value' => true,
                    ],
                    [
                        'type' => 'text',
                        'name' => 'marca',
                        'label' => 'Marca',
                        'placeholder' => 'Ej. Stanley',
                    ],
                    [
                        'type' => 'text',
                        'name' => 'color',
                        'label' => 'Color',
                        'placeholder' => 'Ej. Rojo',
                    ],
                    [
                        'type' => 'text',
                        'name' => 'medida',
                        'label' => 'Medida',
                        'placeholder' => 'Ej. 16oz, 1/2 pulgada',
                    ],
                    [
                        'type' => 'textarea',
                        'name' => 'description',
                        'label' => 'Descripción',
                        'placeholder' => 'Ej. Mango de fibra de vidrio.',
                        'fullWidth' => true,
                        'rows' => 2,
                    ],
                    [
                        'type' => 'checkbox',
                        'name' => 'create_unit',
                        'label' => 'Crear también su unidad de venta (unidad base, factor 1)',
                        'value' => true,
                        'fullWidth' => true,
                    ],
                    [
                        'type' => 'select',
                        'name' => 'unit_id',
                        'label' => 'Unidad de medida',
                        'value' => $units->firstWhere('name', 'Unidad')?->id,
                        'options' => $units->pluck('name', 'id')->toArray(),
                        'help' => 'Elige de la lista (se administra en Inventario → Unidades de medida).',
                        'wrapperClass' => 'unit-field hidden',
                    ],
                    [
                        'type' => 'number',
                        'name' => 'unit_price',
                        'label' => 'Precio de venta',
                        'placeholder' => '0.00',
                        'help' => 'Es el precio con el que se vende esta unidad. Su código de barras será el SKU del producto.',
                        'wrapperClass' => 'unit-field hidden',
                    ],
                    [
                        'type' => 'file',
                        'name' => 'photo',
                        'label' => 'Foto del Producto',
                        'help' => 'Formatos admitidos: JPG, PNG o WEBP. Máx 10MB (se optimiza automáticamente).',
                        'fullWidth' => true,
                    ],
                ]" />
        @endcan

        {{-- Modal del lector de código para el campo SKU --}}
        <div id="sku-scanner-modal" tabindex="-1" aria-hidden="true"
            class="hidden fixed inset-0 z-[60] bg-slate-900/70 backdrop-blur-sm p-4 flex items-center justify-center">
            <div
                class="w-full max-w-sm bg-white dark:bg-slate-900 rounded-2xl border border-gray-200 dark:border-slate-700 shadow-2xl overflow-hidden">
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

        {{-- Modal del lector de código para buscar producto --}}
        <div id="product-scanner-modal" tabindex="-1" aria-hidden="true"
            class="hidden fixed inset-0 z-[60] bg-slate-900/70 backdrop-blur-sm p-4 flex items-center justify-center">
            <div
                class="w-full max-w-sm bg-white dark:bg-slate-900 rounded-2xl border border-gray-200 dark:border-slate-700 shadow-2xl overflow-hidden">
                <div
                    class="px-5 py-4 border-b border-gray-100 dark:border-slate-800 flex items-center justify-between">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white">Buscar Producto</h3>
                    <button type="button" id="product-scanner-close" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="p-4">
                    <div id="product-scan-reader" class="w-full rounded-lg overflow-hidden"></div>
                    <div id="product-scan-feedback"
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
    </div>

    @push('scripts')
        <script>
            // Gestión rápida de creación de categoría
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

            const importModal = document.getElementById('product-import-modal');
            document.getElementById('product-import-toggle')?.addEventListener('click', () => importModal?.classList.remove('hidden'));
            document.querySelectorAll('[data-modal-close="product-import-modal"]').forEach(el => {
                el.addEventListener('click', () => importModal?.classList.add('hidden'));
            });

            const crudModal = document.getElementById('crud-modal');
            document.getElementById('modal-toggle-button')?.addEventListener('click', () => crudModal.classList.remove('hidden'));
            document.querySelectorAll('[data-modal-close="crud-modal"]').forEach(el => {
                el.addEventListener('click', () => crudModal.classList.add('hidden'));
            });

            // Crear la unidad base junto con el producto: los datos de la unidad solo aparecen (y son obligatorios) si se marca.
            (() => {
                const toggle = crudModal?.querySelector('input[name="create_unit"]');
                if (!toggle) return;
                const fields = crudModal.querySelectorAll('.unit-field');
                const price = crudModal.querySelector('input[name="unit_price"]');
                // La lista de unidades es un buscador: el campo que el usuario ve (y que puede ser obligatorio) está junto al valor oculto.
                const unitField = crudModal.querySelector('[name="unit_id"]');
                const unitText = unitField?.closest('[data-ac-wrapper]')?.querySelector('[data-ac-input]') ?? unitField;
                const sync = () => {
                    fields.forEach((el) => el.classList.toggle('hidden', !toggle.checked));
                    if (price) { price.required = toggle.checked; price.step = '0.01'; price.min = '0'; }
                    if (unitText) unitText.required = toggle.checked;
                    if (toggle.checked) price?.focus();
                };
                toggle.addEventListener('change', sync);
                sync();
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

            // Autoenfoque continuo, aplicado DESPUÉS de que la cámara ya está
            // funcionando (no al pedirla) — si el navegador/cámara no lo soporta,
            // simplemente no hace nada en vez de tumbar el acceso a la cámara.
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

            // El autoenfoque continuo de muchas webcams igual prioriza distancias
            // "normales" y no cierra bien de cerca. Si la cámara expone sus
            // capacidades reales, se le pide foco MANUAL fijado en la distancia más
            // cercana que soporte — eso sí obliga a enfocar de cerca. Si no se puede
            // averiguar (cámara/navegador no expone capacidades), se cae al
            // autoenfoque continuo de siempre. Todo esto corre después de que el
            // stream ya está activo, así que si algo no es soportado no rompe nada.
            function tryFocusAsCloseAsPossible(instance) {
                if (!instance) return;

                try {
                    if (typeof instance.getRunningTrackCameraCapabilities === 'function') {
                        const capabilities = instance.getRunningTrackCameraCapabilities();
                        const focusDistanceRange = typeof capabilities?.focusDistance === 'function'
                            ? capabilities.focusDistance()
                            : capabilities?.focusDistance;

                        if (focusDistanceRange && typeof focusDistanceRange.min === 'number') {
                            instance.applyVideoConstraints({
                                advanced: [{
                                    focusMode: 'manual',
                                    focusDistance: focusDistanceRange.min
                                }]
                            }).then(() => {
                                // OK, foco manual cercano aplicado.
                            }).catch(() => {
                                tryEnableContinuousFocus(instance);
                            });
                            return;
                        }
                    }
                } catch (e) {
                    // Sigue con el intento genérico de abajo.
                }

                tryEnableContinuousFocus(instance);
            }

            // Los códigos EAN-13/UPC-A/EAN-8 (los números impresos debajo de las
            // barras) traen un último dígito "verificador", calculado a partir de
            // los demás. Si la cámara lee un dígito mal por el desenfoque/ángulo, el
            // texto decodificado no va a cumplir esa fórmula — así que se puede usar
            // para detectar y descartar lecturas erróneas antes de aceptarlas, sin
            // tener que mejorar la imagen en sí.
            function isValidBarcodeChecksum(code) {
                if (!/^\d+$/.test(code)) return true; // no es numérico puro, no se filtra

                let digits = code;
                if (digits.length === 12) {
                    digits = '0' + digits; // UPC-A: se valida igual que un EAN-13 con cero a la izquierda
                }

                if (digits.length === 13) {
                    return checkEanChecksum(digits, 12, 1, 3);
                }

                if (digits.length === 8) {
                    return checkEanChecksum(digits, 7, 3, 1);
                }

                return true; // longitud no reconocida: no se filtra, para no generar falsos negativos
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

            // Formatos de código de barra que traen dígito verificador — a estos sí
            // se les aplica la validación. QR y otros formatos (CODE_128, CODE_39...)
            // no tienen esta fórmula, así que se aceptan tal cual las decodifica la
            // librería.
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
            // PC/laptop, que no tienen "cámara trasera"), usa la primera disponible.
            // No se pide directamente {facingMode: 'environment'} porque en varias
            // combinaciones de navegador/driver eso hace fallar getUserMedia entero
            // en vez de ignorarse — con getCameras() alcanza pedir permiso genérico
            // de video y elegir el dispositivo a mano.
            //
            // El id de cámara elegido se cachea (compartido entre los lectores de
            // esta página) para no volver a enumerar cámaras cada vez que se abre el
            // modal — eso es lo que hacía sentir la apertura más lenta de lo normal.
            // Si el id cacheado deja de servir (cámara desconectada, etc.), se limpia
            // solo y se vuelve a enumerar una vez.
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
                const skuInput = document.querySelector('#crud-modal input[name="sku"]');
                if (!skuInput) return;

                // Inyecta el botón de escaneo junto al input de SKU.
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

        <script>
            (function() {
                const scanBtn = document.getElementById('product-scan-btn');
                const modal = document.getElementById('product-scanner-modal');
                const closeBtn = document.getElementById('product-scanner-close');
                const reader = document.getElementById('product-scan-reader');
                const feedback = document.getElementById('product-scan-feedback');

                if (!scanBtn) return;

                let html5QrCode = null;
                let scannerActive = false;

                function startScanner() {
                    reader.innerHTML = '';
                    html5QrCode = new Html5Qrcode('product-scan-reader');
                    startScannerAnyCamera(html5QrCode, (decodedText, decodedResult) => {
                        if (!scannerActive) return;

                        const formatName = getDecodedFormatName(decodedResult);
                        if (isEanLikeFormat(formatName) && !isValidBarcodeChecksum(decodedText)) {
                            return; // dígito verificador no cierra: probable lectura borrosa, sigue escaneando
                        }

                        scannerActive = false;

                        feedback.textContent = '✓ Escaneado correctamente: ' + decodedText;
                        feedback.classList.remove('hidden');
                        if (navigator.vibrate) navigator.vibrate(100);

                        setTimeout(() => {
                            stopScanner();
                            // Redirige al listado filtrado por ese código — reutiliza el mismo
                            // buscador de SKU/nombre/categoría que ya tiene la página.
                            const url = new URL(window.location.href);
                            url.searchParams.set('filter[buscar]', decodedText);
                            window.location.href = url.toString();
                        }, 700);
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
                    if (!html5QrCode) return;
                    html5QrCode.stop().then(() => html5QrCode.clear()).catch(() => {});
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
</x-app-layout>
