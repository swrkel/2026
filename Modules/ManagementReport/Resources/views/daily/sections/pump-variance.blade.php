<table class="mgmt-report-table">
    <thead><tr><th>Pump Operator</th><th class="num">Shortage</th><th class="num">Excess</th><th class="num">Net Variance</th></tr></thead>
    <tbody>
    @forelse($data['rows'] as $row)<tr><td>{{ $row['operator'] }}</td><td class="num">{{ number_format($row['shortage'], 2) }}</td><td class="num">{{ number_format($row['excess'], 2) }}</td><td class="num">{{ number_format($row['excess'] - $row['shortage'], 2) }}</td></tr>
    @empty<tr><td colspan="4" class="empty">No shortage or excess records for this period.</td></tr>@endforelse
    </tbody>
    <tfoot><tr><th>Total</th><th class="num">{{ number_format($data['shortage_total'], 2) }}</th><th class="num">{{ number_format($data['excess_total'], 2) }}</th><th class="num">{{ number_format($data['excess_total'] - $data['shortage_total'], 2) }}</th></tr></tfoot>
</table>
