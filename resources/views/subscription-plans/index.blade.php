<x-app-layout>
    <div class="max-w-10xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        @if (session('success'))
            <x-ui.alert type="success" :message="session('success')" />
        @endif

        @php
            $buttons = [
                [
                    'id' => 'modal-toggle-button',
                    'can' => 'crear subscription-plans',
                    'label' => 'Nuevo Plan',
                    'variant' => 'primary',
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />',
                    'iconPosition' => 'left',
                ],
            ];
        @endphp

        <x-ui.page-header category="Suscripciones" title="Planes de Suscripción"
            description="Gestiona los planes disponibles para las empresas." :buttons="$buttons" />

        <div class="mb-5">
            <x-ui.search-box placeholder="Buscar plan por nombre..." filterName="buscar" />
        </div>

        @php
            $columns = [
                ['key' => 'id', 'label' => 'ID'],
                ['key' => 'name', 'label' => 'Nombre'],
                ['key' => 'price', 'label' => 'Precio'],
                ['key' => 'billing_cycle', 'label' => 'Ciclo'],
                ['key' => 'max_users', 'label' => 'Máx. Usuarios'],
            ];

            $actions = [
                ['type' => 'view', 'route' => 'subscription-plans.show'],
                ['type' => 'edit', 'route' => 'subscription-plans.edit'],
                ['type' => 'delete', 'route' => 'subscription-plans.destroy', 'confirm' => '¿Eliminar este plan?'],
            ];
        @endphp

        <x-ui.data-table :rows="$plans" :columns="$columns" :actions="$actions" :paginator="$plans" />

        <x-ui.create-modal id="crud-modal" title="Registrar Nuevo Plan" :action="route('subscription-plans.store')"
            submitText="Guardar Plan" :columns="2" :fields="[
                ['type' => 'text', 'name' => 'name', 'label' => 'Nombre del Plan', 'placeholder' => 'Ej. Pro', 'required' => true],
                [
                    'type' => 'select',
                    'name' => 'billing_cycle',
                    'label' => 'Ciclo de Facturación',
                    'required' => true,
                    'options' => ['monthly' => 'Mensual', 'quarterly' => 'Trimestral', 'yearly' => 'Anual'],
                ],
                ['type' => 'number', 'name' => 'price', 'label' => 'Precio', 'placeholder' => 'Ej. 249.00', 'required' => true],
                ['type' => 'text', 'name' => 'currency', 'label' => 'Moneda', 'placeholder' => 'Ej. BOB', 'value' => 'BOB'],
                ['type' => 'number', 'name' => 'max_users', 'label' => 'Máx. Usuarios', 'placeholder' => 'Vacío = ilimitado', 'help' => 'Deja vacío para ilimitado'],
                ['type' => 'checkbox', 'name' => 'active', 'label' => 'Plan activo', 'value' => true],
                [
                    'type' => 'textarea', 'name' => 'description', 'label' => 'Descripción',
                    'placeholder' => 'Breve descripción del plan', 'fullWidth' => true, 'rows' => 2,
                ],
                [
                    'type' => 'textarea', 'name' => 'features', 'label' => 'Características',
                    'placeholder' => 'Ej. Hasta 5 usuarios
Soporte prioritario
Reportes avanzados',
                    'help' => 'Una característica por línea.', 'fullWidth' => true, 'rows' => 4,
                ],
                ['type' => 'file', 'name' => 'qr_image', 'label' => 'Imagen QR de Pago', 'fullWidth' => true],
            ]" />
    </div>

    @push('scripts')
        <script>
            const modal = document.getElementById('crud-modal');
            document.getElementById('modal-toggle-button')?.addEventListener('click', () => modal.classList.remove('hidden'));
            document.querySelectorAll('[data-modal-close="crud-modal"]').forEach(el => {
                el.addEventListener('click', () => modal.classList.add('hidden'));
            });
        </script>
    @endpush
</x-app-layout>