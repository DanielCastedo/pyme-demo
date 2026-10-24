{{--
    Pagos de la compra a crédito (anticipo y abonos). Recibe: $purchase
--}}
<div class="mb-6">
    <span class="block text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">
        Pagos ({{ $purchase->payments->where('canceled', false)->count() }})
    </span>
    <div class="overflow-x-auto border border-gray-100 dark:border-gray-800 rounded-lg">
        <table class="rtable w-full text-xs">
            <thead class="bg-gray-50 dark:bg-slate-800/60 text-gray-500 dark:text-gray-400 uppercase">
                <tr>
                    <th class="px-3 py-2 text-left">Fecha</th>
                    <th class="px-3 py-2 text-left">Transacción</th>
                    <th class="px-3 py-2 text-left">Método</th>
                    <th class="px-3 py-2 text-left">Caja</th>
                    <th class="px-3 py-2 text-left">Pagado por</th>
                    <th class="px-3 py-2 text-right">Monto</th>
                    <th class="px-3 py-2 text-left">Estado</th>
                    <th class="px-3 py-2"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($purchase->payments as $payment)
                    <tr class="{{ $payment->canceled ? 'opacity-50 line-through' : '' }}">
                        <td class="px-3 py-2 text-gray-800 dark:text-gray-200" data-label="Fecha">{{ $payment->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-3 py-2 font-mono text-gray-800 dark:text-gray-200" data-label="Transacción">{{ $payment->transaction->number }}</td>
                        <td class="px-3 py-2 text-gray-800 dark:text-gray-200" data-label="Método">{{ $payment->transaction->paymentMethod->name ?? '—' }}</td>
                        <td class="px-3 py-2 text-gray-800 dark:text-gray-200" data-label="Caja">{{ $payment->transaction->cashbox->name }}</td>
                        <td class="px-3 py-2 text-gray-800 dark:text-gray-200" data-label="Pagado por">{{ $payment->user->name }}</td>
                        <td class="px-3 py-2 text-right font-medium text-gray-900 dark:text-white" data-label="Monto">{{ number_format((float) $payment->amount, 2) }}</td>
                        <td class="px-3 py-2 text-gray-800 dark:text-gray-200" data-label="Estado">{{ $payment->canceled ? 'Anulado' : 'Vigente' }}</td>
                        <td class="px-3 py-2 text-right">
                            @can('cancelPayment', $purchase)
                                @unless ($payment->canceled)
                                    <form action="{{ route('purchases.payments.destroy', [$purchase, $payment]) }}" method="POST"
                                        onsubmit="return confirm('¿Anular este pago? El monto vuelve al saldo de la compra y se revierte en la caja.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:underline font-semibold">Anular</button>
                                    </form>
                                @endunless
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr class="rtable-empty">
                        <td colspan="8" class="px-3 py-4 text-center text-gray-400">Todavía no se pagó nada de esta compra.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
