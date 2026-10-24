@php
    $items = [
        ['label' => 'Gasto N°', 'value' => '#'.$expense->id],
        ['label' => 'Estado', 'value' => $expense->status_label],
        ['label' => 'Concepto', 'value' => $expense->concept, 'fullWidth' => true],
        ['label' => 'Monto', 'value' => $expense->amount_label],
        ['label' => 'Fecha del gasto', 'value' => $expense->date->format('d/m/Y')],
        ['label' => 'Categoría', 'value' => $expense->category_label],
        ['label' => 'Proveedor', 'value' => $expense->supplier?->name ?? '—'],
        ['label' => 'N° de recibo / factura', 'value' => $expense->receipt_number ?: '—'],
        ['label' => 'Sucursal', 'value' => $expense->branch->name],
        ['label' => 'Registrado por', 'value' => $expense->user->name],
    ];

    if ($expense->transaction) {
        $items[] = ['label' => 'Pago', 'value' => ($expense->transaction->paymentMethod->name ?? '—').' · '.$expense->transaction->cashbox->name];
        $items[] = ['label' => 'Transacción', 'value' => $expense->transaction->number.($expense->transaction->canceled ? ' (anulada)' : ''), 'mono' => true];
    }

    if ($expense->description) {
        $items[] = ['label' => 'Detalle', 'value' => $expense->description, 'fullWidth' => true];
    }
@endphp

<x-ui.show-page title="Gasto #{{ $expense->id }}" category="Gastos" :backRoute="route('expenses.index')"
    :editRoute="auth()->user()->can('update', $expense) ? route('expenses.edit', $expense) : null" :pdfRoute="auth()->user()->can('gastos.exportar') ? route('expenses.pdf', $expense) : ''" :items="$items" :columns="3">

    @include('sales.partials.alerts')

    <div class="flex items-center gap-3 mt-4">
        @can('delete', $expense)
            <form action="{{ route('expenses.destroy', $expense) }}" method="POST"
                onsubmit="return confirm('{{ $expense->isCanceled() ? '¿Eliminar definitivamente este gasto anulado?' : '¿Anular este gasto? El dinero vuelve a la caja.' }}')">
                @csrf
                @method('DELETE')
                <x-ui.button type="submit" variant="outline">{{ $expense->isCanceled() ? 'Eliminar registro' : 'Anular gasto' }}</x-ui.button>
            </form>
        @endcan
    </div>
</x-ui.show-page>
