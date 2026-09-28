@extends('layouts.app')
@section('title', 'Post-Operative Note')
@section('content')
<section class="content-header"><h1>Post-Operative Note</h1></section>
<section class="content"><form method="POST" action="{{ route('myhealth.operation_theatre.post_op.store') }}">@csrf<div class="box box-primary"><div class="box-body"><div class="row">
<div class="col-md-4 form-group"><label>Surgery *</label><select name="surgery_schedule_id" class="form-control" required><option value="">Select</option>@foreach($schedules as $s)<option value="{{ $s->id }}">{{ $s->surgery_no }} - {{ $s->procedure_name }}</option>@endforeach</select></div>
<div class="col-md-2 form-group"><label>Member ID *</label><input type="number" name="member_id" class="form-control" required></div>
<div class="col-md-3 form-group"><label>Operative Record</label><select name="operative_record_id" class="form-control"><option value="">Select</option>@foreach($records as $r)<option value="{{ $r->id }}">{{ $r->operation_no }}</option>@endforeach</select></div>
<div class="col-md-3 form-group"><label>Recovery Status</label><input type="text" name="recovery_status" class="form-control" value="stable"></div>
<div class="col-md-2 form-group"><label>Pain Score</label><input type="number" min="0" max="10" name="pain_score" class="form-control"></div>
<div class="col-md-4 form-group"><label>Vital Status</label><input type="text" name="vital_status" class="form-control"></div>
<div class="col-md-3"><label><input type="checkbox" name="icu_transfer_required" value="1"> ICU Transfer Required</label></div>
<div class="col-md-3"><label><input type="checkbox" name="ward_transfer_required" value="1"> Ward Transfer Required</label></div>
<div class="col-md-6 form-group"><label>Post-Op Instructions</label><textarea name="post_op_instructions" class="form-control" rows="3"></textarea></div>
<div class="col-md-6 form-group"><label>Medications</label><textarea name="medications" class="form-control" rows="3"></textarea></div>
<div class="col-md-6 form-group"><label>Follow-Up Plan</label><textarea name="follow_up_plan" class="form-control" rows="3"></textarea></div>
<div class="col-md-6 form-group"><label>Discharge Recommendations</label><textarea name="discharge_recommendations" class="form-control" rows="3"></textarea></div>
</div></div><div class="box-footer"><button class="btn btn-primary">Save Post-Op Note</button></div></div></form></section>
@endsection
