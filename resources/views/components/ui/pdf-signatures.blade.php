@props([
    'company' => 'Brazil Importaciones',
    'customer' => 'Cliente Aceptante',
    'companyLabel' => 'Firma Responsable Ventas',
    'customerLabel' => 'Conforme Cliente / Aceptación',
])

<table class="signature-table">
    <tr>
        <td class="signature-box">
            <div style="height: 35px;"></div>
            <div class="signature-line">
                {{ $companyLabel }}<br>
                <strong>{{ $company }}</strong>
            </div>
        </td>
        <td class="signature-spacer"></td>
        <td class="signature-box">
            <div style="height: 35px;"></div>
            <div class="signature-line">
                {{ $customerLabel }}<br>
                <strong>{{ $customer }}</strong>
            </div>
        </td>
    </tr>
</table>
