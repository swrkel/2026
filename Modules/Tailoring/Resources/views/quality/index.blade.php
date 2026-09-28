@extends('tailoring::layouts.app')
@section('title','Quality Control')
@section('content')
@include('tailoring::partials.smart_toolbar', ['title'=>'Quality Control'])
<form method="post" action="{{ route('tailoring.quality.store') }}">@csrf<div class="row"><div class="col-md-3"><input name="job_card_id" class="form-control" placeholder="Job Card ID"></div><div class="col-md-3"><select name="status" class="form-control"><option value="pending">Pending</option><option value="approved">Approved</option><option value="rework">Rework</option></select></div><div class="col-md-6"><input name="remarks" class="form-control" placeholder="Remarks"></div></div><button class="btn btn-primary mt-2">Save QC</button></form>
@endsection
