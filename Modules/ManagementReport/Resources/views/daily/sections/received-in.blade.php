<div class="mgmt-table-scroll">
    <table class="mgmt-report-table">
        <thead>
            <tr>
                <th>Account Sector</th>
                <th>Accounts Module Account</th>
                <th class="num">Received Amount</th>
            </tr>
        </thead>
        <tbody>
        @php $lastSector = null; @endphp
        @forelse(data_get($data, 'rows', []) as $row)
            <tr>
                <td>
                    @if($lastSector !== $row['sector'])
                        <strong>{{ $row['sector'] }}</strong>
                        @php $lastSector = $row['sector']; @endphp
                    @endif
                </td>
                <td>{{ $row['account'] }}</td>
                <td class="num">{{ number_format($row['amount'], data_get($meta, 'currency_decimals', 2)) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="3" class="text-center">No Accounts Module received-in movements for the selected period.</td>
            </tr>
        @endforelse
        </tbody>
        @if(count(data_get($data, 'sector_totals', [])))
        <tfoot>
            @foreach(data_get($data, 'sector_totals', []) as $sector)
                <tr>
                    <th colspan="2">{{ $sector['sector'] }} - Total Received</th>
                    <th class="num">{{ number_format($sector['amount'], data_get($meta, 'currency_decimals', 2)) }}</th>
                </tr>
            @endforeach
            <tr>
                <th colspan="2">Total Received In</th>
                <th class="num">{{ number_format(data_get($data, 'total', 0), data_get($meta, 'currency_decimals', 2)) }}</th>
            </tr>
        </tfoot>
        @endif
    </table>
</div>
