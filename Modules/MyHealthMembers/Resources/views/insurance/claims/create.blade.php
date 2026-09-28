@extends('layouts.app')
@section('title', 'New Insurance Claim')
@section('content')
<section class="content-header"><h1>New Insurance Claim</h1></section>
<section class="content"><div class="box box-primary"><div class="box-body">
<form method="POST" action="{{ route('myhealth.insurance.claims.store') }}">@csrf
<div class="row">
<div class="col-md-8 form-group"><label>Policy *</label><select name="policy_id" class="form-control" required><option value="">Select</option>@foreach($policies as $policy)<option value="{{ $policy->id }}">{{ $policy->policy_no }} - {{ optional($policy->member)->myhealth_code }} - {{ optional($policy->member)->name }} / {{ optional($policy->company)->company_name }} / Balance {{ number_format($policy->available_balance, 4) }}</option>@endforeach</select></div>
<div class="col-md-4 form-group"><label>Claim Date</label><input type="date" name="claim_date" class="form-control" value="{{ date('Y-m-d') }}"></div>
</div>
<div class="table-responsive"><table class="table table-bordered"><thead><tr><th>Type</th><th>Description</th><th>Service Date</th><th>Amount</th></tr></thead><tbody>
@for($i=0; $i<5; $i++)<tr><td><input name="items[{{ $i }}][item_type]" class="form-control" placeholder="Consultation/Lab/Pharmacy"></td><td><input name="items[{{ $i }}][description]" class="form-control"></td><td><input type="date" name="items[{{ $i }}][service_date]" class="form-control"></td><td><input type="number" step="0.0001" name="items[{{ $i }}][amount]" class="form-control"></td></tr>@endfor
</tbody></table></div>
<div class="form-group"><label>Remarks</label><textarea name="remarks" class="form-control"></textarea></div>
<button class="btn btn-primary">Submit Claim</button> <a href="{{ route('myhealth.insurance.claims.index') }}" class="btn btn-default">Cancel</a>
</form></div></div></section>
@endsection
