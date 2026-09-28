@extends('layouts.app')
@section('title','Pawning Pledge')
@section('content')
<section class="content-header no-print"><h1>Pawning Pledge</h1></section><section class="content no-print">@include('pawning::layouts.nav')
<form method="POST" action="{{ $action }}">@csrf
<div class="box box-primary"><div class="box-header with-border"><h3 class="box-title">Pledge Details</h3></div><div class="box-body"><div class="row">
<div class="col-md-3 form-group"><label>Pledge No *</label><input name="pledge_no" class="form-control" value="{{ old('pledge_no',$pledge->pledge_no) }}" required></div>
<div class="col-md-3 form-group"><label>Product</label><select name="pawning_product_id" class="form-control"><option value="">Select</option>@foreach($products as $id=>$name)<option value="{{ $id }}" {{ old('pawning_product_id',$pledge->pawning_product_id)==$id?'selected':'' }}>{{ $name }}</option>@endforeach</select></div>
<div class="col-md-3 form-group"><label>Location</label><select name="location_id" class="form-control"><option value="">Select</option>@foreach($locations as $id=>$name)<option value="{{ $id }}" {{ old('location_id',$pledge->location_id)==$id?'selected':'' }}>{{ $name }}</option>@endforeach</select></div>
<div class="col-md-3 form-group"><label>Banking Customer ID</label><input type="number" name="banking_customer_id" class="form-control" value="{{ old('banking_customer_id',$pledge->banking_customer_id) }}"></div>
<div class="col-md-4 form-group"><label>Customer Name</label><input name="customer_name" class="form-control" value="{{ old('customer_name',$pledge->customer_name) }}"></div>
<div class="col-md-2 form-group"><label>Mobile</label><input name="customer_mobile" class="form-control" value="{{ old('customer_mobile',$pledge->customer_mobile) }}"></div>
<div class="col-md-3 form-group"><label>Pledged On</label><input type="date" name="pledged_on" class="form-control" value="{{ old('pledged_on', $pledge->pledged_on ? substr($pledge->pledged_on,0,10) : '') }}"></div>
<div class="col-md-3 form-group"><label>Due On</label><input type="date" name="due_on" class="form-control" value="{{ old('due_on', $pledge->due_on ? substr($pledge->due_on,0,10) : '') }}"></div>
<div class="col-md-3 form-group"><label>Assessed Value</label><input type="number" step="0.01" name="assessed_value" class="form-control" value="{{ old('assessed_value',$pledge->assessed_value) }}"></div>
<div class="col-md-3 form-group"><label>Advance Amount</label><input type="number" step="0.01" name="advance_amount" class="form-control" value="{{ old('advance_amount',$pledge->advance_amount) }}"></div>
<div class="col-md-3 form-group"><label>Interest %</label><input type="number" step="0.0001" name="interest_rate" class="form-control" value="{{ old('interest_rate',$pledge->interest_rate) }}"></div>
<div class="col-md-3 form-group"><label>Outstanding</label><input type="number" step="0.01" name="outstanding_amount" class="form-control" value="{{ old('outstanding_amount',$pledge->outstanding_amount) }}"></div>
<div class="col-md-3 form-group"><label>Workflow</label><select name="workflow_status" class="form-control">@foreach(config('pawning.workflow_statuses',[]) as $status)<option value="{{ $status }}" {{ old('workflow_status',$pledge->workflow_status)==$status?'selected':'' }}>{{ ucfirst(str_replace('_',' ',$status)) }}</option>@endforeach</select></div>
<div class="col-md-3 form-group"><label>Status</label><select name="status" class="form-control">@foreach(['active','redeemed','renewed','auctioned','closed','cancelled'] as $status)<option value="{{ $status }}" {{ old('status',$pledge->status ?: 'active')==$status?'selected':'' }}>{{ ucfirst($status) }}</option>@endforeach</select></div>
<div class="col-md-12 form-group"><label>Articles</label><select name="article_ids[]" class="form-control" multiple size="6">@foreach($articles as $article)<option value="{{ $article->id }}" {{ in_array($article->id,$selectedArticles)?'selected':'' }}>{{ $article->article_no }} - {{ $article->name }} ({{ number_format($article->estimated_value,2) }})</option>@endforeach</select></div>
<div class="col-md-12 form-group"><label>Notes</label><textarea name="notes" class="form-control" rows="3">{{ old('notes',$pledge->notes) }}</textarea></div>
</div></div><div class="box-footer"><button class="btn btn-primary"><i class="fa fa-save"></i> Save</button><a href="{{ route('pawning.pledges.index') }}" class="btn btn-default">Cancel</a></div></div>
</form></section>@endsection
