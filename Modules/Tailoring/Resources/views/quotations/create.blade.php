@extends('tailoring::layouts.app')
@section('title','Add Quotation')
@section('content')
@include('tailoring::partials.smart_toolbar', ['title'=>'Add Quotation'])
<form method="post" action="{{ route('tailoring.quotations.store') }}">@csrf<div class="row"><div class="col-md-4"><label>Customer ID</label><input name="customer_id" class="form-control"></div><div class="col-md-4"><label>Quotation Date</label><input type="date" name="quotation_date" class="form-control" value="{{ date('Y-m-d') }}"></div><div class="col-md-4"><label>Valid Until</label><input type="date" name="valid_until" class="form-control"></div></div><div class="form-group mt-3"><label>Notes</label><textarea name="notes" class="form-control"></textarea></div><button class="btn btn-primary">Save</button></form>
@endsection
