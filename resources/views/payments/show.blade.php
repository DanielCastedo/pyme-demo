@php
    $items = [
        ['label' => 'Nombre', 'value' => $paymentMethod->name],
        ['label' => 'N° de Cuenta / Celular', 'value' => $paymentMethod->number ?? '—', 'mono' => true],
        ['label' => 'Tipo', 'value' => $paymentMethod->cash_label],
        ['label' => 'Nota / Instrucciones', 'value' => $paymentMethod->note ?? '—', 'fullWidth' => true],
    ];
@endphp

<x-ui.show-page title="Detalle de Método de Pago" category="Ventas" :backRoute="route('payments.index')"
    :editRoute="auth()->user()->can('update', $paymentMethod) ? route('payments.edit', $paymentMethod) : ''" :items="$items" :columns="2">
    @if ($paymentMethod->photo)
        <div>
            <span class="block text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">Imagen QR / Comprobante</span>
            <img src="{{ $paymentMethod->photo_url }}" alt="{{ $paymentMethod->name }}"
                class="w-40 h-40 object-contain rounded-lg border border-gray-200 dark:border-gray-700 bg-white p-2">
        </div>
    @endif
</x-ui.show-page>
