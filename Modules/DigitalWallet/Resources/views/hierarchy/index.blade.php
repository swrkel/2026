@extends('digitalwallet::layout')
@section('digitalwallet-title', 'Wallet Hierarchy')
@section('digitalwallet-content')
<div class="row">
@foreach([
    'Business Wallets' => $summary['business_wallets'] ?? 0,
    'Branch Wallets' => $summary['branch_wallets'] ?? 0,
    'Department Wallets' => $summary['department_wallets'] ?? 0,
    'User Wallets' => $summary['user_wallets'] ?? 0,
    'Locked Wallets' => $summary['locked_wallets'] ?? 0,
    'Wallets with Limits' => $summary['with_limits'] ?? 0,
] as $label => $value)
    <div class="col-md-2 col-sm-6"><div class="small-box bg-aqua"><div class="inner"><h3>{{ $value }}</h3><p>{{ $label }}</p></div><div class="icon"><i class="fa fa-sitemap"></i></div></div></div>
@endforeach
</div>
<div class="box box-primary"><div class="box-header with-border"><h3 class="box-title">Hierarchy Tree</h3></div><div class="box-body table-responsive">
<table class="table table-bordered table-striped"><thead><tr><th>Wallet</th><th>Level</th><th>Business</th><th>Branch</th><th>Department</th><th>User</th><th>Balance</th><th>Children</th></tr></thead><tbody>
@forelse($walletTree as $wallet)
<tr><td>{{ $wallet->wallet_code }} - {{ $wallet->wallet_name }}</td><td>{{ ucfirst($wallet->hierarchy_level) }}</td><td>{{ $wallet->business_id ?: '-' }}</td><td>{{ $wallet->location_id ?: '-' }}</td><td>{{ $wallet->department_id ?: '-' }}</td><td>{{ $wallet->user_id ?: '-' }}</td><td>{{ number_format($wallet->available_balance, 2) }} {{ $wallet->currency }}</td><td>{{ $wallet->childWallets->count() }}</td></tr>
@foreach($wallet->childWallets as $child)
<tr><td style="padding-left:35px;">↳ {{ $child->wallet_code }} - {{ $child->wallet_name }}</td><td>{{ ucfirst($child->hierarchy_level) }}</td><td>{{ $child->business_id ?: '-' }}</td><td>{{ $child->location_id ?: '-' }}</td><td>{{ $child->department_id ?: '-' }}</td><td>{{ $child->user_id ?: '-' }}</td><td>{{ number_format($child->available_balance, 2) }} {{ $child->currency }}</td><td>{{ $child->childWallets->count() }}</td></tr>
@endforeach
@empty
<tr><td colspan="8" class="text-center">No wallets found.</td></tr>
@endforelse
</tbody></table>
</div></div>
@endsection
