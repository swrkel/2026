@extends('airlineticketingnew::layouts.app')
@section('atn-title', __('airlineticketingnew::messages.agents'))
@section('atn-content')
<div class="atn-panel">
<form method="POST" action="{{ $record->exists ? route('airline-ticketing-new.masters.agents.update', $record) : route('airline-ticketing-new.masters.agents.store') }}">
@csrf
@if($record->exists) @method('PUT') @endif
<div class="atn-panel-header"><h4>{{ $record->exists ? __('airlineticketingnew::messages.edit') : __('airlineticketingnew::messages.add') }} {{ __('airlineticketingnew::messages.agents') }}</h4></div>
<div class="atn-panel-body"><div class="row"><div class="col-md-4"><div class="form-group"><label>{{ __('airlineticketingnew::messages.code') }}</label><input type="text" name="code" class="form-control" value="{{ old('code', $record->code) }}"></div></div>
<div class="col-md-4"><div class="form-group"><label>{{ __('airlineticketingnew::messages.name') }}</label><input type="text" name="name" class="form-control" value="{{ old('name', $record->name) }}"></div></div>
<div class="col-md-4"><div class="form-group"><label>{{ __('airlineticketingnew::messages.agent_type') }}</label><input type="text" name="agent_type" class="form-control" value="{{ old('agent_type', $record->agent_type) }}"></div></div>
<div class="col-md-4"><div class="form-group"><label>{{ __('airlineticketingnew::messages.contact_person') }}</label><input type="text" name="contact_person" class="form-control" value="{{ old('contact_person', $record->contact_person) }}"></div></div>
<div class="col-md-4"><div class="form-group"><label>{{ __('airlineticketingnew::messages.phone') }}</label><input type="text" name="phone" class="form-control" value="{{ old('phone', $record->phone) }}"></div></div>
<div class="col-md-4"><div class="form-group"><label>{{ __('airlineticketingnew::messages.email') }}</label><input type="text" name="email" class="form-control" value="{{ old('email', $record->email) }}"></div></div>
<div class="col-md-4"><div class="form-group"><label>{{ __('airlineticketingnew::messages.commission_type') }}</label><input type="text" name="commission_type" class="form-control" value="{{ old('commission_type', $record->commission_type) }}"></div></div>
<div class="col-md-4"><div class="form-group"><label>{{ __('airlineticketingnew::messages.commission_value') }}</label><input type="text" name="commission_value" class="form-control" value="{{ old('commission_value', $record->commission_value) }}"></div></div>
<div class="col-md-4"><div class="form-group"><label>{{ __('airlineticketingnew::messages.credit_limit') }}</label><input type="text" name="credit_limit" class="form-control" value="{{ old('credit_limit', $record->credit_limit) }}"></div></div><div class="col-md-12"><div class="form-group"><label>{{ __('airlineticketingnew::messages.address') }}</label><textarea name="address" class="form-control" rows="3">{{ old('address', $record->address) }}</textarea></div></div>
<div class="col-md-4"><div class="checkbox"><label><input type="checkbox" name="is_active" value="1" {{ old('is_active', $record->exists ? $record->is_active : true) ? 'checked' : '' }}> {{ __('airlineticketingnew::messages.active') }}</label></div></div>
</div></div>
<div class="atn-panel-footer text-right"><a class="btn btn-default" href="{{ route('airline-ticketing-new.masters.agents.index') }}">{{ __('airlineticketingnew::messages.cancel') }}</a> <button class="btn btn-primary">{{ __('airlineticketingnew::messages.save') }}</button></div>
</form></div>
@endsection
