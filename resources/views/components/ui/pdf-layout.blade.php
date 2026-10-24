@props([
    'title' => 'DOCUMENTO COMERCIAL',
    'number' => '',
    'date' => '',
    'company' => null,
    'footerNote' => 'Documento referencial emitido por el sistema comercial.',
])

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }} {{ $number }}</title>
    <style>
        @page {
            margin: 36pt 38pt 135pt 38pt;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 10pt;
            color: #1f2937;
            line-height: 1.35;
        }
        .header-table {
            width: 100%;
            margin-bottom: 16px;
            border-bottom: 2px solid #111827;
            padding-bottom: 10px;
        }
        .company-name {
            font-size: 16pt;
            font-weight: bold;
            color: #111827;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .company-info {
            font-size: 8.5pt;
            color: #4b5563;
            margin-top: 3px;
            line-height: 1.35;
        }
        .doc-header-col {
            text-align: right;
            vertical-align: top;
        }
        .doc-title {
            font-size: 14pt;
            font-weight: bold;
            color: #111827;
            letter-spacing: 0.5px;
        }
        .doc-number {
            font-size: 13pt;
            font-weight: bold;
            color: #111827;
            margin-top: 2px;
        }
        .doc-date {
            font-size: 8.5pt;
            color: #4b5563;
            margin-top: 2px;
        }
        .info-grid {
            width: 100%;
            margin-bottom: 14px;
            border: 1px solid #d1d5db;
            border-collapse: collapse;
            background-color: #f9fafb;
        }
        .info-grid td {
            padding: 6px 9px;
            vertical-align: top;
            font-size: 8.5pt;
            border: 1px solid #e5e7eb;
        }
        .label {
            font-weight: bold;
            color: #4b5563;
            font-size: 7.5pt;
            text-transform: uppercase;
            display: block;
            margin-bottom: 1px;
        }
        .value {
            color: #111827;
            font-weight: 600;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }
        .items-table th {
            background-color: #1f2937;
            color: #ffffff;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 8pt;
            padding: 6px 7px;
            border: 1px solid #1f2937;
        }
        .items-table td {
            padding: 5.5px 7px;
            border: 1px solid #e5e7eb;
            font-size: 8.5pt;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .total-box {
            width: 42%;
            margin-left: auto;
            border-collapse: collapse;
            margin-bottom: 14px;
        }
        .total-box td {
            padding: 5px 9px;
            font-size: 9pt;
            border: 1px solid #e5e7eb;
        }
        .total-row {
            background-color: #f3f4f6;
            border: 2px solid #111827 !important;
            font-weight: bold;
            font-size: 11pt;
            color: #111827;
        }
        .page-footer {
            position: fixed;
            bottom: -125pt;
            left: 0;
            right: 0;
        }
        .notes-box {
            border: 1px solid #d1d5db;
            padding: 7px 10px;
            margin-bottom: 14px;
            background-color: #f9fafb;
            font-size: 8pt;
            line-height: 1.35;
            border-radius: 4px;
        }
        .notes-title {
            font-weight: bold;
            color: #374151;
            margin-bottom: 2px;
            text-transform: uppercase;
            font-size: 7.5pt;
        }
        .signature-table {
            width: 100%;
            border-collapse: collapse;
        }
        .signature-box {
            width: 42%;
            text-align: center;
            font-size: 8pt;
            color: #374151;
            vertical-align: bottom;
        }
        .signature-line {
            border-top: 1px dashed #6b7280;
            padding-top: 5px;
        }
        .signature-spacer {
            width: 16%;
        }
        .footer-note {
            margin-top: 8px;
            text-align: center;
            font-size: 7.5pt;
            color: #9ca3af;
            border-top: 1px solid #f3f4f6;
            padding-top: 4px;
        }
    </style>
</head>
<body>

    {{-- Encabezado Corporativo --}}
    <table class="header-table">
        <tr>
            <td style="width: 58%; vertical-align: top;">
                <div class="company-name">{{ $company?->name ?? 'Brazil Importaciones' }}</div>
                <div class="company-info">
                    @if($company?->nit) <strong>NIT:</strong> {{ $company->nit }}<br> @endif
                    @if($company?->address) <strong>Dirección:</strong> {{ $company->address }}<br> @endif
                    @if($company?->phone) <strong>Teléfono:</strong> {{ $company->phone }} @endif
                    @if($company?->email) | <strong>Email:</strong> {{ $company->email }} @endif
                </div>
            </td>
            <td class="doc-header-col" style="width: 42%;">
                <div class="doc-title">{{ $title }}</div>
                @if($number)
                    <div class="doc-number">{{ $number }}</div>
                @endif
                @if($date)
                    <div class="doc-date">Fecha: {{ $date }}</div>
                @endif
            </td>
        </tr>
    </table>

    {{-- Contenido Principal --}}
    {{ $slot }}

    {{-- Pie de Página Fijo en la Parte Inferior --}}
    <div class="page-footer">
        {{ $footer ?? '' }}

        @if($footerNote)
            <div class="footer-note">
                {{ $footerNote }}
            </div>
        @endif
    </div>

</body>
</html>
