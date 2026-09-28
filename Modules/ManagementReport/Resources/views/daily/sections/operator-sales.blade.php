<div class="mgmt-table-scroll">
<table class="mgmt-report-table mgmt-compact">
    <thead>
        <tr>
            <th>Cashier / Pump Operator</th><th>Settlement</th>
            <th class="num">Cash</th><th class="num">Cheque</th><th class="num">Bank</th><th class="num">Card</th><th class="num">Credit</th>
            <th class="num">Loans</th><th class="num">Drawings</th><th class="num">Shortage</th><th class="num">Excess</th>
            <th class="num">Commission</th><th class="num">Expenses</th><th class="num">Total Sale</th>
        </tr>
    </thead>
    <tbody>
    @forelse($data['rows'] as $row)
    <tr>
        <td>{{ $row['operator'] }}</td><td>{{ $row['settlement_no'] }}</td>
        @foreach(['cash','cheque','bank','card','credit','loans','drawings','shortage','excess','commission','expenses','total_sale'] as $key)
            <td class="num">{{ number_format($row[$key], data_get($meta, 'currency_decimals', 2)) }}</td>
        @endforeach
    </tr>
    @empty
    <tr><td colspan="14" class="empty">No cashier or pump operator sales data for this period.</td></tr>
    @endforelse
    </tbody>
    <tfoot>
        <tr><th colspan="2">Total</th>
        @foreach(['cash','cheque','bank','card','credit','loans','drawings','shortage','excess','commission','expenses','total_sale'] as $key)
            <th class="num">{{ number_format(data_get($data, 'totals.'.$key, 0), data_get($meta, 'currency_decimals', 2)) }}</th>
        @endforeach
        </tr>
    </tfoot>
</table>
</div>
