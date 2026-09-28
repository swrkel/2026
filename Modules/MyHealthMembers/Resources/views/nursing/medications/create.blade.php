@extends('layouts.app')
@section('title', $title ?? 'My Health Nursing')
@section('content')
<section class="content-header"><h1>{{ $title ?? 'My Health Nursing' }}</h1></section>
<section class="content">
@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif

@php($title = 'Medication Administration Record')
<form method="POST" action="{{ route('myhealth.nursing.medications.store') }}">@csrf<div class="box box-primary"><div class="box-body"><div class="form-group"><label>Member *</label><select name="member_id" class="form-control" required><option value="">Select member</option>@foreach($members as $member)<option value="{{ $member->id }}">{{ $member->member_code ?? '' }} - {{ $member->name }}</option>@endforeach</select></div>
<div class="row"><div class="col-md-4"><div class="form-group"><label>Medicine *</label><input name="medicine_name" class="form-control" required></div></div><div class="col-md-2"><div class="form-group"><label>Dose</label><input name="dose" class="form-control"></div></div><div class="col-md-2"><div class="form-group"><label>Route</label><input name="route" class="form-control"></div></div><div class="col-md-2"><div class="form-group"><label>Time Due</label><input type="datetime-local" name="time_due" class="form-control"></div></div><div class="col-md-2"><div class="form-group"><label>Status</label><select name="status" class="form-control"><option value="due">Due</option><option value="given">Given</option><option value="missed">Missed</option><option value="held">Held</option></select></div></div></div>
<div class="form-group"><label>Missed Reason / Adverse Reaction / Remarks</label><textarea name="remarks" class="form-control" rows="3"></textarea></div>
</div><div class="box-footer"><button class="btn btn-primary">Save MAR</button></div></div></form></section>
@endsection
