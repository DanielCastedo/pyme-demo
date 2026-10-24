<x-app-layout>
    <div class="max-w-10xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        @if (session('success'))
            <x-ui.alert type="success" :message="session('success')" />
        @endif

        @if (session('error'))
            <x-ui.alert type="error" :message="session('error')" />
        @endif

        @php
            $isSuperAdmin = auth()->user()?->hasRole('superadmin') ?? false;
            $buttons = [];

            if ($isSuperAdmin) {
                $buttons[] = [
                    'id' => 'modal-toggle-button',
                    'label' => 'Nueva Empresa',
                    'variant' => 'primary',
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />',
                    'iconPosition' => 'left',
                ];
            }
        @endphp

        <x-ui.page-header category="Configuración" title="Gestión de Empresas"
            description="Administra los datos de la empresa." :buttons="$buttons" />

        <div class="mb-5">
            <x-ui.search-box placeholder="Buscar empresa por nombre..." filterName="buscar" />
        </div>

        @php
            $columns = [
                ['key' => 'id', 'label' => 'ID'],
                ['key' => 'name', 'label' => 'Nombre Comercial'],
                ['key' => 'tax_id', 'label' => 'NIT'],
                ['key' => 'city', 'label' => 'Ciudad'],
            ];

            $actions = [
                ['type' => 'view', 'route' => 'companies.show'],
                ['type' => 'edit', 'route' => 'companies.edit'],
            ];

            if ($isSuperAdmin) {
                $actions[] = ['type' => 'delete', 'route' => 'companies.destroy', 'confirm' => '¿Eliminar esta empresa?'];
            }
        @endphp

        <x-ui.data-table :rows="$companies" :columns="$columns" :actions="$actions" :paginator="$companies" />

        @if ($isSuperAdmin)
            <x-ui.create-modal id="crud-modal" title="Registrar Nueva Empresa" :action="route('companies.store')"
              submitText="Guardar Empresa" :columns="3" :fields="[
                    [
                        'type' => 'text',
                        'name' => 'name',
                        'label' => 'Nombre Comercial',
                        'placeholder' => 'Ej. Ferretería El Constructor',
                        'required' => true,
                    ],
                    [
                        'type' => 'text',
                        'name' => 'legal_name',
                        'label' => 'Razón Social',
                        'placeholder' => 'Ej. El Constructor S.R.L.',
                    ],
                    [
                        'type' => 'text',
                        'name' => 'tax_id',
                        'label' => 'NIT',
                        'placeholder' => 'Ej. 1023456028',
                    ],
                    [
                        'type' => 'text',
                        'name' => 'phone',
                        'label' => 'Teléfono',
                        'placeholder' => 'Ej. 70012345',
                    ],
                    [
                        'type' => 'email',
                        'name' => 'email',
                        'label' => 'Correo',
                        'placeholder' => 'contacto@ferreteria.com',
                    ],
                    [
                        'type' => 'text',
                        'name' => 'address',
                        'label' => 'Dirección',
                        'placeholder' => 'Ej. Av. Cristo Redentor #123',
                        'fullWidth' => true,
                    ],
                    [
                        'type' => 'text',
                        'name' => 'city',
                        'label' => 'Ciudad',
                        'placeholder' => 'Ej. Santa Cruz de la Sierra',
                    ],
                    [
                        'type' => 'text',
                        'name' => 'country',
                        'label' => 'País',
                        'placeholder' => 'Ej. Bolivia',
                    ],
                ]" />
        @endif
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