@extends('layouts.app')
@section('title', 'Loan Statement')

@section('content')
<section class="content-header no-print">
    <h1>Loan Statement <small>#{{ $loan->id ?? '' }}</small></h1>
</section>

<section class="content no-print">
    <div class="row">
        <div class="col-md-4"><div class="box box-primary"><div class="box-header with-border"><h3 class="box-title">Loan Summary</h3></div><div class="box-body">
            <p><strong>Status:</strong> {{ ucfirst(str_replace('_', ' ', $loan->status ?? '-')) }}</p>
            <p><strong>Principal:</strong> {{ number_format($balance['principal'] ?? 0, 2) }}</p>
            <p><strong>Paid:</strong> {{ number_format($balance['paid'] ?? 0, 2) }}</p>
            <p><strong>Balance:</strong> {{ number_format($balance['balance'] ?? 0, 2) }}</p>
            <p><strong>Early Settlement:</strong> {{ number_format($settlement['settlement_amount'] ?? 0, 2) }}</p>
        </div></div></div>
        <div class="col-md-8"><div class="box box-primary"><div class="box-header with-border"><h3 class="box-title">Transactions</h3></div><div class="box-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead><tr><th>Date</th><th>Reference</th><th>Amount</th><th>Note</th></tr></thead>
                <tbody>
                    @forelse($transactions as $txn)
                        <tr>
                            <td>{{ $txn->transaction_date ?? $txn->created_at ?? '-' }}</td>
                            <td>{{ $txn->reference_no ?? $txn->receipt_no ?? $txn->id ?? '-' }}</td>
                            <td>{{ number_format($txn->amount ?? $txn->paid_amount ?? $txn->total_amount ?? 0, 2) }}</td>
                            <td>{{ $txn->note ?? $txn->remarks ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted">No repayment transactions found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div></div></div>
    </div>
</section>
@endsection
