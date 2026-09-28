@extends('layouts.app')
@section('title', 'Add Claim Settlement')
@section('content')
<section class="content-header"><h1>Add Claim Settlement</h1></section>
<section class="content"><form method="post" action="{{ route('myhealth.billing.claims.store') }}">@csrf
<div class="row"><div class="col-md-5"><label>Claim</label><select name="claim_id" class="form-control" required><option value="">Select</option>@foreach($claims as $claim)<option value="{{ $claim->id }}">{{ $claim->claim_no }} - {{ optional($claim->member)->name }} - {{ number_format($claim->claim_amount, 4) }}</option>@endforeach</select></div><div class="col-md-2"><label>Date</label><input type="date" name="settlement_date" value="{{ date('Y-m-d') }}" class="form-control"></div><div class="col-md-2"><label>Approved</label><input name="approved_amount" value="0.0000" class="form-control text-right"></div><div class="col-md-2"><label>Settled</label><input name="settled_amount" value="0.0000" class="form-control text-right"></div><div class="col-md-1"><label>Status</label><select name="status" class="form-control"><option value="settled">Settled</option><option value="approved">Approved</option><option value="rejected">Rejected</option></select></div></div><br><label>Remarks</label><textarea name="remarks" class="form-control"></textarea><br><button class="btn btn-primary">Save Settlement</button> <a href="{{ route('myhealth.billing.claims.index') }}" class="btn btn-default">Cancel</a>
</form></section>
@endsection
