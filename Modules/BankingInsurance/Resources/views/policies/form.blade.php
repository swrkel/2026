@extends('bankinginsurance::layouts.app', ['subtitle' => empty($policy->id) ? 'Add Policy' : 'Edit Policy'])
@section('bankinginsurance_content')
<form method="post" action="{{ empty($policy->id) ? route('banking-insurance.policies.store') : route('banking-insurance.policies.update',$policy) }}">@csrf @if(!empty($policy->id)) @method('PUT') @endif
<div class="box box-primary"><div class="box-body row">
<div class="form-group col-md-3"><label>Product</label><select name="product_id" class="form-control"><option value="">Please Select</option>@foreach($products as $id=>$name)<option value="{{ $id }}" @selected($policy->product_id==$id)>{{ $name }}</option>@endforeach</select></div>
<div class="form-group col-md-3"><label>Policy No</label><input class="form-control" name="policy_no" value="{{ old('policy_no',$policy->policy_no) }}" placeholder="Auto if blank"></div>
<div class="form-group col-md-3"><label>Customer Name</label><input required class="form-control" name="customer_name" value="{{ old('customer_name',$policy->customer_name) }}"></div>
<div class="form-group col-md-3"><label>Mobile</label><input class="form-control" name="mobile" value="{{ old('mobile',$policy->mobile) }}"></div>
<div class="form-group col-md-3"><label>NIC No</label><input class="form-control" name="nic_no" value="{{ old('nic_no',$policy->nic_no) }}"></div>
<div class="form-group col-md-3"><label>Nominee Name</label><input class="form-control" name="nominee_name" value="{{ old('nominee_name',$policy->nominee_name) }}"></div>
<div class="form-group col-md-3"><label>Start Date</label><input type="date" required class="form-control" name="start_date" value="{{ old('start_date', optional($policy->start_date)->format('Y-m-d') ?: now()->toDateString()) }}"></div>
<div class="form-group col-md-3"><label>End Date</label><input type="date" class="form-control" name="end_date" value="{{ old('end_date', optional($policy->end_date)->format('Y-m-d')) }}"></div>
<div class="form-group col-md-3"><label>Sum Assured</label><input required class="form-control input_number" name="sum_assured" value="{{ old('sum_assured',$policy->sum_assured) }}"></div>
<div class="form-group col-md-3"><label>Premium Amount</label><input required class="form-control input_number" name="premium_amount" value="{{ old('premium_amount',$policy->premium_amount) }}"></div>
<div class="form-group col-md-3"><label>Frequency</label><select name="premium_frequency" class="form-control">@foreach(['one_time','monthly','quarterly','half_yearly','yearly'] as $s)<option value="{{ $s }}" @selected($policy->premium_frequency==$s)>{{ ucfirst(str_replace('_',' ',$s)) }}</option>@endforeach</select></div>
<div class="form-group col-md-3"><label>Status</label><select name="status" class="form-control">@foreach(['draft','active','lapsed','cancelled','matured'] as $s)<option value="{{ $s }}" @selected($policy->status==$s)>{{ ucfirst($s) }}</option>@endforeach</select></div>
<div class="form-group col-md-12"><label>Remarks</label><textarea name="remarks" class="form-control">{{ old('remarks',$policy->remarks) }}</textarea></div>
</div><div class="box-footer"><button class="btn btn-primary">Save</button><a href="{{ route('banking-insurance.policies.index') }}" class="btn btn-default">Cancel</a></div></div></form>
@endsection
