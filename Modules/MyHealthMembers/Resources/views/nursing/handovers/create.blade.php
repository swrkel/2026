@extends('layouts.app')
@section('title', $title ?? 'My Health Nursing')
@section('content')
<section class="content-header"><h1>{{ $title ?? 'My Health Nursing' }}</h1></section>
<section class="content">
@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif

@php($title = 'Shift Handover')
<form method="POST" action="{{ route('myhealth.nursing.handovers.store') }}">@csrf<div class="box box-primary"><div class="box-body"><div class="form-group"><label>Member *</label><select name="member_id" class="form-control" required><option value="">Select member</option>@foreach($members as $member)<option value="{{ $member->id }}">{{ $member->member_code ?? '' }} - {{ $member->name }}</option>@endforeach</select></div>
<div class="row"><div class="col-md-6"><div class="form-group"><label>From Shift *</label><select name="from_shift" class="form-control" required><option>Morning</option><option>Evening</option><option>Night</option></select></div></div><div class="col-md-6"><div class="form-group"><label>To Shift *</label><select name="to_shift" class="form-control" required><option>Morning</option><option>Evening</option><option>Night</option></select></div></div></div>
@foreach(['patient_summary'=>'Patient Summary','outstanding_tasks'=>'Outstanding Tasks','critical_alerts'=>'Critical Alerts','pending_investigations'=>'Pending Investigations','pending_medications'=>'Pending Medications'] as $name=>$label)<div class="form-group"><label>{ $label }</label><textarea name="{ $name }" class="form-control" rows="2"></textarea></div>@endforeach
</div><div class="box-footer"><button class="btn btn-primary">Save Handover</button></div></div></form></section>
@endsection
