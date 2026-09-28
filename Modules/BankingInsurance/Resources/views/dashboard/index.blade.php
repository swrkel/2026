@extends('bankinginsurance::layouts.app', ['subtitle' => 'Insurance dashboard'])
@section('bankinginsurance_content')
<div class="row">
@foreach([
 ['Total Policies',$data['total_policies'],'fa-file-text'],['Active Policies',$data['active_policies'],'fa-check-circle'],['Premium Collected',number_format($data['premium_collected'],4),'fa-money'],['Pending Claims',$data['pending_claims'],'fa-clock-o']
] as $card)
<div class="col-md-3 col-sm-6"><div class="small-box bg-aqua"><div class="inner"><h3>{{ $card[1] }}</h3><p>{{ $card[0] }}</p></div><div class="icon"><i class="fa {{ $card[2] }}"></i></div></div></div>
@endforeach
</div>
<div class="box box-primary"><div class="box-header"><h3 class="box-title">Quick Actions</h3></div><div class="box-body">
<a href="{{ route('banking-insurance.policies.create') }}" class="btn btn-primary"><i class="fa fa-plus"></i> New Policy</a>
<a href="{{ route('banking-insurance.premiums.create') }}" class="btn btn-success"><i class="fa fa-money"></i> Receive Premium</a>
<a href="{{ route('banking-insurance.claims.create') }}" class="btn btn-warning"><i class="fa fa-medkit"></i> New Claim</a>
<a href="{{ route('banking-insurance.products.create') }}" class="btn btn-default"><i class="fa fa-cube"></i> Add Product</a>
</div></div>
@endsection
