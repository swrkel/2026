@extends('layouts.app')
@section('title', 'Enter Radiology Report')
@section('content')
<section class="content-header"><h1>Enter Radiology Report</h1></section>
<section class="content"><form method="POST" action="{{ route('myhealth.radiology.reports.store') }}">@csrf
<div class="box box-primary"><div class="box-body"><div class="row">
<div class="col-md-3"><div class="form-group"><label>Report No</label><input name="report_no" class="form-control" value="{{ $nextReportNo }}" readonly></div></div>
<div class="col-md-6"><div class="form-group"><label>Radiology Request *</label><select name="radiology_request_id" class="form-control" required>@foreach($requests as $request)<option value="{{ $request->id }}">{{ $request->request_no }} - {{ $request->member_id }} - {{ $request->modality }} {{ $request->study_type }}</option>@endforeach</select></div></div>
<div class="col-md-3"><div class="form-group"><label>Status</label><select name="status" class="form-control"><option value="reported">Reported</option><option value="verified">Verified</option><option value="approved">Approved</option><option value="released">Released</option></select></div></div>
<div class="col-md-12"><div class="form-group"><label>Findings *</label><textarea name="findings" class="form-control" rows="5" required></textarea></div></div>
<div class="col-md-12"><div class="form-group"><label>Impression</label><textarea name="impression" class="form-control" rows="3"></textarea></div></div>
<div class="col-md-12"><div class="form-group"><label>Recommendations</label><textarea name="recommendations" class="form-control" rows="3"></textarea></div></div>
<div class="col-md-3"><div class="form-group"><label><input type="checkbox" name="critical_finding" value="1"> Critical Finding</label></div></div>
<div class="col-md-9"><div class="form-group"><label>Critical Notes</label><input name="critical_notes" class="form-control"></div></div>
</div></div><div class="box-footer"><button class="btn btn-primary">Save Report</button><a href="{{ route('myhealth.radiology.reports.index') }}" class="btn btn-default">Back</a></div></div>
</form></section>
@endsection
