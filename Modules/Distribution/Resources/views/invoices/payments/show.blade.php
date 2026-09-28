<div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">Distribution Invoice Payments - {{ $invoice->invoice_no ?? $invoice->id }}</h4>
        </div>
        <div class="modal-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Method</th>
                            <th>Reference</th>
                            <th class="text-right">Amount</th>
                            <th>Note</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            <tr>
                                <td>{{ !empty($row['paid_on']) ? @format_date($row['paid_on']) : '-' }}</td>
                                <td>{{ $row['method'] ?? '-' }}</td>
                                <td>{{ $row['payment_ref_no'] ?? '-' }}</td>
                                <td class="text-right">{{ number_format((float) ($row['amount'] ?? 0), 2) }}</td>
                                <td>{{ $row['note'] ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">No payment details found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="3" class="text-right">Total Paid</th>
                            <th class="text-right">{{ number_format((float) $total, 2) }}</th>
                            <th></th>
                        </tr>
                        <tr>
                            <th colspan="3" class="text-right">Balance Due</th>
                            <th class="text-right">{{ number_format((float) $balanceDue, 2) }}</th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        </div>
    </div>
</div>
