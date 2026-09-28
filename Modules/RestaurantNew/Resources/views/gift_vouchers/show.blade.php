@extends('restaurantnew::layouts.app')

@section('title', $voucher->voucher_no)

@section('content')
<div class="restnew-page">
    <div class="restnew-toolbar"><h3>{{ $voucher->voucher_no }}</h3><span class="restnew-balance">{{ number_format($voucher->balance_amount, 4) }}</span></div>
    <div class="restnew-card">
        <form method="POST" action="{{ route('restaurant-new.gift-vouchers.redeem', $voucher) }}" class="row">
            @csrf
            <div class="col-md-4"><input name="amount" type="number" step="0.0001" class="form-control" placeholder="Redeem amount"></div>
            <div class="col-md-6"><input name="note" class="form-control" placeholder="Note"></div>
            <div class="col-md-2"><button class="btn btn-warning btn-block">{{ __('restaurantnew::gift_voucher.redeem') }}</button></div>
        </form>
    </div>
    <div class="restnew-card mt-20">
        <h4>{{ __('restaurantnew::gift_voucher.transactions') }}</h4>
        <table class="table table-bordered">
            <thead><tr><th>Date</th><th>Type</th><th>Amount</th><th>Balance</th><th>Note</th></tr></thead>
            <tbody>@foreach($voucher->transactions as $txn)<tr><td>{{ $txn->created_at }}</td><td>{{ $txn->transaction_type }}</td><td>{{ number_format($txn->amount, 4) }}</td><td>{{ number_format($txn->balance_after, 4) }}</td><td>{{ $txn->note }}</td></tr>@endforeach</tbody>
        </table>
    </div>
</div>
@endsection
