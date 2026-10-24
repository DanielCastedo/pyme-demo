@php
    $items = [
        ['label' => 'Nombre Comercial', 'value' => $company->name],
        ['label' => 'Razón Social', 'value' => $company->legal_name ?? '—'],
        ['label' => 'NIT', 'value' => $company->tax_id ?? '—', 'mono' => true],
        ['label' => 'Teléfono', 'value' => $company->phone ?? '—'],
        ['label' => 'Correo', 'value' => $company->email ?? '—'],
        ['label' => 'Dirección', 'value' => $company->address ?? '—', 'fullWidth' => true],
        ['label' => 'Ciudad', 'value' => $company->city ?? '—'],
        ['label' => 'País', 'value' => $company->country ?? '—'],
        ['label' => 'Moneda', 'value' => $company->currency],
    ];
@endphp

<x-ui.show-page
    title="Detalle de Empresa"
    category="Configuración"
    :backRoute="route('companies.index')"
    :editRoute="auth()->user()->can('update', $company) ? route('companies.edit', $company) : ''"
    :items="$items"
    :columns="2"
/>