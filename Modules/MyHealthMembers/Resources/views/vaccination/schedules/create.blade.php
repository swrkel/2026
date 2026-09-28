@extends('layouts.app')
@section('title', 'Add Immunization Schedule')
@section('content')
<section class="content-header"><h1>Add Immunization Schedule</h1></section>
<section class="content"><form method="POST" action="{{ route('myhealth.vaccination.schedules.store') }}">@csrf
<div class="box box-primary"><div class="box-body"><div class="row">
<div class="col-md-3 form-group"><label>Schedule Name *</label><input name="schedule_name" class="form-control" required></div>
<div class="col-md-3 form-group"><label>Schedule Type *</label><select name="schedule_type" class="form-control" required><option>Childhood</option><option>Adult</option><option>Pregnancy</option><option>Occupational</option><option>Travel</option></select></div>
<div class="col-md-3 form-group"><label>Vaccine *</label><select name="vaccine_id" class="form-control" required>@foreach($vaccines as $v)<option value="{{ $v->id }}">{{ $v->vaccine_name }}</option>@endforeach</select></div>
<div class="col-md-3 form-group"><label>Dose No</label><input type="number" min="1" name="dose_no" class="form-control"></div>
<div class="col-md-3 form-group"><label>Recommended Age Days</label><input type="number" min="0" name="recommended_age_days" class="form-control"></div>
<div class="col-md-3 form-group"><label>Recommended Age Text</label><input name="recommended_age_text" class="form-control" placeholder="e.g. At birth / 6 weeks"></div>
<div class="col-md-3 form-group"><label>Interval Days</label><input type="number" min="0" name="interval_days" class="form-control"></div>
<div class="col-md-12 form-group"><label><input type="checkbox" name="is_mandatory" value="1"> Mandatory</label></div>
<div class="col-md-12 form-group"><label>Notes</label><textarea name="notes" class="form-control"></textarea></div>
</div></div><div class="box-footer"><button class="btn btn-primary">Save</button> <a href="{{ route('myhealth.vaccination.schedules.index') }}" class="btn btn-default">Cancel</a></div></div>
</form></section>
@endsection
