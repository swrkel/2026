@extends('layouts.app')
@section('title', 'Record Vaccination')
@section('content')
<section class="content-header"><h1>Record Vaccination</h1></section>
<section class="content"><form method="POST" action="{{ route('myhealth.vaccination.records.store') }}">@csrf
<div class="box box-primary"><div class="box-body"><div class="row">
<div class="col-md-3 form-group"><label>Member ID *</label><input type="number" name="member_id" class="form-control" required></div>
<div class="col-md-3 form-group"><label>Vaccine *</label><select name="vaccine_id" class="form-control" required><option value="">Select</option>@foreach($vaccines as $v)<option value="{{ $v->id }}">{{ $v->vaccine_name }}</option>@endforeach</select></div>
<div class="col-md-2 form-group"><label>Dose No</label><input type="number" min="1" name="dose_no" class="form-control"></div>
<div class="col-md-2 form-group"><label>Date Given *</label><input type="date" name="date_given" class="form-control" value="{{ date('Y-m-d') }}" required></div>
<div class="col-md-2 form-group"><label>Next Due Date</label><input type="date" name="next_due_date" class="form-control"></div>
<div class="col-md-3 form-group"><label>Batch ID</label><input type="number" name="batch_id" class="form-control"></div>
<div class="col-md-3 form-group"><label>Administered By</label><input name="administered_by" class="form-control"></div>
<div class="col-md-3 form-group"><label>Location</label><input name="administered_location" class="form-control"></div>
<div class="col-md-3 form-group"><label>Adverse Reaction</label><input name="adverse_reaction" class="form-control"></div>
<div class="col-md-12 form-group"><label>Reaction Notes</label><textarea name="reaction_notes" class="form-control"></textarea></div>
<div class="col-md-12 form-group"><label>Remarks</label><textarea name="remarks" class="form-control"></textarea></div>
</div></div><div class="box-footer"><button class="btn btn-primary">Save</button> <a href="{{ route('myhealth.vaccination.records.index') }}" class="btn btn-default">Cancel</a></div></div>
</form></section>
@endsection
