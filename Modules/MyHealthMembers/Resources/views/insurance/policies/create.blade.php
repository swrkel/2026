@extends('layouts.app')
@section('title', 'Add Insurance Policy')
@section('content')
<section class="content-header"><h1>Add Insurance Policy</h1></section>
<section class="content"><div class="box box-primary"><div class="box-body">
<form method="POST" action="{{ route('myhealth.insurance.policies.store') }}">@csrf
<div class="row">
<div class="col-md-4 form-group"><label>Member *</label><select name="member_id" class="form-control" required><option value="">Select</option>@foreach($members as $member)<option value="{{ $member->id }}">{{ $member->myhealth_code }} - {{ $member->name }} - {{ $member->mobile }}</option>@endforeach</select></div>
<div class="col-md-4 form-group"><label>Insurance Company *</label><select name="insurance_company_id" class="form-control" required><option value="">Select</option>@foreach($companies as $company)<option value="{{ $company->id }}">{{ $company->company_code }} - {{ $company->company_name }}</option>@endforeach</select></div>
<div class="col-md-4 form-group"><label>Policy Type</label><input name="policy_type" class="form-control" placeholder="Health / Family / Corporate"></div>
<div class="col-md-4 form-group"><label>Start Date</label><input type="date" name="start_date" class="form-control" value="{{ date('Y-m-d') }}"></div>
<div class="col-md-4 form-group"><label>End Date</label><input type="date" name="end_date" class="form-control"></div>
<div class="col-md-4 form-group"><label>Coverage Amount</label><input type="number" step="0.0001" name="coverage_amount" class="form-control" value="0"></div>
<div class="col-md-12 form-group"><label>Notes</label><textarea name="notes" class="form-control"></textarea></div>
</div>
<button class="btn btn-primary">Save Policy</button> <a href="{{ route('myhealth.insurance.policies.index') }}" class="btn btn-default">Cancel</a>
</form></div></div></section>
@endsection
