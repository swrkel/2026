@extends('digitalwallet::layout')
@section('digitalwallet-title', 'Digital Wallet Dashboard')
@section('digitalwallet-content')
<div class="row">
@foreach([
    'Total Wallets' => $summary['total_wallets'] ?? 0,
    'Business Wallets' => $summary['business_wallets'] ?? 0,
    'Branch Wallets' => $summary['branch_wallets'] ?? 0,
    'Department Wallets' => $summary['department_wallets'] ?? 0,
    'User Wallets' => $summary['user_wallets'] ?? 0,
    'Available Balance' => number_format($summary['available_balance'] ?? 0, 2),
    'Reserved Balance' => number_format($summary['reserved_balance'] ?? 0, 2),
    'Transfers Today' => number_format($summary['transfers_today'] ?? 0, 2),
    'Pending Approvals' => $summary['pending_approvals'] ?? 0,
    'Low Balance Wallets' => $summary['low_balance_wallets'] ?? 0,
    'Locked Wallets' => $summary['locked_wallets'] ?? 0,
    'Wallet Types' => $summary['wallet_types'] ?? 0,
] as $label => $value)
    <div class="col-md-3 col-sm-6"><div class="small-box bg-aqua"><div class="inner"><h3>{{ $value }}</h3><p>{{ $label }}</p></div><div class="icon"><i class="fa fa-wallet"></i></div></div></div>
@endforeach
</div>
<div class="box box-primary">
    <div class="box-header with-border"><h3 class="box-title">DIGITALWALLET_002 - Multi-Business / Multi-Branch Wallets</h3></div>
    <div class="box-body">
        <p>This standalone module now supports business, branch, department, user and shared wallets with wallet types, limits, transfers and approval foundations. It does not depend on the legacy Wallet module or CommunicationHub internals.</p>
        <div class="row">
            <div class="col-md-3"><a class="btn btn-block btn-primary" href="{{ route('digitalwallet.wallets.index') }}">Wallet Register</a></div>
            <div class="col-md-3"><a class="btn btn-block btn-info" href="{{ route('digitalwallet.hierarchy.index') }}">Wallet Hierarchy</a></div>
            <div class="col-md-3"><a class="btn btn-block btn-warning" href="{{ route('digitalwallet.transfers.index') }}">Transfers</a></div>
            <div class="col-md-3"><a class="btn btn-block btn-success" href="{{ route('digitalwallet.approvals.index') }}">Approvals</a></div>
        </div>
    </div>
</div>
@endsection
