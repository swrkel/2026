@extends('layouts.app')
@section('title', 'Deposit Accounts')
@section('content')
<section class="content-header no-print"><h1>Deposit Accounts</h1></section>
<section class="content no-print">
@include('deposits::layouts.nav')
<div class="box box-primary">
    <div class="box-header with-border"><h3 class="box-title">Deposit Accounts</h3><div class="box-tools"><a href="{{ route('deposits.accounts.create') }}" class="btn btn-primary"><i class="fa fa-plus"></i> Add</a></div></div>
    <div class="box-body">
        <form method="GET" class="row" style="margin-bottom: 10px;">
            <div class="col-md-4 form-group"><input name="search" class="form-control" placeholder="Search account/customer" value="{{ request('search') }}"></div>
            <div class="col-md-3 form-group"><select name="status" class="form-control"><option value="">All Status</option>@foreach(['active','matured','closed','renewed'] as $status)<option value="{{ $status }}" {{ request('status')==$status?'selected':'' }}>{{ ucfirst($status) }}</option>@endforeach</select></div>
            <div class="col-md-2 form-group"><button class="btn btn-primary"><i class="fa fa-search"></i> Search</button></div>
        </form>
        <div class="table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Account No</th><th>Customer</th><th>Product</th><th>Principal</th><th>Balance</th><th>Maturity</th><th>Status</th><th>Action</th></tr></thead><tbody>
        @forelse($accounts as $account)<tr><td><a href="{{ route('deposits.accounts.show', $account->id) }}">{{ $account->account_no }}</a></td><td>{{ $account->customer_name }}</td><td>{{ optional($account->product)->name }}</td><td>{{ number_format($account->principal_amount, 2) }}</td><td>{{ number_format($account->current_balance, 2) }}</td><td>{{ $account->maturity_on }}</td><td>{{ ucfirst($account->status) }}</td><td><a class="btn btn-xs btn-primary" href="{{ route('deposits.accounts.edit', $account->id) }}">Edit</a> <a class="btn btn-xs btn-default" href="{{ route('deposits.certificates.show', $account->id) }}">Certificate</a><form action="{{ route('deposits.accounts.delete', $account->id) }}" method="POST" style="display:inline;">@csrf<button class="btn btn-xs btn-danger" onclick="return confirm('Delete?')">Delete</button></form></td></tr>@empty<tr><td colspan="8" class="text-center">No records found</td></tr>@endforelse
        </tbody></table>@if(method_exists($accounts, 'appends')) {{ $accounts->appends(request()->query())->links() }} @endif</div>
    </div>
</div>
</section>
@endsection
