<table class="mgmt-report-table mgmt-stock-value-status-table">
    <thead>
        <tr>
            <th>Stock Status</th>
            <th class="num">Value</th>
        </tr>
    </thead>
    <tbody>
    @forelse(data_get($data, 'rows', []) as $row)
        <tr>
            <td>{{ data_get($row, 'label', '') }}</td>
            <td class="num">{{ number_format((float) data_get($row, 'amount', 0), 2) }}</td>
        </tr>
    @empty
        <tr><td colspan="2" class="empty">Stock value data is unavailable for this scope.</td></tr>
    @endforelse
    </tbody>
    <tfoot>
        <tr>
            <th>Balance Stock</th>
            <th class="num">{{ number_format((float) data_get($data, 'balance_stock', data_get($data, 'total', 0)), 2) }}</th>
        </tr>
    </tfoot>
</table>
