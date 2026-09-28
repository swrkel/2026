@extends('distributionnew::layouts.app')
@section('content')
<div class="pos-card disnew-page"><div class="pos-card-header"><h4>Return Details</h4></div><div class="pos-card-body">
<div class="row"><div class="col-md-3"><strong>No:</strong> {{ $return->return_no }}</div><div class="col-md-3"><strong>Status:</strong> {{ $return->status }}</div><div class="col-md-3"><strong>Total:</strong> {{ number_format($return->total_amount, 4) }}</div></div>
<form method="POST" action="{{ route('distribution-new.returns.approve', $return) }}" class="d-inline">@csrf<button class="btn btn-success mt-3">Approve & Update Stock</button></form>
<form method="POST" action="{{ route('distribution-new.returns.credit-note', $return) }}" class="d-inline">@csrf<button class="btn btn-warning mt-3">Create Credit Note</button></form></div></div>
@endsection
