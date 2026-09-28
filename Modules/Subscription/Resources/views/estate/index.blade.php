@extends('layouts.app')
@section('title', 'Subscription Management')
@section('content')
<section class="content-header">
    <h1>Subscription Management <small>Central tenant/business subscriptions</small></h1>
</section>
<section class="content">
@if(session('status'))
    <div class="alert {{ data_get(session('status'),'success') ? 'alert-success' : 'alert-danger' }}">{{ data_get(session('status'),'msg') }}</div>
@endif
<div class="box box-solid">
    <div class="box-header with-border"><h3 class="box-title">Filters</h3></div>
    <div class="box-body">
        <form method="get" class="row">
            <div class="col-md-3"><label>From</label><input type="date" name="start_date" value="{{ request('start_date') }}" class="form-control"></div>
            <div class="col-md-3"><label>To</label><input type="date" name="end_date" value="{{ request('end_date') }}" class="form-control"></div>
            <div class="col-md-3"><label>Tenant Database</label><select name="tenant_id" class="form-control select2"><option value="">All</option>@foreach($tenants as $tenant)<option value="{{ $tenant->id }}" {{ request('tenant_id')==$tenant->id?'selected':'' }}>{{ $tenant->label }}</option>@endforeach</select></div>
            <div class="col-md-3" style="padding-top:25px"><button class="btn btn-primary"><i class="fa fa-filter"></i> Apply</button> <a href="{{ route('subscription.estate.index') }}" class="btn btn-default">Reset</a></div>
        </form>
    </div>
</div>
<div class="box box-primary">
    <div class="box-header with-border"><h3 class="box-title">Subscriptions</h3><div class="box-tools"><a href="{{ route('subscription.estate.create') }}" class="btn btn-primary btn-sm"><i class="fa fa-plus"></i> Add Subscription</a></div></div>
    <div class="box-body table-responsive">
        <table class="table table-bordered table-striped" id="estate_subscriptions_table">
            <thead><tr><th>Tenant DB</th><th>Tenant UID</th><th>Business</th><th>Registered On</th><th>Period Days</th><th class="text-right">Amount</th><th>Expiry Date</th><th>Mobile Numbers</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>@foreach($subscriptions as $row)<tr>
                <td>{{ $row->tenant_database }}</td><td>{{ $row->tenant_id }}</td><td>{{ $row->business_name }}</td><td>{{ $row->business_registered_on ? \Carbon\Carbon::parse($row->business_registered_on)->format('Y-m-d') : '' }}</td><td>{{ $row->subscription_period_days }}</td><td class="text-right">{{ number_format($row->subscription_amount, 4) }}</td><td>{{ $row->expiry_date ? \Carbon\Carbon::parse($row->expiry_date)->format('Y-m-d') : '' }}</td><td>{{ $row->business_mobile_numbers }}</td><td>{{ $row->status ? 'Active' : 'Inactive' }}</td>
                <td><a class="btn btn-xs btn-primary" href="{{ route('subscription.estate.edit',$row->id) }}"><i class="fa fa-edit"></i> Edit</a>
                <form method="post" action="{{ route('subscription.estate.destroy',$row->id) }}" style="display:inline" onsubmit="return confirm('Delete this subscription record?')">@csrf @method('DELETE')<button class="btn btn-xs btn-danger"><i class="fa fa-trash"></i> Delete</button></form></td>
            </tr>@endforeach</tbody>
        </table>
        {{ $subscriptions->links() }}
    </div>
</div>
</section>
@endsection
@section('javascript')
<script>$(function(){ $('.select2').select2({width:'100%'}); if($.fn.DataTable){ $('#estate_subscriptions_table').DataTable({paging:false,searching:true,ordering:true,dom:'Bfrtip',buttons:['csv','excel','pdf','print','colvis']}); } });</script>
@endsection
