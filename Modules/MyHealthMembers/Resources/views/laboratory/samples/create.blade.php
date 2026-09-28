@extends('layouts.app')
@section('title', 'Collect Laboratory Sample')
@section('content')
<section class="content-header"><h1>Collect Laboratory Sample</h1></section>
<section class="content"><form method="POST" action="{{ route('myhealth.laboratory.samples.store') }}">@csrf
<div class="box box-primary"><div class="box-body"><div class="row">
<div class="col-md-3"><div class="form-group"><label>Member ID</label><input name="member_id" type="number" class="form-control" required></div></div>
<div class="col-md-3"><div class="form-group"><label>Consultation ID</label><input name="consultation_id" type="number" class="form-control"></div></div>
<div class="col-md-3"><div class="form-group"><label>Lab Request ID</label><input name="lab_request_id" type="number" class="form-control"></div></div>
<div class="col-md-3"><div class="form-group"><label>Sample Type</label><input name="sample_type" class="form-control" required></div></div>
<div class="col-md-3"><div class="form-group"><label>Priority</label><select name="priority" class="form-control"><option value="routine">Routine</option><option value="urgent">Urgent</option><option value="stat">STAT</option></select></div></div>
<div class="col-md-3"><div class="form-group"><label>Collected At</label><input name="collected_at" type="datetime-local" class="form-control"></div></div>
<div class="col-md-6"><div class="form-group"><label>Remarks</label><input name="remarks" class="form-control"></div></div>
</div></div><div class="box-footer"><button class="btn btn-primary">Save Sample</button><a href="{{ route('myhealth.laboratory.samples.index') }}" class="btn btn-default">Back</a></div></div>
</form></section>
@endsection
