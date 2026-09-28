@extends('layouts.app')
@section('title', 'Pre-Operative Checklist')
@section('content')
<section class="content-header"><h1>Pre-Operative Checklist</h1></section>
<section class="content"><form method="POST" action="{{ route('myhealth.operation_theatre.checklists.store') }}">@csrf<div class="box box-primary"><div class="box-body"><div class="row">
<div class="col-md-4 form-group"><label>Surgery *</label><select name="surgery_schedule_id" class="form-control" required><option value="">Select</option>@foreach($schedules as $s)<option value="{{ $s->id }}">{{ $s->surgery_no }} - {{ $s->procedure_name }}</option>@endforeach</select></div>
<div class="col-md-3 form-group"><label>Member ID *</label><input type="number" name="member_id" class="form-control" required></div>
</div><hr><div class="row">
@foreach(['consent_verified'=>'Consent Verified','identity_verified'=>'Identity Verified','procedure_site_marked'=>'Procedure Site Marked','allergy_checked'=>'Allergy Checked','investigations_completed'=>'Investigations Completed','blood_available'=>'Blood Available','anaesthesia_clearance'=>'Anaesthesia Clearance','fasting_confirmed'=>'Fasting Confirmed','equipment_ready'=>'Equipment Ready','implant_available'=>'Implant Available','antibiotic_given'=>'Antibiotic Given'] as $name=>$label)
<div class="col-md-3"><label><input type="checkbox" name="{{ $name }}" value="1"> {{ $label }}</label></div>
@endforeach
</div><div class="form-group"><label>Remarks</label><textarea name="remarks" class="form-control"></textarea></div></div><div class="box-footer"><button class="btn btn-primary">Save Checklist</button></div></div></form></section>
@endsection
