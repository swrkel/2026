@extends('airlineticketingnew::layouts.app')
@section('atn-title', __('airlineticketingnew::messages.commission-rules'))
@section('atn-content')
<div class="atn-panel">
<form method="POST" action="{{ $record->exists ? route('airline-ticketing-new.masters.commission-rules.update', $record) : route('airline-ticketing-new.masters.commission-rules.store') }}">
@csrf
@if($record->exists) @method('PUT') @endif
<div class="atn-panel-header"><h4>{{ $record->exists ? __('airlineticketingnew::messages.edit') : __('airlineticketingnew::messages.add') }} {{ __('airlineticketingnew::messages.commission-rules') }}</h4></div>
<div class="atn-panel-body"><div class="row"><div class="col-md-4"><div class="form-group"><label>{{ __('airlineticketingnew::messages.name') }}</label><input type="text" name="name" class="form-control" value="{{ old('name', $record->name) }}"></div></div>
<div class="col-md-4"><div class="form-group"><label>{{ __('airlineticketingnew::messages.code') }}</label><input type="text" name="code" class="form-control" value="{{ old('code', $record->code) }}"></div></div>
<div class="col-md-4"><div class="form-group"><label>{{ __('airlineticketingnew::messages.party_type') }}</label><input type="text" name="party_type" class="form-control" value="{{ old('party_type', $record->party_type) }}"></div></div>
<div class="col-md-4"><div class="form-group"><label>{{ __('airlineticketingnew::messages.calculation_type') }}</label><input type="text" name="calculation_type" class="form-control" value="{{ old('calculation_type', $record->calculation_type) }}"></div></div>
<div class="col-md-4"><div class="form-group"><label>{{ __('airlineticketingnew::messages.value') }}</label><input type="text" name="value" class="form-control" value="{{ old('value', $record->value) }}"></div></div>
<div class="col-md-4"><div class="form-group"><label>{{ __('airlineticketingnew::messages.airline_id') }}</label><input type="text" name="airline_id" class="form-control" value="{{ old('airline_id', $record->airline_id) }}"></div></div>
<div class="col-md-4"><div class="form-group"><label>{{ __('airlineticketingnew::messages.route_id') }}</label><input type="text" name="route_id" class="form-control" value="{{ old('route_id', $record->route_id) }}"></div></div>
<div class="col-md-4"><div class="form-group"><label>{{ __('airlineticketingnew::messages.travel_class_id') }}</label><input type="text" name="travel_class_id" class="form-control" value="{{ old('travel_class_id', $record->travel_class_id) }}"></div></div>
<div class="col-md-4"><div class="form-group"><label>{{ __('airlineticketingnew::messages.effective_from') }}</label><input type="text" name="effective_from" class="form-control" value="{{ old('effective_from', $record->effective_from) }}"></div></div>
<div class="col-md-4"><div class="form-group"><label>{{ __('airlineticketingnew::messages.effective_to') }}</label><input type="text" name="effective_to" class="form-control" value="{{ old('effective_to', $record->effective_to) }}"></div></div>
<div class="col-md-4"><div class="checkbox"><label><input type="checkbox" name="is_active" value="1" {{ old('is_active', $record->exists ? $record->is_active : true) ? 'checked' : '' }}> {{ __('airlineticketingnew::messages.active') }}</label></div></div>
</div></div>
<div class="atn-panel-footer text-right"><a class="btn btn-default" href="{{ route('airline-ticketing-new.masters.commission-rules.index') }}">{{ __('airlineticketingnew::messages.cancel') }}</a> <button class="btn btn-primary">{{ __('airlineticketingnew::messages.save') }}</button></div>
</form></div>
@endsection
