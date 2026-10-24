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
                    'can' => 'crear cashboxes',
                    'label' => 'Nueva Caja',
                    'variant' => 'primary',
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />',
                ],
            ];
        @endphp

        <x-ui.page-header category="Finanzas" title="Cajas"
            description="Cajas por sucursal. El efectivo y el saldo solo cambian con transacciones." :buttons="$buttons" />

        <div class="mb-5">
            <x-ui.search-box placeholder="Buscar por caja o sucursal..." filterName="buscar" />
        </div>

        @php
            $columns = [
                ['key' => 'name', 'label' => 'Caja'],
                ['key' => 'branch.name', 'label' => 'Sucursal'],
                ['key' => 'cash_label', 'label' => 'Efectivo'],
                ['key' => 'balance_label', 'label' => 'Saldo Total'],
            ];

            $actions = [
                ['type' => 'view', 'route' => 'cashboxes.show'],
                ['type' => 'edit', 'route' => 'cashboxes.edit'],
                ['type' => 'delete', 'route' => 'cashboxes.destroy', 'confirm' => '¿Eliminar esta caja? Solo se puede si no tiene transacciones ni dinero.'],
            ];
        @endphp

        <x-ui.data-table :rows="$cashboxes" :columns="$columns" :actions="$actions" :paginator="$cashboxes"
            emptyText="No hay cajas registradas." />

        <x-ui.create-modal id="crud-modal" title="Registrar Nueva Caja" :action="route('cashboxes.store')"
            submitText="Guardar Caja" size="sm" :columns="1" :fields="[
                [
                    'type' => 'text',
                    'name' => 'name',
                    'label' => 'Nombre',
                    'placeholder' => 'Ej. Caja Principal',
                    'required' => true,
                ],
                [
                    'type' => 'select',
                    'name' => 'branche_id',
                    'label' => 'Sucursal',
                    'options' => $branches->pluck('name', 'id')->toArray(),
                    'required' => true,
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
        </script>
    @endpush
</x-app-layout>
