@php
    $rows = data_get($data, 'rows', []);
    $targetRowCount = max(count($rows), (int) ($padToRows ?? count($rows)));
@endphp

<div class="mgmt-table-scroll">
    <table class="mgmt-report-table">
        <tbody>
        @foreach($rows as $row)
            <tr>
                <td>{{ $row['label'] }}</td>
                <td class="num">{{ number_format($row['amount'], data_get($meta, 'currency_decimals', 2)) }}</td>
            </tr>
        @endforeach
        @for($rowIndex = count($rows); $rowIndex < $targetRowCount; $rowIndex++)
            <tr class="mgmt-report-spacer-row" aria-hidden="true">
                <td>&nbsp;</td>
                <td class="num">&nbsp;</td>
            </tr>
        @endfor
        </tbody>
        <tfoot>
            <tr>
                <th>Total Add</th>
                <th class="num">{{ number_format(data_get($data, 'total', 0), data_get($meta, 'currency_decimals', 2)) }}</th>
            </tr>
        </tfoot>
    </table>
</div>
