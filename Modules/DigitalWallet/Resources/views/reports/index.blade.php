@extends('digitalwallet::layout')
@section('digitalwallet-title', 'Digital Wallet Reports')
@section('digitalwallet-content')
<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">Summary</h3>
        <a class="btn btn-success btn-sm pull-right" href="{{ route('digitalwallet.reports.export', request()->all()) }}">CSV / Export View</a>
    </div>
    <div class="box-body">
        <div class="row">
            <div class="col-md-3"><b>Total Wallets:</b> {{ $summary['total_wallets'] ?? 0 }}</div>
            <div class="col-md-3"><b>Available:</b> {{ number_format($summary['available_balance'] ?? 0, 2) }}</div>
            <div class="col-md-3"><b>Top-ups Today:</b> {{ number_format($summary['topups_today'] ?? 0, 2) }}</div>
            <div class="col-md-3"><b>Charges Today:</b> {{ number_format($summary['charges_today'] ?? 0, 2) }}</div>
        </div>
        <hr>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead><tr><th>No</th><th>Wallet</th><th>Type</th><th>Amount</th><th>Before</th><th>After</th><th>Source</th><th>Status</th><th>Date</th></tr></thead>
                <tbody>
                @forelse($transactions as $transaction)
                    <tr>
                        <td>{{ $transaction->transaction_no }}</td>
                        <td>{{ optional($transaction->wallet)->wallet_code }}</td>
                        <td>{{ ucfirst($transaction->transaction_type) }}</td>
                        <td>{{ number_format($transaction->amount, 2) }}</td>
                        <td>{{ number_format($transaction->balance_before, 2) }}</td>
                        <td>{{ number_format($transaction->balance_after, 2) }}</td>
                        <td>{{ $transaction->source_module }}</td>
                        <td>{{ ucfirst($transaction->status) }}</td>
                        <td>{{ $transaction->created_at }}</td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center">No transactions found.</td></tr>
                @endforelse
                </tbody>
            </table>
            {{ $transactions->links() }}
        </div>
    </div>
</div>
@endsection
