@extends('layouts.app')
@section('title', 'Operative Record')
@section('content')
<section class="content-header"><h1>Operative Record</h1></section>
<section class="content"><form method="POST" action="{{ route('myhealth.operation_theatre.records.store') }}">@csrf<div class="box box-primary"><div class="box-body"><div class="row">
<div class="col-md-4 form-group"><label>Surgery *</label><select name="surgery_schedule_id" class="form-control" required><option value="">Select</option>@foreach($schedules as $s)<option value="{{ $s->id }}">{{ $s->surgery_no }} - {{ $s->procedure_name }}</option>@endforeach</select></div>
<div class="col-md-2 form-group"><label>Member ID *</label><input type="number" name="member_id" class="form-control" required></div>
<div class="col-md-6 form-group"><label>Procedure Performed *</label><input type="text" name="procedure_performed" class="form-control" required></div>
<div class="col-md-3 form-group"><label>Anaesthesia Type</label><input type="text" name="anaesthesia_type" class="form-control"></div>
<div class="col-md-3 form-group"><label>Incision Time</label><input type="datetime-local" name="incision_time" class="form-control"></div>
<div class="col-md-3 form-group"><label>Closure Time</label><input type="datetime-local" name="closure_time" class="form-control"></div>
<div class="col-md-3 form-group"><label>Blood Loss ML</label><input type="number" step="0.01" name="blood_loss_ml" class="form-control"></div>
<div class="col-md-3 form-group"><label>Surgeon ID</label><input type="number" name="surgeon_id" class="form-control"></div>
<div class="col-md-3 form-group"><label>Anaesthetist ID</label><input type="number" name="anaesthetist_id" class="form-control"></div>
<div class="col-md-3 form-group"><label>Scrub Nurse ID</label><input type="number" name="scrub_nurse_id" class="form-control"></div>
<div class="col-md-3 form-group"><label>Circulating Nurse ID</label><input type="number" name="circulating_nurse_id" class="form-control"></div>
<div class="col-md-6 form-group"><label>Findings</label><textarea name="findings" class="form-control" rows="3"></textarea></div>
<div class="col-md-6 form-group"><label>Procedure Notes</label><textarea name="procedure_notes" class="form-control" rows="3"></textarea></div>
<div class="col-md-6 form-group"><label>Implants Used</label><textarea name="implants_used" class="form-control" rows="2"></textarea></div>
<div class="col-md-6 form-group"><label>Consumables Used</label><textarea name="consumables_used" class="form-control" rows="2"></textarea></div>
<div class="col-md-12 form-group"><label>Complications</label><textarea name="complications" class="form-control" rows="2"></textarea></div>
<div class="col-md-3"><label><input type="checkbox" name="blood_transfusion" value="1"> Blood Transfusion</label></div>
<div class="col-md-3"><label><input type="checkbox" name="specimen_sent" value="1"> Specimen Sent</label></div>
</div></div><div class="box-footer"><button class="btn btn-primary">Save Operative Record</button></div></div></form></section>
@endsection
