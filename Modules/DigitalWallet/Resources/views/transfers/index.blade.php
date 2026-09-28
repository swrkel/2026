@extends('digitalwallet::layout')
@section('digitalwallet-title', 'Wallet Transfers')
@section('digitalwallet-content')
<div class="box box-primary"><div class="box-header with-border"><h3 class="box-title">Transfer Register</h3><a href="{{ route('digitalwallet.transfers.create') }}" class="btn btn-primary btn-sm pull-right"><i class="fa fa-exchange"></i> New Transfer</a></div><div class="box-body table-responsive">
<table class="table table-bordered table-striped"><thead><tr><th>No</th><th>From</th><th>To</th><th>Amount</th><th>Status</th><th>Approval</th><th>Date</th></tr></thead><tbody>
@forelse($transfers as $transfer)<tr><td>{{ $transfer->transfer_no }}</td><td>{{ optional($transfer->fromWallet)->wallet_code }} - {{ optional($transfer->fromWallet)->wallet_name }}</td><td>{{ optional($transfer->toWallet)->wallet_code }} - {{ optional($transfer->toWallet)->wallet_name }}</td><td>{{ number_format($transfer->amount, 2) }} {{ $transfer->currency }}</td><td>{{ ucfirst($transfer->status) }}</td><td>{{ ucfirst(str_replace('_', ' ', $transfer->approval_status)) }}</td><td>{{ $transfer->created_at }}</td></tr>@empty<tr><td colspan="7" class="text-center">No transfers found.</td></tr>@endforelse
</tbody></table>{{ $transfers->links() }}</div></div>
@endsection
