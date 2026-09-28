@extends('bankinginsurance::layouts.app', ['subtitle' => 'Claim Details'])
@section('bankinginsurance_content')
<div class="box box-warning"><div class="box-header"><h3 class="box-title">{{ $claim->claim_no }}</h3></div><div class="box-body"><table class="table table-bordered"><tr><th>Policy</th><td>{{ optional($claim->policy)->policy_no }}</td></tr><tr><th>Claim Date</th><td>{{ optional($claim->claim_date)->format('Y-m-d') }}</td></tr><tr><th>Claim Amount</th><td>{{ number_format($claim->claim_amount,4) }}</td></tr><tr><th>Approved Amount</th><td>{{ number_format($claim->approved_amount,4) }}</td></tr><tr><th>Status</th><td>{{ ucfirst(str_replace('_',' ',$claim->status)) }}</td></tr><tr><th>Reason</th><td>{{ $claim->reason }}</td></tr></table>
@if(!in_array($claim->status,['settled','rejected']))<form method="post" action="{{ route('banking-insurance.claims.approve',$claim) }}" class="form-inline">@csrf <input name="approved_amount" class="form-control input_number" placeholder="Approved Amount"> <button class="btn btn-success">Approve</button></form>@endif
</div></div>
@endsection
