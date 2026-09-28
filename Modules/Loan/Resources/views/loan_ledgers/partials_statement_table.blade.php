<div class="table-responsive">
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Transaction Date</th>
                <th>System Entered Date & Time</th>
                <th>Reference No</th>
                <th>Description</th>
                <th class="text-right">Debit</th>
                <th class="text-right">Credit</th>
                <th class="text-right">Balance</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ $row->transaction_date }}</td>
                    <td>{{ $row->system_date }}</td>
                    <td>{{ $row->reference_no }}</td>
                    <td>{{ $row->description }}</td>
                    <td class="text-right">{{ number_format($row->debit, 2) }}</td>
                    <td class="text-right">{{ number_format($row->credit, 2) }}</td>
                    <td class="text-right">{{ number_format($row->balance, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted">No statement entries found.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <th colspan="4" class="text-right">Totals</th>
                <th class="text-right">{{ number_format($summary->total_debit, 2) }}</th>
                <th class="text-right">{{ number_format($summary->total_credit, 2) }}</th>
                <th class="text-right">{{ number_format($summary->balance, 2) }}</th>
            </tr>
        </tfoot>
    </table>
</div>
