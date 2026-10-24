@if ($order->receipt_photo_url || $order->payments->whereNotNull('receipt_photo')->isNotEmpty())
    @php
        $receiptUrl = $order->receipt_photo_url ?? $order->payments->whereNotNull('receipt_photo')->first()?->receipt_photo_url;
    @endphp
    @if ($receiptUrl)
        <div class="mb-6 p-4 rounded-xl border border-sky-200 dark:border-sky-800/60 bg-sky-50/50 dark:bg-sky-950/20">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold text-sky-900 dark:text-sky-300 uppercase tracking-wider flex items-center gap-2">
                    <svg class="w-4 h-4 text-sky-600 dark:text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Comprobante de Pago (QR / Transferencia)
                </span>
                <a href="{{ $receiptUrl }}" target="_blank" download="comprobante-venta-{{ $order->id }}"
                    class="text-xs font-semibold text-sky-600 dark:text-sky-400 hover:underline flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Descargar
                </a>
            </div>

            <div class="flex flex-wrap items-start gap-4">
                <a href="{{ $receiptUrl }}" target="_blank" class="block group shrink-0 relative overflow-hidden rounded-lg border border-sky-300 dark:border-sky-700 shadow-sm">
                    <img src="{{ $receiptUrl }}" alt="Comprobante de Venta #{{ $order->id }}"
                        class="w-36 h-36 object-cover rounded-lg group-hover:scale-105 transition-transform duration-200">
                    <span class="absolute inset-0 bg-slate-900/30 opacity-0 group-hover:opacity-100 flex items-center justify-center text-white text-[11px] font-semibold transition-opacity">
                        Ver completo
                    </span>
                </a>
                <div class="text-xs text-gray-600 dark:text-gray-300 space-y-1 min-w-[160px]">
                    <p class="font-medium text-gray-900 dark:text-white">Foto o imagen capturada durante el cobro con QR.</p>
                    <p class="text-gray-500">Haz clic en la imagen para verla en tamaño completo o abrirla en una pestaña nueva.</p>
                </div>
            </div>
        </div>
    @endif
@endif
