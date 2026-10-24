@php
    $items = [
        ['label' => 'Nombre', 'value' => $branch->name],
        ['label' => 'Dirección', 'value' => $branch->address ?? '—'],
        ['label' => 'Teléfono', 'value' => $branch->phone ?? '—'],
        ['label' => 'Estado', 'value' => $branch->is_active ? 'Activa' : 'Inactiva'],
        ['label' => 'Usuarios', 'value' => $branch->users_count],
        ['label' => 'Cajas', 'value' => $branch->cashboxes_count],
        ['label' => 'Almacenes', 'value' => $branch->warehouses_count],
    ];
@endphp

<x-ui.show-page title="Detalle de Sucursal" category="Inventario" :backRoute="route('branches.index')"
    :editRoute="auth()->user()->can('update', $branch) ? route('branches.edit', $branch) : ''" :items="$items" :columns="2">
    <div class="flex flex-wrap gap-6">
        @if ($branch->logo)
            <div>
                <span class="block text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">Logo</span>
                <img src="{{ $branch->logo_url }}" alt="Logo" class="w-32 h-32 object-contain rounded-lg border border-gray-200 dark:border-gray-700 bg-white p-2">
            </div>
        @endif

        @if ($branch->payment_qr_image)
            <div>
                <span class="block text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">QR de Cobro</span>
                <img src="{{ $branch->payment_qr_image_url }}" alt="QR de cobro" class="w-32 h-32 object-contain rounded-lg border border-gray-200 dark:border-gray-700 bg-white p-2">
            </div>
        @endif
    </div>
</x-ui.show-page>
