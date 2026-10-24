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

            if (auth()->user()->can('kardex.importar')) {
                $buttons[] = [
                    'id' => 'stock-import-toggle',
                    'label' => 'Importar Excel',
                    'variant' => 'outline',
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h12a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM4 10h16M4 15h16M10 4v16" />',
                ];
            }

            $buttons[] = [
                'route' => route('stock-movements.create'),
                'can' => 'crear stock-movements',
                'label' => 'Nuevo Movimiento',
                'variant' => 'primary',
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />',
            ];
        @endphp

        <x-ui.page-header category="Inventario" title="Movimientos de Stock"
            description="Entradas y salidas de inventario por almacén." :buttons="$buttons" />

        <div class="mb-5">
            <x-ui.search-box placeholder="Buscar por nota, tipo o almacén..." filterName="buscar" />
        </div>

        @php
            $columns = [
                ['key' => 'type.name', 'label' => 'Tipo'],
                ['key' => 'warehouse.name', 'label' => 'Almacén'],
                ['key' => 'date_label', 'label' => 'Fecha'],
                ['key' => 'user.name', 'label' => 'Usuario'],
                ['key' => 'note', 'label' => 'Nota'],
            ];

            $actions = [
                ['type' => 'view', 'route' => 'stock-movements.show'],
                ['type' => 'edit', 'route' => 'stock-movements.edit'],
                [
                    'type' => 'delete',
                    'route' => 'stock-movements.destroy',
                    'confirm' => '¿Eliminar este movimiento? Esto revierte el stock que afectó.',
                ],
            ];
        @endphp

        <x-ui.data-table :rows="$stockMovements" :columns="$columns" :actions="$actions" :paginator="$stockMovements"
            emptyText="No hay movimientos de stock registrados." />

        @can('kardex.importar')
            <x-ui.stock-import-modal :warehouses="$warehouses" />
        @endcan
    </div>

    @push('scripts')
        <script>
            const stockImportModal = document.getElementById('stock-import-modal');
            document.getElementById('stock-import-toggle')?.addEventListener('click', () => stockImportModal?.classList.remove('hidden'));
            document.querySelectorAll('[data-modal-close="stock-import-modal"]').forEach(el => {
                el.addEventListener('click', () => stockImportModal?.classList.add('hidden'));
            });
        </script>
    @endpush
</x-app-layout>
