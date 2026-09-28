@extends('beautysaloons::layout')
@section('beauty_content')
<div class="bs-page">
    <div class="bs-page-header">
        <h3>Customer Wallets</h3>
        <a href="{{ route('beauty-saloons.wallets.create') }}" class="btn btn-primary">Add Wallet</a>
    </div>
    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    <div class="table-responsive bs-table-scroll">
        <table class="table table-bordered table-striped">
            <thead><tr><th>Wallet No</th><th>Customer</th><th>Mobile</th><th>Balance</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
            @forelse($wallets as $wallet)
                <tr>
                    <td>{{ $wallet->wallet_no }}</td>
                    <td>{{ $wallet->customer_name }}</td>
                    <td>{{ $wallet->customer_mobile }}</td>
                    <td class="text-right">{{ number_format($wallet->balance, 2) }}</td>
                    <td>{{ ucfirst($wallet->status) }}</td>
                    <td><a href="{{ route('beauty-saloons.wallets.show', $wallet->id) }}" class="btn btn-sm btn-info">Statement</a></td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center">No wallets found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $wallets->links() }}
</div>
@endsection
