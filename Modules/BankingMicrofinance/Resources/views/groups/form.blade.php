@extends('bankingmicrofinance::layouts.app')
@section('page-title', $group->exists ? 'Edit Group' : 'Add Group')
@section('module-content')
<form method="post" action="{{ $group->exists ? route('banking.microfinance.groups.update',$group) : route('banking.microfinance.groups.store') }}">@csrf @if($group->exists) @method('PUT') @endif
<div class="box"><div class="box-body row">
<div class="form-group col-md-4"><label>Name</label><input name="name" class="form-control" value="{{ old('name',$group->name) }}" required></div>
<div class="form-group col-md-4"><label>Center</label><input name="center_name" class="form-control" value="{{ old('center_name',$group->center_name) }}"></div>
<div class="form-group col-md-4"><label>Village</label><input name="village" class="form-control" value="{{ old('village',$group->village) }}"></div>
<div class="form-group col-md-3"><label>Meeting Day</label><input name="meeting_day" class="form-control" value="{{ old('meeting_day',$group->meeting_day) }}"></div>
<div class="form-group col-md-3"><label>Meeting Time</label><input type="time" name="meeting_time" class="form-control" value="{{ old('meeting_time',$group->meeting_time) }}"></div>
<div class="form-group col-md-3"><label>Field Officer</label><input name="field_officer" class="form-control" value="{{ old('field_officer',$group->field_officer) }}"></div>
<div class="form-group col-md-3"><label>Status</label><select name="status" class="form-control"><option value="active">Active</option><option value="inactive">Inactive</option><option value="closed">Closed</option></select></div>
</div><div class="box-footer"><button class="btn btn-primary">Save</button></div></div></form>
@endsection
