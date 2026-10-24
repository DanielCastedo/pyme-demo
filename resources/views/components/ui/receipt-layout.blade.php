{{--
    Recibo (PDF) con el formato de una factura boliviana, pero SIN ser factura: encabezado con datos de la empresa
    y del documento, título, datos del cliente, tabla de detalle (slot), total con monto en letras, leyenda y un QR
    con el número de la transacción (o del documento si no hubo movimiento de caja).

    Se usa en el recibo de venta (contado y crédito), de consignación y de pedido.
--}}
@props([
    'title' => 'RECIBO',
    'documentLabel' => 'RECIBO',  // etiqueta del número en el encabezado (RECIBO N°, PROFORMA N°...)
    'subtitle' => null,          // ej. "(Venta al contado)"
    'number' => '',              // N° del recibo
    'date' => null,              // fecha y hora ya formateada
    'company' => null,
    'branch' => null,            // modelo Branch (su logo va en el encabezado; se muestra su nombre)
    'warehouse' => null,         // nombre del almacén (opcional)
    'customerName' => null,
    'customerDocument' => null,
    'customerCode' => null,
    'partyLabel' => 'Nombre/Razón Social',  // etiqueta del nombre: cliente en ventas, proveedor en compras
    'partyCodeLabel' => 'Cod. Cliente',     // etiqueta del código: "Cod. Proveedor" en compras
    'reference' => null,         // ['label' => 'TRANSACCIÓN', 'value' => 'TRX-0001'] bajo el N° de recibo
    'extra' => [],               // datos adicionales del cliente/operación: [['Vendedor', 'Ana'], ['Forma de pago', 'Contado']]
    'total' => null,             // monto para "Son: ... Bolivianos"
    'qr' => null,                // texto del QR
    'qrCaption' => null,         // texto bajo el QR
    'legend' => 'ESTE DOCUMENTO ES UN RECIBO DE LA OPERACIÓN REALIZADA Y NO DA DERECHO A CRÉDITO FISCAL.',
])

@php
    $qrImage = $qr ? \App\Support\ReceiptQr::dataUri($qr) : null;
    $logoImage = \App\Support\ReceiptLogo::dataUri($branch);
    $pairs = array_chunk($extra, 2);
