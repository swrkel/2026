@extends('layouts.app')
@section('title', 'Deposit Reports')
@section('content')
<section class="content-header no-print"><h1>Deposit Reports</h1></section>
<section class="content no-print">
@include('deposits::layouts.nav')
<div class="row">
    <div class="col-md-3"><div class="small-box bg-aqua"><div class="inner"><h3>{{ number_format($summary['active_accounts']) }}</h3><p>Active Accounts</p></div><div class="icon"><i class="fa fa-bank"></i></div></div></div>
    <div class="col-md-3"><div class="small-box bg-green"><div class="inner"><h3>{{ number_format($summary['balance'],2) }}</h3><p>Total Balance</p></div><div class="icon"><i class="fa fa-money"></i></div></div></div>
    <div class="col-md-3"><div class="small-box bg-yellow"><div class="inner"><h3>{{ number_format($summary['maturity_due']) }}</h3><p>Maturity Due</p></div><div class="icon"><i class="fa fa-calendar"></i></div></div></div>
    <div class="col-md-3"><div class="small-box bg-red"><div class="inner"><h3>{{ number_format($summary['closed_accounts']) }}</h3><p>Closed Accounts</p></div><div class="icon"><i class="fa fa-lock"></i></div></div></div>
</div>
<div class="box box-primary">
    <div class="box-header with-border"><h3 class="box-title">Report Filters</h3></div>
    <div class="box-body">
        <form method="GET" class="row">
            <div class="col-md-3 form-group"><label>Report Type</label><select name="type" class="form-control"><option value="summary" {{ $type=='summary'?'selected':'' }}>Summary</option><option value="active" {{ $type=='active'?'selected':'' }}>Active Deposits</option><option value="maturity_due" {{ $type=='maturity_due'?'selected':'' }}>Maturity Due</option><option value="maturity_next_30" {{ $type=='maturity_next_30'?'selected':'' }}>Maturity Next 30 Days</option><option value="closed" {{ $type=='closed'?'selected':'' }}>Closed Deposits</option><option value="renewed" {{ $type=='renewed'?'selected':'' }}>Renewed Deposits</option></select></div>
            <div class="col-md-3 form-group"><label>Location</label><select name="location_id" class="form-control"><option value="">All Locations</option>@foreach($locations as $id=>$name)<option value="{{ $id }}" {{ request('location_id')==$id?'selected':'' }}>{{ $name }}</option>@endforeach</select></div>
            <div class="col-md-2 form-group"><label>&nbsp;</label><button class="btn btn-primary btn-block"><i class="fa fa-search"></i> Run Report</button></div><div class="col-md-2 form-group"><label>&nbsp;</label><a class="btn btn-success btn-block" href="{{ request()->fullUrlWithQuery(['format' => 'csv']) }}"><i class="fa fa-download"></i> CSV</a></div>
        </form>
    </div>
</div>
<div class="box box-primary"><div class="box-header with-border"><h3 class="box-title">Report Results</h3></div><div class="box-body table-responsive">
<table class="table table-bordered table-striped"><thead><tr><th>Account No</th><th>Customer</th><th>Product</th><th>Principal</th><th>Balance</th><th>Interest</th><th>Opened</th><th>Maturity</th><th>Status</th></tr></thead><tbody>
@forelse($accounts as $account)<tr><td>{{ $account->account_no }}</td><td>{{ $account->customer_name }}</td><td>{{ optional($account->product)->name }}</td><td>{{ number_format($account->principal_amount,2) }}</td><td>{{ number_format($account->current_balance,2) }}</td><td>{{ number_format($account->interest_accrued,2) }}</td><td>{{ $account->opened_on }}</td><td>{{ $account->maturity_on }}</td><td>{{ ucfirst($account->status) }}</td></tr>@empty<tr><td colspan="9" class="text-center">No records found</td></tr>@endforelse
</tbody></table>@if(method_exists($accounts, 'appends')) {{ $accounts->appends(request()->query())->links() }} @endif
</div></div>
</section>
@endsection
