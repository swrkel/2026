@extends('layouts.app')
@section('title', 'Insurance Claims')
@section('content')
<section class="content-header"><h1>Insurance Claims</h1></section>
<section class="content">
@if(session('status')) <div class="alert alert-success">{{ session('status') }}</div> @endif
<form method="GET" class="mb-3"><div class="input-group"><input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Search claim/member"><span class="input-group-btn"><button class="btn btn-primary">Search</button></span></div></form>
<a href="{{ route('myhealth.insurance.claims.create') }}" class="btn btn-success mb-3">New Claim</a> <a href="{{ route('myhealth.insurance.dashboard') }}" class="btn btn-default mb-3">Dashboard</a>
<div class="table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Claim No</th><th>Date</th><th>Member</th><th>Policy</th><th>Company</th><th>Claim</th><th>Approved</th><th>Settled</th><th>Status</th><th>Action</th></tr></thead><tbody>
@foreach($claims as $claim)<tr><td>{{ $claim->claim_no }}</td><td>{{ $claim->claim_date }}</td><td>{{ optional($claim->member)->myhealth_code }} - {{ optional($claim->member)->name }}</td><td>{{ optional($claim->policy)->policy_no }}</td><td>{{ optional(optional($claim->policy)->company)->company_name }}</td><td>{{ number_format($claim->claim_amount, 4) }}</td><td>{{ number_format($claim->approved_amount, 4) }}</td><td>{{ number_format($claim->settled_amount, 4) }}</td><td>{{ ucfirst($claim->status) }}</td><td><a href="{{ route('myhealth.insurance.claims.show', $claim->id) }}" class="btn btn-xs btn-primary">View</a></td></tr>@endforeach
</tbody></table></div>{{ $claims->links() }}
</section>
@endsection
