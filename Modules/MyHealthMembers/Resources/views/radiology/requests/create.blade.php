@extends('layouts.app')
@section('title', 'Add Radiology Request')
@section('content')
<section class="content-header"><h1>Add Radiology Request</h1></section>
<section class="content"><form method="POST" action="{{ route('myhealth.radiology.requests.store') }}">@csrf
<div class="box box-primary"><div class="box-body"><div class="row">
<div class="col-md-3"><div class="form-group"><label>Request No</label><input name="request_no" class="form-control" value="{{ $nextRequestNo }}" readonly></div></div>
<div class="col-md-3"><div class="form-group"><label>Member ID *</label><input name="member_id" type="number" class="form-control" required></div></div>
<div class="col-md-3"><div class="form-group"><label>Consultation ID</label><input name="consultation_id" type="number" class="form-control"></div></div>
<div class="col-md-3"><div class="form-group"><label>Appointment ID</label><input name="appointment_id" type="number" class="form-control"></div></div>
<div class="col-md-3"><div class="form-group"><label>Modality *</label><select name="modality" class="form-control" required>@foreach(['X-Ray','CT','MRI','Ultrasound','ECG','Echo','Mammography','Other'] as $m)<option value="{{ $m }}">{{ $m }}</option>@endforeach</select></div></div>
<div class="col-md-3"><div class="form-group"><label>Study Type *</label><input name="study_type" class="form-control" placeholder="e.g. Chest PA" required></div></div>
<div class="col-md-3"><div class="form-group"><label>Body Part</label><input name="body_part" class="form-control" placeholder="e.g. Chest"></div></div>
<div class="col-md-3"><div class="form-group"><label>Priority</label><select name="priority" class="form-control"><option value="routine">Routine</option><option value="urgent">Urgent</option><option value="stat">STAT</option></select></div></div>
<div class="col-md-3"><div class="form-group"><label>Scheduled At</label><input name="scheduled_at" type="datetime-local" class="form-control"></div></div>
<div class="col-md-3"><div class="form-group"><label>Equipment</label><input name="equipment_name" class="form-control"></div></div>
<div class="col-md-3"><div class="form-group"><label>Room No</label><input name="room_no" class="form-control"></div></div>
<div class="col-md-12"><div class="form-group"><label>Clinical Notes</label><textarea name="clinical_notes" class="form-control" rows="3"></textarea></div></div>
<div class="col-md-12"><div class="form-group"><label>Remarks</label><textarea name="remarks" class="form-control" rows="2"></textarea></div></div>
</div></div><div class="box-footer"><button class="btn btn-primary">Save Request</button><a href="{{ route('myhealth.radiology.requests.index') }}" class="btn btn-default">Back</a></div></div>
</form></section>
@endsection
