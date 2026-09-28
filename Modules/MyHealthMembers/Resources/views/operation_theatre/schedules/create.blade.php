@extends('layouts.app')
@section('title', 'Schedule Surgery')
@section('content')
<section class="content-header"><h1>Schedule Surgery</h1></section>
<section class="content"><form method="POST" action="{{ route('myhealth.operation_theatre.schedules.store') }}">@csrf<div class="box box-primary"><div class="box-body"><div class="row">
<div class="col-md-3 form-group"><label>Member ID *</label><input type="number" name="member_id" class="form-control" required></div>
<div class="col-md-3 form-group"><label>Consultation ID</label><input type="number" name="consultation_id" class="form-control"></div>
<div class="col-md-6 form-group"><label>Procedure *</label><input type="text" name="procedure_name" class="form-control" required></div>
<div class="col-md-3 form-group"><label>Category</label><input type="text" name="procedure_category" class="form-control"></div>
<div class="col-md-3 form-group"><label>Priority</label><select name="priority" class="form-control">@foreach($priorities as $p)<option value="{{ $p }}">{{ ucfirst($p) }}</option>@endforeach</select></div>
<div class="col-md-3 form-group"><label>Theatre Room</label><select name="theatre_room_id" class="form-control"><option value="">Select</option>@foreach($rooms as $room)<option value="{{ $room->id }}">{{ $room->room_name }}</option>@endforeach</select></div>
<div class="col-md-3 form-group"><label>Estimated Minutes</label><input type="number" name="estimated_duration_minutes" class="form-control"></div>
<div class="col-md-3 form-group"><label>Surgeon ID</label><input type="number" name="surgeon_id" class="form-control"></div>
<div class="col-md-3 form-group"><label>Assistant Surgeon ID</label><input type="number" name="assistant_surgeon_id" class="form-control"></div>
<div class="col-md-3 form-group"><label>Anaesthetist ID</label><input type="number" name="anaesthetist_id" class="form-control"></div>
<div class="col-md-3 form-group"><label>Nurse In Charge ID</label><input type="number" name="nurse_in_charge_id" class="form-control"></div>
<div class="col-md-3 form-group"><label>Scheduled Start</label><input type="datetime-local" name="scheduled_start_at" class="form-control"></div>
<div class="col-md-3 form-group"><label>Scheduled End</label><input type="datetime-local" name="scheduled_end_at" class="form-control"></div>
<div class="col-md-6 form-group"><label>Diagnosis</label><textarea name="diagnosis" class="form-control" rows="2"></textarea></div>
<div class="col-md-6 form-group"><label>Clinical Notes</label><textarea name="clinical_notes" class="form-control" rows="2"></textarea></div>
<div class="col-md-6 form-group"><label>Special Instructions</label><textarea name="special_instructions" class="form-control" rows="2"></textarea></div>
</div></div><div class="box-footer"><button class="btn btn-primary">Save Surgery Schedule</button></div></div></form></section>
@endsection
