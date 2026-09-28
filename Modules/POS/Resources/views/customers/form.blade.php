@extends('pos::layouts.app')
@section('pos_content')
<form method="post" action="{{ $customer ? route('pos.customers.update',$customer->id) : route('pos.customers.store') }}">
  @csrf @if($customer) @method('PUT') @endif
  <div class="card">
    <div class="card-header"><strong>{{ $customer ? 'Edit Customer' : 'Add Customer' }}</strong></div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-3"><label>Customer Code</label><input class="form-control" name="customer_code" value="{{ old('customer_code', $customer->customer_code ?? '') }}" placeholder="Auto if blank"></div>
        <div class="col-md-3"><label>Name *</label><input class="form-control" name="name" required value="{{ old('name', $customer->name ?? '') }}"></div>
        <div class="col-md-3"><label>Mobile</label><input class="form-control" name="mobile" value="{{ old('mobile', $customer->mobile ?? '') }}"></div>
        <div class="col-md-3"><label>Email</label><input class="form-control" name="email" value="{{ old('email', $customer->email ?? '') }}"></div>
      </div><br>
      <div class="row">
        <div class="col-md-3"><label>NIC / ID No</label><input class="form-control" name="nic_no" value="{{ old('nic_no', $customer->nic_no ?? '') }}"></div>
        <div class="col-md-3"><label>Customer Type</label><select class="form-control" name="customer_type"><option value="walk_in" @selected(old('customer_type',$customer->customer_type ?? '')==='walk_in')>Walk-in</option><option value="credit" @selected(old('customer_type',$customer->customer_type ?? '')==='credit')>Credit</option><option value="loyalty" @selected(old('customer_type',$customer->customer_type ?? '')==='loyalty')>Loyalty</option></select></div>
        <div class="col-md-3"><label>Credit Limit</label><input class="form-control" name="credit_limit" type="number" step="0.0001" value="{{ old('credit_limit', $customer->credit_limit ?? 0) }}"></div>
        <div class="col-md-3"><label>Status</label><select class="form-control" name="status"><option value="active" @selected(old('status',$customer->status ?? 'active')==='active')>Active</option><option value="inactive" @selected(old('status',$customer->status ?? '')==='inactive')>Inactive</option></select></div>
      </div><br>
      @if(!$customer)
      <div class="row"><div class="col-md-3"><label>Opening Balance</label><input class="form-control" name="opening_balance" type="number" step="0.0001" value="{{ old('opening_balance',0) }}"></div></div><br>
      @endif
      <div class="row">
        <div class="col-md-6"><label>Address</label><textarea class="form-control" name="address" rows="3">{{ old('address',$customer->address ?? '') }}</textarea></div>
        <div class="col-md-6"><label>Note</label><textarea class="form-control" name="note" rows="3">{{ old('note',$customer->note ?? '') }}</textarea></div>
      </div><br>
      <button class="btn btn-success">{{ $customer ? 'Update' : 'Save' }}</button>
      <a class="btn btn-default" href="{{ route('pos.customers.index') }}">Cancel</a>
    </div>
  </div>
</form>
@endsection
