@extends('bankingmicrofinance::layouts.app')
@section('page-title', $product->exists ? 'Edit Product' : 'Add Product')
@section('module-content')
<form method="post" action="{{ $product->exists ? route('banking.microfinance.products.update',$product) : route('banking.microfinance.products.store') }}">@csrf @if($product->exists) @method('PUT') @endif
<div class="box"><div class="box-body row">
<div class="form-group col-md-3"><label>Code</label><input name="code" class="form-control" value="{{ old('code',$product->code) }}" required></div>
<div class="form-group col-md-5"><label>Name</label><input name="name" class="form-control" value="{{ old('name',$product->name) }}" required></div>
<div class="form-group col-md-2"><label>Min Amount</label><input name="min_amount" class="form-control" value="{{ old('min_amount',$product->min_amount ?? 0) }}"></div>
<div class="form-group col-md-2"><label>Max Amount</label><input name="max_amount" class="form-control" value="{{ old('max_amount',$product->max_amount ?? 0) }}"></div>
<div class="form-group col-md-3"><label>Annual Interest %</label><input name="annual_interest_rate" class="form-control" value="{{ old('annual_interest_rate',$product->annual_interest_rate ?? 0) }}"></div>
<div class="form-group col-md-3"><label>Default Term Weeks</label><input name="default_term_weeks" class="form-control" value="{{ old('default_term_weeks',$product->default_term_weeks ?? 24) }}"></div>
<div class="form-group col-md-3"><label>Method</label><select name="interest_method" class="form-control"><option value="flat">Flat</option><option value="declining">Declining</option></select></div>
<div class="form-group col-md-3"><label>Active</label><select name="is_active" class="form-control"><option value="1">Yes</option><option value="0">No</option></select></div>
</div><div class="box-footer"><button class="btn btn-primary">Save</button></div></div></form>
@endsection
