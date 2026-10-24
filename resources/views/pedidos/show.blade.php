@php
    $items = [
        ['label' => 'Pedido N°', 'value' => '#'.$pedido->id],
        ['label' => 'Estado', 'value' => $pedido->pedido_status_label],
        ['label' => 'Cliente', 'value' => $pedido->customer_label],
        ['label' => 'Registrado', 'value' => $pedido->date->format('d/m/Y')],
        ['label' => 'Fecha de entrega', 'value' => $pedido->delivery_label],
        ['label' => 'Sucursal', 'value' => $pedido->branch->name],
        ['label' => 'Almacén', 'value' => $pedido->warehouse->name],
        ['label' => 'Registrado por', 'value' => $pedido->user->name],
        ['label' => 'Total', 'value' => $pedido->amount_label],
    ];

    $discountTotal = (float) \App\Support\Bc::sum($pedido->items->pluck('discount'));

    if (\App\Support\Bc::gt($discountTotal, 0)) {
        $items[] = ['label' => 'Descuentos acordados', 'value' => number_format($discountTotal, 2)];
    }

    if ($pedido->memo) {
        $items[] = ['label' => 'Nota', 'value' => $pedido->memo, 'fullWidth' => true];
    }

    $canEdit = auth()->user()->can('updatePedido', $pedido);
    $canCredit = auth()->user()->can('ventas.credito');
    $shortage = $pedido->isPending() && $pedido->items->contains(fn ($i) => ($availability[$i->id] ?? 0) < (float) $i->quantity);
@endphp

