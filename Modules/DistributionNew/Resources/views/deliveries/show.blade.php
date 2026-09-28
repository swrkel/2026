@extends('distributionnew::layouts.app')
@section('content')
@include('distributionnew::partials.erp-standard-styles')
<div class="pos-card"><div class="pos-card-header"><h4>Delivery #{{ $delivery->id }}</h4></div>
<form method="POST" action="{{ route('distributionnew.deliveries.mark-delivered',$delivery->id) }}">@csrf
<div class="row"><div class="col-md-3"><input name="received_by" class="form-control" placeholder="Received By"></div><div class="col-md-3"><input name="receiver_mobile" class="form-control" placeholder="Receiver Mobile"></div><div class="col-md-6"><input name="proof_note" class="form-control" placeholder="Proof / Note"></div></div>
<br><button class="btn btn-success">Mark Delivered</button>
</form></div>
@endsection
