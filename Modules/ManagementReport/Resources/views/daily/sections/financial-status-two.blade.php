<table class="mgmt-report-table mgmt-financial-status-two-table" style="table-layout:fixed;width:100%">
    <colgroup>
        <col class="mgmt-fin2-col-position" style="width:55%">
        <col class="mgmt-fin2-col-classification" style="width:25%">
        <col class="mgmt-fin2-col-amount" style="width:20%">
    </colgroup>
    <thead><tr><th>Financial Position II</th><th>Classification</th><th class="num">Amount</th></tr></thead>
    <tbody>
    @foreach($data['rows'] as $row)
        <tr><td>{{ $row['label'] }}</td><td>{{ ucfirst($row['type']) }}</td><td class="num">{{ number_format($row['amount'], data_get($meta, 'currency_decimals', 2)) }}</td></tr>
    @endforeach
    </tbody>
    <tfoot>
        <tr><th colspan="2">Total Assets</th><th class="num">{{ number_format($data['total_assets'], data_get($meta, 'currency_decimals', 2)) }}</th></tr>
        <tr><th colspan="2">Total Liabilities</th><th class="num">{{ number_format($data['total_liabilities'], data_get($meta, 'currency_decimals', 2)) }}</th></tr>
        <tr><th colspan="2">Net Working Position</th><th class="num">{{ number_format($data['net_working_position'], data_get($meta, 'currency_decimals', 2)) }}</th></tr>
    </tfoot>
</table>
