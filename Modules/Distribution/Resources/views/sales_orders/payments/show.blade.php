<div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <h4 class="modal-title">Sales Order Payment Details - {{ $sales_order->sales_order_no }}</h4>
        </div>
        <div class="modal-body">
            <div class="row">
                <div class="col-md-6">
                    <strong>Customer:</strong>
                    {{ $sales_order->customer_name ?: optional($sales_order->customer)->name }}
                </div>
                <div class="col-md-3 text-right">
                    <strong>Total Paid:</strong> {{ number_format((float) $total, 2) }}
                </div>
                <div class="col-md-3 text-right">
                    <strong>Balance Due:</strong> {{ number_format((float) $balance_due, 2) }}
                </div>
            </div>
            <br>
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Invoice No</th>
                            <th>Date</th>
                            <th>Payment Method</th>
                            <th class="text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            <tr>
                                <td>{{ $row['invoice_no'] ?? '-' }}</td>
                                <td>{{ $row['invoice_date'] ?? '-' }}</td>
                                <td>{{ $row['method'] }}</td>
                                <td class="text-right">{{ number_format((float) $row['amount'], 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center">No payment details found for this sales order.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="3" class="text-right">Total</th>
                            <th class="text-right">{{ number_format((float) $total, 2) }}</th>
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
