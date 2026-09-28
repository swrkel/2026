<div class="mgmt-table-scroll">
<table class="mgmt-report-table">
    <thead><tr><th>Activity</th><th class="num">Cash</th><th class="num">Cheque</th><th class="num">Bank</th><th class="num">Card</th><th class="num">Credit</th><th class="num">Total</th></tr></thead>
    <tbody>@foreach($data['rows'] as $row)<tr><td>{{ $row['label'] }}</td>@foreach($data['columns'] as $column)<td class="num">{{ number_format($row[$column], 2) }}</td>@endforeach<td class="num"><strong>{{ number_format($row['total'], 2) }}</strong></td></tr>@endforeach</tbody>
</table>
</div>
