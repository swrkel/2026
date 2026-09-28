@extends('distributionnew::layouts.app')
@section('content')
@include('distributionnew::partials.erp-standard-styles')
<div class="pos-card">
    <div class="pos-card-header"><h4>@lang('distributionnew::messages.sales_reps')</h4></div>
    <form method="POST" action="{{ route('distributionnew.sales-reps.store') }}" class="row">@csrf
        <div class="col-md-2"><input name="sales_rep_code" class="form-control" placeholder="Rep Code" required></div>
        <div class="col-md-3"><input name="sales_rep_name" class="form-control" placeholder="Sales Rep Name" required></div>
        <div class="col-md-3"><input name="mobile" class="form-control" placeholder="Mobile"></div>
        <div class="col-md-2"><input name="user_id" class="form-control" placeholder="User ID"></div>
        <div class="col-md-2"><button class="btn btn-primary btn-block">Save</button></div>
    </form>
    <hr>
    <table class="table table-bordered table-striped disnew-table"><thead><tr><th>Code</th><th>Name</th><th>Mobile</th><th>User</th><th>Status</th></tr></thead><tbody>
    @foreach($salesReps as $rep)<tr><td>{{ $rep->sales_rep_code }}</td><td>{{ $rep->sales_rep_name }}</td><td>{{ $rep->mobile }}</td><td>{{ $rep->user_id }}</td><td>{{ $rep->is_active ? 'Active' : 'Inactive' }}</td></tr>@endforeach
    </tbody></table>
</div>
@endsection
