{{--
    Detalle de una venta A CRÉDITO: el cliente debe un saldo que se cobra con abonos hasta la fecha de vencimiento.
    Recibe: $order, $paymentMethods, $cashboxes
--}}
@php
    $percentPaid = \App\Support\Bc::gt($order->amount, 0) ? (float) \App\Support\Bc::min(100, \App\Support\Bc::round(\App\Support\Bc::mul(\App\Support\Bc::div($order->paid_amount, $order->amount), 100))) : 0;

    // Color del estado del cobro: verde pagada, rojo vencida/anulada, ámbar con saldo vigente.
    $tone = match (true) {
        $order->isCanceled() => 'gray',
        $order->is_paid => 'emerald',
        $order->is_payment_overdue => 'red',
        default => 'amber',
    };
    $toneClasses = [
        'gray' => 'border-gray-200 dark:border-gray-700 text-gray-500',
        'emerald' => 'border-emerald-200 dark:border-emerald-800 text-emerald-600',
        'red' => 'border-red-300 dark:border-red-800 text-red-600',
        'amber' => 'border-amber-200 dark:border-amber-800 text-amber-600',
    ];
    $barClasses = ['gray' => 'bg-gray-400', 'emerald' => 'bg-emerald-500', 'red' => 'bg-red-500', 'amber' => 'bg-amber-500'];

    $items = [
        ['label' => 'Venta N°', 'value' => '#'.$order->id],
        ['label' => 'Estado de la venta', 'value' => $order->status_label],
        ['label' => 'Cliente', 'value' => $order->customer_label],
        ['label' => 'Fecha', 'value' => $order->date->format('d/m/Y')],
        ['label' => 'Sucursal', 'value' => $order->branch->name],
        ['label' => 'Almacén', 'value' => $order->warehouse->name],
        ['label' => 'Vendedor', 'value' => $order->user->name],
        ['label' => 'Forma de pago', 'value' => 'A crédito'],
    ];

    if ($order->customer) {
        $items[] = ['label' => 'Límite de crédito del cliente', 'value' => $order->customer->credit_limit_label];
        $items[] = ['label' => 'Deuda total del cliente', 'value' => number_format($order->customer->receivableBalance(), 2)];
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

<x-ui.show-page title="Venta a crédito #{{ $order->id }}" category="Ventas" :backRoute="route('sales.index')" :pdfRoute="auth()->user()->can('ventas.exportar') ? route('sales.pdf', $order) : ''" :items="$items" :columns="3">

    @include('sales.partials.alerts')

    {{-- Resumen del crédito --}}
    <div class="mb-6">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-3">
            <div class="p-3 rounded-xl bg-white dark:bg-slate-900 border border-gray-200 dark:border-gray-800">
                <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider">Total</span>
                <span class="text-lg font-extrabold text-gray-900 dark:text-white">{{ $order->amount_label }}</span>
            </div>
            <div class="p-3 rounded-xl bg-white dark:bg-slate-900 border border-gray-200 dark:border-gray-800">
                <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider">Cobrado</span>
                <span class="text-lg font-extrabold text-gray-900 dark:text-white">{{ $order->paid_label }}</span>
            </div>
            <div class="p-3 rounded-xl bg-white dark:bg-slate-900 border {{ $toneClasses[$tone] }}">
                <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider">Saldo pendiente</span>
                <span class="text-lg font-extrabold {{ $toneClasses[$tone] }}">{{ $order->balance_label }}</span>
            </div>
            <div class="p-3 rounded-xl bg-white dark:bg-slate-900 border {{ $toneClasses[$tone] }}">
                <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider">Vence</span>
                <span class="text-lg font-extrabold {{ $toneClasses[$tone] }}">{{ $order->due_label }}</span>
                <span class="block text-[11px] font-semibold {{ $toneClasses[$tone] }}">{{ $order->payment_status_label }}</span>
            </div>
        </div>

        <div class="h-2 rounded-full bg-gray-100 dark:bg-slate-800 overflow-hidden" title="{{ $percentPaid }}% cobrado">
            <div class="h-full {{ $barClasses[$tone] }}" style="width: {{ $percentPaid }}%"></div>
        </div>
        <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">{{ $percentPaid }}% cobrado</p>
    </div>

    @include('sales.partials.items-table')

    @include('sales.partials.receipt-card')

    @include('sales.partials.payments-table', ['title' => 'Abonos y cobros', 'emptyText' => 'Todavía no se cobró nada de esta venta.', 'allowCancel' => true])

    {{-- Registrar un abono --}}
    @can('collect', $order)
        <div class="mb-6 p-4 rounded-xl border border-amber-200 dark:border-amber-800/50 bg-amber-50/50 dark:bg-amber-900/10">
            <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-1">Registrar cobro</h3>
            <p class="text-xs text-gray-600 dark:text-gray-400 mb-4">
                Saldo pendiente: <strong>{{ $order->balance_label }}</strong> · Vence: <strong>{{ $order->due_label }}</strong>
            </p>

            <form action="{{ route('sales.payments.store', $order) }}" method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                @csrf
                <x-ui.form-field type="number" name="amount" label="Monto a cobrar" required :value="old('amount', $order->balance)" />
                <x-ui.form-field type="select" name="payment_id" label="Método de pago" required
                    :options="$paymentMethods->pluck('name', 'id')->toArray()" />
                <x-ui.form-field type="select" name="cashbox_id" label="Caja" required
                    :options="$cashboxes->pluck('name', 'id')->toArray()" />
                <x-ui.button type="submit" variant="primary" :fullWidth="true">Registrar cobro</x-ui.button>
            </form>
        </div>
    @endcan

    @include('sales.partials.actions', ['cancelMessage' => '¿Anular esta venta a crédito? El stock vuelve a sus lotes y todos los cobros registrados se revierten en la caja.'])
</x-ui.show-page>
