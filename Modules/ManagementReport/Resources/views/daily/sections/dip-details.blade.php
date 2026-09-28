<table class="mgmt-report-table">
    <thead><tr><th>Tank</th><th class="num">Physical Dip Qty</th><th class="num">System Qty</th><th class="num">Difference</th></tr></thead>
    <tbody>
    @forelse($data['rows'] as $row)<tr><td>{{ $row['tank'] }}</td><td class="num">{{ number_format($row['dip_reading'], 3) }}</td><td class="num">{{ number_format($row['system_qty'], 3) }}</td><td class="num">{{ number_format($row['difference'], 3) }}</td></tr>
    @empty<tr><td colspan="4" class="empty">No dip readings for this period.</td></tr>@endforelse
    </tbody>
    <tfoot><tr><th colspan="3">Total Dip Difference</th><th class="num">{{ number_format($data['difference_total'], 3) }}</th></tr></tfoot>
</table>
