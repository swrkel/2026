@extends('distributionnew::layouts.app')
@section('content')
<div class="pos-card disnew-page"><div class="pos-card-header"><h4>Add Distribution Return</h4></div><div class="pos-card-body">
<form method="POST" action="{{ route('distribution-new.returns.store') }}">@csrf
<div class="row"><div class="col-md-3"><label>Return No</label><input name="return_no" class="form-control" required></div><div class="col-md-3"><label>Return Date</label><input type="date" name="return_date" class="form-control" value="{{ date('Y-m-d') }}" required></div><div class="col-md-3"><label>Invoice ID</label><input name="sales_invoice_id" class="form-control"></div><div class="col-md-3"><label>Vehicle ID</label><input name="vehicle_id" class="form-control"></div></div>
<div class="mt-3"><label>Reason</label><textarea name="reason" class="form-control"></textarea></div>
<button class="btn btn-primary mt-3">Save Return</button></form></div></div>
@endsection
