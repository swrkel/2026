@extends('layouts.app')
@section('title', 'Deposit Maturity Due')
@section('content')
<section class="content-header no-print"><h1>Deposit Maturity Due</h1></section>
<section class="content no-print">
@include('deposits::layouts.nav')
<div class="box box-warning">
    <div class="box-header with-border"><h3 class="box-title">Matured / Due Deposits</h3></div>
    <div class="box-body table-responsive">
        <table class="table table-bordered table-striped">
            <thead><tr><th>Account No</th><th>Customer</th><th>Maturity Date</th><th>Balance</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            @forelse($accounts as $account)
                <tr>
                    <td>{{ $account->account_no }}</td>
                    <td>{{ $account->customer_name }}</td>
                    <td>{{ $account->maturity_on }}</td>
                    <td>{{ number_format($account->current_balance, 2) }}</td>
                    <td>{{ ucfirst($account->status) }}</td>
                    <td>
                        <form method="POST" action="{{ route('deposits.maturity.close', $account->id) }}" style="display:inline;">@csrf<button class="btn btn-xs btn-danger" onclick="return confirm('Close this deposit?')">Close</button></form>
                        <form method="POST" action="{{ route('deposits.maturity.renew', $account->id) }}" style="display:inline;">@csrf<button class="btn btn-xs btn-primary" onclick="return confirm('Renew this deposit?')">Renew</button></form>
                        <a class="btn btn-xs btn-default" href="{{ route('deposits.certificates.show', $account->id) }}">Certificate</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center">No matured deposits found</td></tr>
            @endforelse
            </tbody>
        </table>
        @if(method_exists($accounts, 'links')) {{ $accounts->links() }} @endif
    </div>
</div>
</section>
@endsection
