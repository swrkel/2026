@extends('distributionnew::layouts.app')
@section('content')
<div class="pos-card disnew-page"><div class="pos-card-header"><h4>Credit Note Details</h4></div><div class="pos-card-body">
<div class="row"><div class="col-md-3"><strong>No:</strong> {{ $creditNote->credit_note_no }}</div><div class="col-md-3"><strong>Status:</strong> {{ $creditNote->status }}</div><div class="col-md-3"><strong>Total:</strong> {{ number_format($creditNote->total_amount, 4) }}</div></div>
<form method="POST" action="{{ route('distribution-new.credit-notes.approve', $creditNote) }}">@csrf<button class="btn btn-success mt-3">Approve Credit Note</button></form></div></div>
@endsection
