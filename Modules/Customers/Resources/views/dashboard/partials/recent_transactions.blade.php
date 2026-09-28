<div class="box box-success customers-dashboard-box">
    <div class="box-header with-border">
        <h3 class="box-title">Recent Customer Transactions</h3>
    </div>
    <div class="box-body table-responsive no-padding">
        <table class="table table-hover table-striped">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Customer</th>
                    <th>Invoice / Ref</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th class="customers-money">Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recent_transactions as $row)
                    <tr>
                        <td>{{ !empty($row->transaction_date) ? date('Y-m-d', strtotime($row->transaction_date)) : '' }}</td>
                        <td>{{ $row->customer_name }}</td>
                        <td>{{ $row->invoice_no ?: $row->ref_no }}</td>
                        <td>{{ ucwords(str_replace('_', ' ', $row->type)) }}</td>
                        <td>{{ ucwords(str_replace('_', ' ', $row->payment_status ?? '')) }}</td>
                        <td class="customers-money">{{ number_format((float) ($row->final_total ?? 0), 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted">No customer transactions found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
