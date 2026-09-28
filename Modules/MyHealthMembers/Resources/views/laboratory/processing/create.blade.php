@extends('layouts.app')
@section('title', 'Enter Laboratory Result')
@section('content')
<section class="content-header"><h1>Enter Laboratory Result</h1></section>
<section class="content"><form method="POST" action="{{ route('myhealth.laboratory.processing.store') }}">@csrf
<div class="box box-primary"><div class="box-body"><div class="row">
<div class="col-md-3"><div class="form-group"><label>Sample</label><select name="sample_id" class="form-control" required>@foreach($samples as $sample)<option value="{{ $sample->id }}">{{ $sample->sample_no }} - {{ $sample->sample_type }}</option>@endforeach</select></div></div>
<div class="col-md-3"><div class="form-group"><label>Test ID</label><input name="test_id" type="number" class="form-control"></div></div>
<div class="col-md-3"><div class="form-group"><label>Member ID</label><input name="member_id" type="number" class="form-control"></div></div>
<div class="col-md-3"><div class="form-group"><label>Result Value</label><input name="result_value" class="form-control" required></div></div>
<div class="col-md-3"><div class="form-group"><label>Unit</label><input name="unit" class="form-control"></div></div>
<div class="col-md-3"><div class="form-group"><label>Reference Range</label><input name="reference_range" class="form-control"></div></div>
<div class="col-md-3"><div class="form-group"><label>Status</label><select name="status" class="form-control"><option value="entered">Entered</option><option value="verified">Verified</option><option value="approved">Approved</option><option value="released">Released</option></select></div></div>
<div class="col-md-3"><div class="form-group"><label>Flags</label><br><label><input type="checkbox" name="is_abnormal" value="1"> Abnormal</label> &nbsp; <label><input type="checkbox" name="is_critical" value="1"> Critical</label></div></div>
<div class="col-md-6"><div class="form-group"><label>Interpretation</label><textarea name="interpretation" class="form-control" rows="3"></textarea></div></div>
<div class="col-md-6"><div class="form-group"><label>Technician Comments</label><textarea name="technician_comments" class="form-control" rows="3"></textarea></div></div>
</div></div><div class="box-footer"><button class="btn btn-primary">Save Result</button><a href="{{ route('myhealth.laboratory.processing.index') }}" class="btn btn-default">Back</a></div></div>
</form></section>
@endsection
