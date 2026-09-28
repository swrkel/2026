<table class="mgmt-report-table">
    <thead><tr><th>Outstanding Type</th><th class="num">Opening</th><th class="num">Increase</th><th class="num">Settled</th><th class="num">Balance</th></tr></thead>
    <tbody>@foreach($data['rows'] as $row)<tr><td>{{ $row['label'] }}</td><td class="num">{{ number_format($row['opening'], 2) }}</td><td class="num">{{ number_format($row['increase'], 2) }}</td><td class="num">{{ number_format($row['settled'], 2) }}</td><td class="num"><strong>{{ number_format($row['balance'], 2) }}</strong></td></tr>@endforeach</tbody>
    <tfoot><tr><th colspan="4">Total Outstanding</th><th class="num">{{ number_format($data['total'], 2) }}</th></tr></tfoot>
</table>
