@extends('layouts.app')
@section('title', 'Member Insurance Policies')
@section('content')
<section class="content-header"><h1>Member Insurance Policies</h1></section>
<section class="content">
@if(session('status')) <div class="alert alert-success">{{ session('status') }}</div> @endif
<form method="GET" class="mb-3"><div class="input-group"><input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Search policy/member"><span class="input-group-btn"><button class="btn btn-primary">Search</button></span></div></form>
<a href="{{ route('myhealth.insurance.policies.create') }}" class="btn btn-success mb-3">Add Policy</a> <a href="{{ route('myhealth.insurance.dashboard') }}" class="btn btn-default mb-3">Dashboard</a>
<div class="table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Policy No</th><th>Member</th><th>Company</th><th>Type</th><th>Period</th><th>Coverage</th><th>Balance</th><th>Status</th></tr></thead><tbody>
@foreach($policies as $policy)<tr><td>{{ $policy->policy_no }}</td><td>{{ optional($policy->member)->myhealth_code }} - {{ optional($policy->member)->name }}</td><td>{{ optional($policy->company)->company_name }}</td><td>{{ $policy->policy_type }}</td><td>{{ $policy->start_date }} to {{ $policy->end_date }}</td><td>{{ number_format($policy->coverage_amount, 4) }}</td><td>{{ number_format($policy->available_balance, 4) }}</td><td>{{ ucfirst($policy->status) }}</td></tr>@endforeach
</tbody></table></div>{{ $policies->links() }}
</section>
@endsection
