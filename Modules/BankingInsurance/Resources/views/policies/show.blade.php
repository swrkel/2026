@extends('bankinginsurance::layouts.app', ['subtitle' => 'Policy Details'])
@section('bankinginsurance_content')
<div class="box box-primary"><div class="box-header"><h3 class="box-title">{{ $policy->policy_no }}</h3></div><div class="box-body">
<div class="row"><div class="col-md-6"><table class="table table-bordered"><tr><th>Customer</th><td>{{ $policy->customer_name }}</td></tr><tr><th>Mobile</th><td>{{ $policy->mobile }}</td></tr><tr><th>NIC</th><td>{{ $policy->nic_no }}</td></tr><tr><th>Status</th><td>{{ ucfirst($policy->status) }}</td></tr></table></div><div class="col-md-6"><table class="table table-bordered"><tr><th>Product</th><td>{{ optional($policy->product)->name }}</td></tr><tr><th>Sum Assured</th><td>{{ number_format($policy->sum_assured,4) }}</td></tr><tr><th>Premium</th><td>{{ number_format($policy->premium_amount,4) }}</td></tr><tr><th>Paid Premium</th><td>{{ number_format($policy->paid_premium,4) }}</td></tr></table></div></div>
</div></div>
@endsection
