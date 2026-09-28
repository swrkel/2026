@extends('tailoring::layouts.app')
@section('title','Warranty & Service')
@section('content')
@include('tailoring::partials.smart_toolbar', ['title'=>'Warranty & Service'])
<form method="post" action="{{ route('tailoring.warranty.store') }}">@csrf<div class="row"><div class="col-md-3"><input name="order_id" class="form-control" placeholder="Order ID"></div><div class="col-md-3"><input type="date" name="service_date" class="form-control" value="{{ date('Y-m-d') }}"></div><div class="col-md-3"><select name="service_type" class="form-control"><option>Free Alteration</option><option>Paid Alteration</option><option>Repair</option><option>Complaint</option></select></div><div class="col-md-3"><button class="btn btn-primary">Save</button></div></div></form>
@endsection
