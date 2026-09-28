<table class="mgmt-report-table mgmt-financial-status-table" style="table-layout:fixed;width:100%">
    <colgroup>
        <col class="mgmt-fin-col-position" style="width:28%">
        <col class="mgmt-fin-col-bf" style="width:17%">
        <col class="mgmt-fin-col-in" style="width:18%">
        <col class="mgmt-fin-col-out" style="width:17%">
        <col class="mgmt-fin-col-balance" style="width:20%">
    </colgroup>
    <thead>
        <tr>
            <th>Financial Position</th>
            <th class="num">B/F Balance</th>
            <th class="num">Total In</th>
            <th class="num">Total Out</th>
            <th class="num">Balance</th>
        </tr>
    </thead>
    <tbody>
    @foreach($data['rows'] as $row)
        <tr>
            <td>{{ $row['label'] }}</td>
            <td class="num">{{ number_format($row['previous_balance'], 2) }}</td>
            <td class="num">{{ number_format($row['total_in'], 2) }}</td>
            <td class="num">{{ number_format($row['total_out'], 2) }}</td>
            <td class="num"><strong>{{ number_format($row['balance'], 2) }}</strong></td>
        </tr>
    @endforeach
    </tbody>
    <tfoot>
        <tr><th colspan="4">Total Financial Balance</th><th class="num">{{ number_format($data['total_balance'], 2) }}</th></tr>
    </tfoot>
</table>
