@extends('beautysaloons::layout')
@section('beauty_content')
<div class="bs-page">
    <div class="bs-page-header"><h3>Wallet Statement - {{ $wallet->wallet_no }}</h3><a href="{{ route('beauty-saloons.wallets.index') }}" class="btn btn-default">Back</a></div>
    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    <div class="row">
        <div class="col-md-4"><div class="bs-kpi"><label>Customer</label><strong>{{ $wallet->customer_name }}</strong></div></div>
        <div class="col-md-4"><div class="bs-kpi"><label>Mobile</label><strong>{{ $wallet->customer_mobile }}</strong></div></div>
        <div class="col-md-4"><div class="bs-kpi"><label>Balance</label><strong>{{ number_format($wallet->balance, 2) }}</strong></div></div>
    </div>
    <div class="row bs-wallet-actions">
        <div class="col-md-4"><form method="POST" action="{{ route('beauty-saloons.wallets.top-up', $wallet->id) }}">@csrf<label>Top-up</label><input name="amount" class="form-control input_number" required><button class="btn btn-success btn-block mt-2">Top-up</button></form></div>
        <div class="col-md-4"><form method="POST" action="{{ route('beauty-saloons.wallets.debit', $wallet->id) }}">@csrf<label>Debit / Usage</label><input name="amount" class="form-control input_number" required><button class="btn btn-warning btn-block mt-2">Debit</button></form></div>
        <div class="col-md-4"><form method="POST" action="{{ route('beauty-saloons.wallets.refund', $wallet->id) }}">@csrf<label>Refund</label><input name="amount" class="form-control input_number" required><button class="btn btn-info btn-block mt-2">Refund</button></form></div>
    </div>
    <div class="table-responsive bs-table-scroll mt-3"><table class="table table-bordered"><thead><tr><th>Date</th><th>No</th><th>Type</th><th>Direction</th><th>Amount</th><th>Balance</th><th>Note</th></tr></thead><tbody>
        @foreach($transactions as $row)<tr><td>{{ $row->transaction_date }}</td><td>{{ $row->transaction_no }}</td><td>{{ $row->transaction_type }}</td><td>{{ $row->direction }}</td><td class="text-right">{{ number_format($row->amount, 2) }}</td><td class="text-right">{{ number_format($row->balance_after, 2) }}</td><td>{{ $row->note }}</td></tr>@endforeach
    </tbody></table></div>
    {{ $transactions->links() }}
</div>
@endsection