@endphp

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }} {{ $number }}</title>
    <style>
        @page { margin: 30pt 34pt 46pt 34pt; }
        body { font-family: Helvetica, Arial, sans-serif; font-size: 8.5pt; color: #111827; line-height: 1.3; }
        table { border-collapse: collapse; }

        /* Encabezado: empresa a la izquierda, datos del documento a la derecha */
        .rc-head { width: 100%; }
        .rc-head td { vertical-align: top; }
        .rc-logo { width: 62pt; padding-right: 9pt; vertical-align: top; }
        .rc-logo img { max-width: 58pt; max-height: 58pt; }
        .rc-company { font-size: 12.5pt; font-weight: bold; text-transform: uppercase; }
        .rc-company-line { font-size: 8pt; line-height: 1.35; }
        .rc-doc { width: auto; float: right; }
        .rc-doc td { padding: 1px 0; font-size: 8.5pt; white-space: nowrap; }
        .rc-doc .k { font-weight: bold; padding-right: 8px; text-align: left; }
        .rc-doc .v { text-align: right; }

        .rc-title { text-align: center; margin: 18px 0 14px; }
        .rc-title .t { font-size: 19pt; font-weight: bold; letter-spacing: 1px; }
        .rc-title .s { font-size: 8pt; color: #374151; margin-top: 1px; }

        /* Datos del cliente */
        .rc-client { width: 100%; margin-bottom: 12px; }
        .rc-client td { padding: 1.5px 0; vertical-align: top; font-size: 8.5pt; }
        .rc-client .k { font-weight: bold; white-space: nowrap; padding-right: 6px; }

        /* Detalle (el contenido del slot usa esta clase) */
        table.rc-items { width: 100%; margin-bottom: 8px; }
        .rc-items th { border: 1px solid #111827; padding: 5px 4px; font-size: 7.3pt; text-transform: uppercase; text-align: center; background: #f3f4f6; }
        .rc-items td { border: 1px solid #111827; padding: 4px 5px; font-size: 8pt; vertical-align: top; }
        .rc-c { text-align: center; }
        .rc-r { text-align: right; }
        .rc-b { font-weight: bold; }

        /* Totales */
        table.rc-totals { width: 42%; margin-left: auto; margin-bottom: 6px; }
        .rc-totals td { border: 1px solid #111827; padding: 4px 8px; font-size: 8.5pt; }
        .rc-totals .k { font-weight: bold; text-align: right; }
        .rc-totals .v { text-align: right; width: 38%; }
        .rc-totals .big td { font-size: 9.5pt; font-weight: bold; }

        .rc-words { font-weight: bold; font-size: 8.5pt; margin: 8px 0 14px; }

        .rc-notes { border: 1px solid #9ca3af; padding: 5px 8px; margin: 0 0 10px; font-size: 8pt; }
        .rc-notes .h { font-weight: bold; text-transform: uppercase; font-size: 7.3pt; margin-bottom: 2px; }

        /* Pie: leyenda a la izquierda y QR a la derecha */
        .rc-foot { width: 100%; margin-top: 6px; page-break-inside: avoid; }
        .rc-foot td { vertical-align: bottom; }
        .rc-legend { font-size: 7.5pt; line-height: 1.4; }
        .rc-qr { text-align: center; width: 130px; }
        .rc-qr img { width: 105px; height: 105px; }
        .rc-qr .cap { font-size: 7pt; margin-top: 2px; font-weight: bold; }

        /* Firmas (opcionales) */
        .signature-table { width: 100%; margin-top: 8px; }
        .signature-box { width: 42%; text-align: center; font-size: 8pt; color: #374151; vertical-align: bottom; }
        .signature-line { border-top: 1px dashed #6b7280; padding-top: 5px; }
        .signature-spacer { width: 16%; }
    </style>
</head>
<body>

    {{-- Encabezado --}}
    <table class="rc-head">
        <tr>
            <td style="width: 58%;">
                <table>
                    <tr>
                        @if ($logoImage)
                            <td class="rc-logo"><img src="{{ $logoImage }}" alt="Logo"></td>
                        @endif
                        <td style="vertical-align: top;">
                            <div class="rc-company">{{ $branch?->name ?? $company?->name ?? config('app.name') }}</div>
                            @if ($warehouse)
                                <div class="rc-company-line">Almacén: {{ $warehouse }}</div>
                            @endif
                            @if ($company?->address)
                                <div class="rc-company-line">{{ $company->address }}</div>
                            @endif
                            @if ($company?->phone)
                                <div class="rc-company-line">Teléfono: {{ $company->phone }}</div>
                            @endif
                            @if ($company?->city)
                                <div class="rc-company-line">{{ mb_strtoupper($company->city) }}</div>
                            @endif
                        </td>
                    </tr>
                </table>
            </td>
            <td style="width: 42%;">
                <table class="rc-doc">
                    @if ($company?->tax_id)
                        <tr><td class="k">NIT</td><td class="v">{{ $company->tax_id }}</td></tr>
                    @endif
                    <tr><td class="k">{{ $documentLabel }} N°</td><td class="v">{{ $number }}</td></tr>
                    @if ($reference)
                        <tr><td class="k">{{ $reference['label'] }}</td><td class="v">{{ $reference['value'] }}</td></tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    {{-- Título --}}
    <div class="rc-title">
        <div class="t">{{ $title }}</div>
        @if ($subtitle)
            <div class="s">{{ $subtitle }}</div>
        @endif
    </div>

    {{-- Fecha y cliente --}}
    <table class="rc-client">
        <tr>
            <td style="width: 55%;"><span class="k">Fecha:</span> {{ $date }}</td>
            <td style="width: 45%;"><span class="k">NIT/CI/CEX:</span> {{ $customerDocument ?: 'S/N' }}</td>
        </tr>
        <tr>
            <td><span class="k">{{ $partyLabel }}:</span> {{ $customerName ?: 'S/N' }}</td>
            <td><span class="k">{{ $partyCodeLabel }}:</span> {{ $customerCode ?: 'N/A' }}</td>
        </tr>
        @foreach ($pairs as $pair)
            <tr>
                <td><span class="k">{{ $pair[0][0] }}:</span> {{ $pair[0][1] }}</td>
                <td>@isset($pair[1])<span class="k">{{ $pair[1][0] }}:</span> {{ $pair[1][1] }}@endisset</td>
            </tr>
        @endforeach
    </table>

    {{-- Detalle y totales --}}
    {{ $slot }}

    @if ($total !== null)
        <div class="rc-words">Son: {{ \App\Support\AmountInWords::bolivianos($total) }}</div>
    @endif

    {{-- Leyenda y QR --}}
    <table class="rc-foot">
        <tr>
            <td class="rc-legend">
                {{ $legend }}
                @isset($notice)
                    <br>{{ $notice }}
                @endisset
            </td>
            @if ($qrImage)
                <td class="rc-qr">
                    <img src="{{ $qrImage }}" alt="QR">
                    @if ($qrCaption)
                        <div class="cap">{{ $qrCaption }}</div>
                    @endif
                </td>
            @endif
        </tr>
    </table>

    {{ $footer ?? '' }}

</body>
</html>
