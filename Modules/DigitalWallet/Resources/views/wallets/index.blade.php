@extends('digitalwallet::layout')
@section('digitalwallet-title', 'Digital Wallets')
@section('digitalwallet-content')
<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">Wallet Register</h3>
        <a href="{{ route('digitalwallet.wallets.create') }}" class="btn btn-primary btn-sm pull-right"><i class="fa fa-plus"></i> Add Wallet</a>
    </div>
    <div class="box-body table-responsive">
        <table class="table table-bordered table-striped">
            <thead><tr><th>Code</th><th>Name</th><th>Type</th><th>Level</th><th>Parent</th><th>Scope</th><th>Currency</th><th>Available</th><th>Limit</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
            @forelse($wallets as $wallet)
                <tr>
                    <td>{{ $wallet->wallet_code }}</td>
                    <td>{{ $wallet->wallet_name }}</td>
                    <td>{{ $wallet->wallet_type }}</td>
                    <td>{{ ucfirst($wallet->hierarchy_level ?: 'business') }}</td>
                    <td>{{ optional($wallet->parentWallet)->wallet_code }}</td>
                    <td>B: {{ $wallet->business_id ?: '-' }} / L: {{ $wallet->location_id ?: '-' }} / D: {{ $wallet->department_id ?: '-' }} / U: {{ $wallet->user_id ?: '-' }}</td>
                    <td>{{ $wallet->currency }}</td>
                    <td>{{ number_format($wallet->available_balance, 2) }}</td>
                    <td>{{ number_format($wallet->daily_spend_limit ?: 0, 2) }} / {{ number_format($wallet->monthly_spend_limit ?: 0, 2) }}</td>
                    <td>{{ $wallet->is_locked ? 'Locked' : ucfirst($wallet->status) }}</td>
                    <td><a class="btn btn-xs btn-primary" href="{{ route('digitalwallet.wallets.edit', $wallet) }}">Edit</a></td>
                </tr>
            @empty
                <tr><td colspan="11" class="text-center">No wallets found.</td></tr>
            @endforelse
            </tbody>
        </table>
        {{ $wallets->links() }}
    </div>
</div>
@endsection
