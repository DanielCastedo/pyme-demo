{{-- Detalle de una venta AL CONTADO: se cobró completa en el momento. Recibe: $order --}}
@php
    $payment = $order->payments->where('canceled', false)->first() ?? $order->payments->first();

    $items = [
        ['label' => 'Venta N°', 'value' => '#'.$order->id],
        ['label' => 'Estado', 'value' => $order->status_label],
        ['label' => 'Cliente', 'value' => $order->customer_label],
        ['label' => 'Fecha', 'value' => $order->date->format('d/m/Y')],
        ['label' => 'Sucursal', 'value' => $order->branch->name],
        ['label' => 'Almacén', 'value' => $order->warehouse->name],
        ['label' => 'Vendedor', 'value' => $order->user->name],
        ['label' => 'Total', 'value' => $order->amount_label],
        ['label' => 'Forma de pago', 'value' => 'Contado'],
    ];

    if ($payment) {
        $items[] = ['label' => 'Método de pago', 'value' => $payment->transaction->paymentMethod->name ?? '—'];
        $items[] = ['label' => 'Caja', 'value' => $payment->transaction->cashbox->name];
        $items[] = ['label' => 'Transacción', 'value' => $payment->transaction->number.($payment->canceled ? ' (anulada)' : ''), 'mono' => true];
    }

    if ($order->discount_total > 0) {
        $items[] = ['label' => 'Descuentos aplicados', 'value' => number_format($order->discount_total, 2)];
    }

    if ($order->profit !== null && ! $order->isCanceled()) {
        $items[] = ['label' => 'Costo de la mercadería (FIFO)', 'value' => number_format($order->cost_of_goods, 2)];
        $items[] = ['label' => 'Ganancia', 'value' => number_format($order->profit, 2)];
    }

    if ($order->consignmentSettlement) {
        $items[] = ['label' => 'Origen', 'value' => 'Consignación #'.$order->consignmentSettlement->consignment_id.' (lo que el cliente vendió)'];
    }

    if ($order->memo) {
        $items[] = ['label' => 'Nota', 'value' => $order->memo, 'fullWidth' => true];
    }
@endphp

<x-ui.show-page title="Venta #{{ $order->id }}" category="Ventas" :backRoute="route('sales.index')" :pdfRoute="auth()->user()->can('ventas.exportar') ? route('sales.pdf', $order) : ''" :items="$items" :columns="3">

    @include('sales.partials.alerts')

    @include('sales.partials.items-table')

    @include('sales.partials.receipt-card')

    @include('sales.partials.payments-table', ['title' => 'Cobro', 'emptyText' => 'Sin cobro registrado.', 'allowCancel' => false])

    @include('sales.partials.actions', ['cancelMessage' => '¿Anular esta venta? El stock vuelve a sus lotes y el cobro se revierte en la caja.'])
</x-ui.show-page>
