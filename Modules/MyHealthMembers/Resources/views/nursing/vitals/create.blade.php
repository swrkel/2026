@extends('layouts.app')
@section('title', $title ?? 'My Health Nursing')
@section('content')
<section class="content-header"><h1>{{ $title ?? 'My Health Nursing' }}</h1></section>
<section class="content">
@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif

@php($title = 'Record Vital Signs')
<form method="POST" action="{{ route('myhealth.nursing.vitals.store') }}">@csrf<div class="box box-primary"><div class="box-body">
<div class="form-group"><label>Member *</label><select name="member_id" class="form-control" required><option value="">Select member</option>@foreach($members as $member)<option value="{{ $member->id }}">{{ $member->member_code ?? '' }} - {{ $member->name }}</option>@endforeach</select></div>
<div class="row"><div class="col-md-3"><div class="form-group"><label>Temperature</label><input type="number" step="0.01" name="temperature" class="form-control"></div></div><div class="col-md-3"><div class="form-group"><label>Pulse</label><input type="number" name="pulse" class="form-control"></div></div><div class="col-md-3"><div class="form-group"><label>Respiration</label><input type="number" name="respiration" class="form-control"></div></div><div class="col-md-3"><div class="form-group"><label>SpO2</label><input type="number" name="spo2" min="0" max="100" class="form-control"></div></div></div>
<div class="row"><div class="col-md-3"><div class="form-group"><label>Systolic BP</label><input type="number" name="systolic_bp" class="form-control"></div></div><div class="col-md-3"><div class="form-group"><label>Diastolic BP</label><input type="number" name="diastolic_bp" class="form-control"></div></div><div class="col-md-3"><div class="form-group"><label>Height Feet</label><input type="number" name="height_feet" min="0" max="9" class="form-control"></div></div><div class="col-md-3"><div class="form-group"><label>Height Inches</label><input type="number" name="height_inches" min="0" max="11" class="form-control"></div></div></div>
<div class="row"><div class="col-md-4"><div class="form-group"><label>Weight</label><input type="number" step="0.01" name="weight" class="form-control"></div></div><div class="col-md-4"><div class="form-group"><label>Blood Sugar</label><input type="number" step="0.01" name="blood_sugar" class="form-control"></div></div><div class="col-md-4"><div class="form-group"><label>Pain Scale</label><input type="number" name="pain_scale" min="0" max="10" class="form-control"></div></div></div>
<div class="form-group"><label>Remarks</label><textarea name="remarks" class="form-control" rows="3"></textarea></div>
</div><div class="box-footer"><button type="submit" class="btn btn-primary">Save Vital Signs</button></div></div></form>
</section>
@endsection