<x-ui.show-page title="Pedido #{{ $pedido->id }}" category="Ventas" :backRoute="route('pedidos.index')"
    :editRoute="$canEdit ? route('pedidos.edit', $pedido) : ''" :pdfRoute="auth()->user()->can('ventas.exportar') ? route('pedidos.pdf', $pedido) : ''" :items="$items" :columns="3">

    @if (session('success'))
        <x-ui.alert type="success" :message="session('success')" />
    @endif

    @if (session('error'))
        <x-ui.alert type="error" :message="session('error')" />
    @endif

    <div class="mb-6">
        <span class="block text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">
            Productos ({{ $pedido->items->count() }})
        </span>
        <div class="overflow-x-auto border border-gray-100 dark:border-gray-800 rounded-lg">
            <table class="w-full text-xs">
                <thead class="bg-gray-50 dark:bg-slate-800/60 text-gray-500 dark:text-gray-400 uppercase">
                    <tr>
                        <th class="px-3 py-2 text-left">Producto</th>
                        <th class="px-3 py-2 text-left">Unidad</th>
                        <th class="px-3 py-2 text-right">Cantidad</th>
                        <th class="px-3 py-2 text-right">Precio</th>
                        <th class="px-3 py-2 text-right">Descuento</th>
                        <th class="px-3 py-2 text-right">Subtotal</th>
                        @if ($pedido->isPending())
                            <th class="px-3 py-2 text-right">Hay hoy</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach ($pedido->items as $item)
                        @php $short = ($availability[$item->id] ?? 0) < (float) $item->quantity; @endphp
                        <tr>
                            <td class="px-3 py-2 text-gray-800 dark:text-gray-200">{{ $item->product->name }}</td>
                            <td class="px-3 py-2 text-gray-800 dark:text-gray-200">{{ $item->productUnit->name }}</td>
                            <td class="px-3 py-2 text-right text-gray-800 dark:text-gray-200">{{ (float) $item->quantity }}</td>
                            <td class="px-3 py-2 text-right text-gray-800 dark:text-gray-200">{{ number_format((float) $item->unit_price, 2) }}</td>
                            <td class="px-3 py-2 text-right text-gray-800 dark:text-gray-200">{{ (float) $item->discount > 0 ? '− '.number_format((float) $item->discount, 2) : '—' }}</td>
                            <td class="px-3 py-2 text-right font-medium text-gray-900 dark:text-white">{{ number_format((float) $item->subtotal, 2) }}</td>
                            @if ($pedido->isPending())
                                <td class="px-3 py-2 text-right font-medium {{ $short ? 'text-red-600' : 'text-emerald-600' }}">
                                    {{ $availability[$item->id] ?? 0 }}{{ $short ? ' (falta)' : '' }}
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-50 dark:bg-slate-800/60">
                    <tr>
                        <td colspan="5" class="px-3 py-2 text-right font-bold uppercase text-gray-600 dark:text-gray-300">Total</td>
                        <td class="px-3 py-2 text-right font-bold text-gray-900 dark:text-white">{{ $pedido->amount_label }}</td>
                        @if ($pedido->isPending())
                            <td></td>
                        @endif
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    @if ($pedido->isCompleted())
        <p class="mb-4 text-sm text-gray-600 dark:text-gray-300">
            Este pedido ya se vendió.
            <a href="{{ route('sales.show', $pedido) }}" class="font-semibold text-sky-600 hover:underline">Ver la venta #{{ $pedido->id }}</a>
        </p>
    @endif

    @can('confirmPedido', $pedido)
        <div class="mb-6 p-4 rounded-xl border border-emerald-200 dark:border-emerald-800/50 bg-emerald-50/50 dark:bg-emerald-900/10">
            <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-1">Confirmar y vender</h3>
            <p class="text-xs text-gray-600 dark:text-gray-400 mb-4">
                Al confirmar se descuenta el stock de <strong>{{ $pedido->warehouse->name }}</strong> (FIFO) y se cobra
                <strong>{{ $pedido->amount_label }}</strong> en la caja elegida, con los precios y descuentos de este pedido.
            </p>

            @if ($shortage)
                <p class="mb-4 text-xs font-medium text-red-600 dark:text-red-400">
                    Hoy no alcanza el stock de algunos productos (columna "Hay hoy"). Espera la mercadería o edita el pedido; la confirmación fallará mientras falte.
                </p>
            @endif

            <form action="{{ route('pedidos.confirm', $pedido) }}" method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                @csrf

                @if ($canCredit)
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Forma de pago</label>
                        <select name="payment_type" id="confirm-type"
                            class="w-full h-10 px-3 text-sm rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white">
                            <option value="cash">Contado</option>
                            <option value="credit" @disabled(! $pedido->customer_id)>A crédito{{ $pedido->customer_id ? '' : ' (requiere cliente)' }}</option>
                        </select>
                    </div>
                    <div id="confirm-credit" class="hidden md:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Vence el</label>
                            <input type="date" name="due_date" value="{{ now()->addDays(30)->toDateString() }}" disabled
                                class="w-full h-10 px-3 text-sm rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Anticipo (opcional)</label>
                            <input type="number" step="0.01" min="0" name="initial_payment" placeholder="0.00" disabled
                                class="w-full h-10 px-3 text-sm rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white">
                        </div>
                    </div>
                @endif

                <x-ui.form-field type="select" name="payment_id" label="Método de pago (del cobro o anticipo)" required
                    :options="$paymentMethods->pluck('name', 'id')->toArray()" />
                <x-ui.form-field type="select" name="cashbox_id" label="Caja" required
                    :options="$cashboxes->pluck('name', 'id')->toArray()" />
                <x-ui.button type="submit" variant="primary" :fullWidth="true">Confirmar venta</x-ui.button>
            </form>

            @if ($canCredit)
                <script>
                    (function() {
                        const type = document.getElementById('confirm-type');
                        const box = document.getElementById('confirm-credit');
                        type.addEventListener('change', () => {
                            const credit = type.value === 'credit';
                            box.classList.toggle('hidden', !credit);
                            box.querySelectorAll('input').forEach((i) => { i.disabled = !credit; });
                        });
                    })();
                </script>
            @endif
        </div>
    @endcan

    @can('deletePedido', $pedido)
        <form action="{{ route('pedidos.destroy', $pedido) }}" method="POST"
            onsubmit="return confirm('¿Eliminar este pedido? No se puede deshacer.')">
            @csrf
            @method('DELETE')
            <x-ui.button type="submit" variant="outline">Eliminar pedido</x-ui.button>
        </form>
    @endcan
</x-ui.show-page>
