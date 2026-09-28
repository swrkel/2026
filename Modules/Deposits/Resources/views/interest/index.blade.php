@extends('layouts.app')
@section('title', 'Deposit Interest Posting')
@section('content')
<section class="content-header no-print"><h1>Deposit Interest Posting</h1></section>
<section class="content no-print">
@include('deposits::layouts.nav')
<div class="box box-primary">
    <div class="box-header with-border"><h3 class="box-title">Active Deposit Accounts</h3></div>
    <div class="box-body table-responsive">
        <table class="table table-bordered table-striped">
            <thead><tr><th>Account No</th><th>Customer</th><th>Balance</th><th>Rate</th><th>Last Posted</th><th>Action</th></tr></thead>
            <tbody>
            @forelse($accounts as $account)
                <tr>
                    <td>{{ $account->account_no }}</td>
                    <td>{{ $account->customer_name }}</td>
                    <td>{{ number_format($account->current_balance, 2) }}</td>
                    <td>{{ number_format($account->interest_rate, 4) }}%</td>
                    <td>{{ $account->last_interest_posted_on }}</td>
                    <td>
                        <form method="POST" action="{{ route('deposits.interest.post') }}" class="form-inline">
                            @csrf
                            <input type="hidden" name="deposit_account_id" value="{{ $account->id }}">
                            <input type="date" name="posting_date" class="form-control input-sm" value="{{ date('Y-m-d') }}">
                            <button class="btn btn-xs btn-success" onclick="return confirm('Post monthly interest?')">Post Interest</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center">No active deposit accounts found</td></tr>
            @endforelse
            </tbody>
        </table>
        @if(method_exists($accounts, 'links')) {{ $accounts->links() }} @endif
    </div>
</div>
</section>
@endsection
