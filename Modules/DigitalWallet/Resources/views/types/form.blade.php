@extends('digitalwallet::layout')
@section('digitalwallet-title', $type->exists ? 'Edit Wallet Type' : 'Add Wallet Type')
@section('digitalwallet-content')
<form method="POST" action="{{ $type->exists ? route('digitalwallet.types.update', $type) : route('digitalwallet.types.store') }}">@csrf @if($type->exists) @method('PUT') @endif
<div class="box box-primary"><div class="box-body"><div class="row">
<div class="col-md-4 form-group"><label>Type Code *</label><input class="form-control" name="type_code" value="{{ old('type_code', $type->type_code) }}" required></div>
<div class="col-md-4 form-group"><label>Type Name *</label><input class="form-control" name="type_name" value="{{ old('type_name', $type->type_name) }}" required></div>
<div class="col-md-4 form-group"><label>Channel</label><input class="form-control" name="channel" value="{{ old('channel', $type->channel) }}"></div>
<div class="col-md-4 form-group"><label>Currency</label><input class="form-control" name="currency" value="{{ old('currency', $type->currency ?: 'LKR') }}"></div>
<div class="col-md-4 form-group"><label>Default Type</label><select class="form-control" name="is_default"><option value="0" {{ old('is_default', $type->is_default) ? '' : 'selected' }}>No</option><option value="1" {{ old('is_default', $type->is_default) ? 'selected' : '' }}>Yes</option></select></div>
<div class="col-md-4 form-group"><label>Status</label><select class="form-control" name="is_active"><option value="1" {{ old('is_active', $type->is_active ?? true) ? 'selected' : '' }}>Active</option><option value="0" {{ old('is_active', $type->is_active ?? true) ? '' : 'selected' }}>Inactive</option></select></div>
<div class="col-md-12 form-group"><label>Description</label><textarea class="form-control" name="description">{{ old('description', $type->description) }}</textarea></div>
</div></div><div class="box-footer"><button class="btn btn-primary">Save</button><a href="{{ route('digitalwallet.types.index') }}" class="btn btn-default">Back</a></div></div></form>
@endsection
