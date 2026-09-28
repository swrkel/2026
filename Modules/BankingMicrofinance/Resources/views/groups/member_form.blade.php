@extends('bankingmicrofinance::layouts.app')
@section('page-title', $member->exists ? 'Edit Member' : 'Add Member')
@section('module-content')
<form method="post" action="{{ $member->exists ? route('banking.microfinance.members.update',$member) : route('banking.microfinance.members.store') }}">@csrf @if($member->exists) @method('PUT') @endif
<div class="box"><div class="box-body row">
<div class="form-group col-md-4"><label>Group</label><select name="group_id" class="form-control" required>@foreach($groups as $group)<option value="{{ $group->id }}" @selected(old('group_id',$member->group_id)==$group->id)>{{ $group->group_no }} - {{ $group->name }}</option>@endforeach</select></div>
<div class="form-group col-md-4"><label>Name</label><input name="name" class="form-control" value="{{ old('name',$member->name) }}" required></div>
<div class="form-group col-md-4"><label>NIC</label><input name="nic_no" class="form-control" value="{{ old('nic_no',$member->nic_no) }}"></div>
<div class="form-group col-md-4"><label>Mobile</label><input name="mobile" class="form-control" value="{{ old('mobile',$member->mobile) }}"></div>
<div class="form-group col-md-4"><label>Joined On</label><input type="date" name="joined_on" class="form-control" value="{{ old('joined_on',$member->joined_on) }}"></div>
<div class="form-group col-md-4"><label>Savings Balance</label><input name="compulsory_saving_balance" class="form-control" value="{{ old('compulsory_saving_balance',$member->compulsory_saving_balance ?? 0) }}"></div>
<div class="form-group col-md-8"><label>Address</label><input name="address" class="form-control" value="{{ old('address',$member->address) }}"></div>
<div class="form-group col-md-4"><label>Status</label><select name="status" class="form-control"><option value="active">Active</option><option value="inactive">Inactive</option><option value="blacklisted">Blacklisted</option><option value="closed">Closed</option></select></div>
</div><div class="box-footer"><button class="btn btn-primary">Save</button></div></div></form>
@endsection
