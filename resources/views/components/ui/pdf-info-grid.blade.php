@props([
    'rows' => [],
])

<table class="info-grid">
    @foreach ($rows as $row)
        <tr>
            @foreach ($row as $col)
                <td style="width: {{ $col['width'] ?? 'auto' }};">
                    <span class="label">{{ $col['label'] }}:</span>
                    <span class="value" @if(!empty($col['highlight'])) style="font-size: 11pt;" @endif>
                        {{ $col['value'] ?? '—' }}
                    </span>
                </td>
            @endforeach
        </tr>
    @endforeach
</table>
