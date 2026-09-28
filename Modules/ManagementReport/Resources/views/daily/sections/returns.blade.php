<table class="mgmt-report-table">
    <thead><tr><th>Return Type</th><th class="num">Amount</th></tr></thead>
    <tbody>@foreach($data['rows'] as $row)<tr><td>{{ $row['label'] }}</td><td class="num">{{ number_format($row['amount'], 2) }}</td></tr>@endforeach</tbody>
    <tfoot><tr><th>Net Return Difference</th><th class="num">{{ number_format($data['sales_return'] - $data['purchase_return'], 2) }}</th></tr></tfoot>
</table>
