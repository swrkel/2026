@extends('airlineticketingnew::layouts.app')
@section('atn-title', __('airlineticketingnew::messages.routes'))
@section('atn-content')
<div class="atn-panel">
<form method="POST" action="{{ $record->exists ? route('airline-ticketing-new.masters.routes.update', $record) : route('airline-ticketing-new.masters.routes.store') }}">
@csrf
@if($record->exists) @method('PUT') @endif
<div class="atn-panel-header"><h4>{{ $record->exists ? __('airlineticketingnew::messages.edit') : __('airlineticketingnew::messages.add') }} {{ __('airlineticketingnew::messages.routes') }}</h4></div>
<div class="atn-panel-body"><div class="row"><div class="col-md-4"><div class="form-group"><label>{{ __('airlineticketingnew::messages.code') }}</label><input type="text" name="code" class="form-control" value="{{ old('code', $record->code) }}"></div></div>
<div class="col-md-4"><div class="form-group"><label>{{ __('airlineticketingnew::messages.origin_airport_id') }}</label><input type="text" name="origin_airport_id" class="form-control" value="{{ old('origin_airport_id', $record->origin_airport_id) }}"></div></div>
<div class="col-md-4"><div class="form-group"><label>{{ __('airlineticketingnew::messages.destination_airport_id') }}</label><input type="text" name="destination_airport_id" class="form-control" value="{{ old('destination_airport_id', $record->destination_airport_id) }}"></div></div>
<div class="col-md-4"><div class="form-group"><label>{{ __('airlineticketingnew::messages.default_airline_id') }}</label><input type="text" name="default_airline_id" class="form-control" value="{{ old('default_airline_id', $record->default_airline_id) }}"></div></div>
<div class="col-md-4"><div class="form-group"><label>{{ __('airlineticketingnew::messages.distance_km') }}</label><input type="text" name="distance_km" class="form-control" value="{{ old('distance_km', $record->distance_km) }}"></div></div>
<div class="col-md-4"><div class="form-group"><label>{{ __('airlineticketingnew::messages.duration_minutes') }}</label><input type="text" name="duration_minutes" class="form-control" value="{{ old('duration_minutes', $record->duration_minutes) }}"></div></div>
<div class="col-md-4"><div class="checkbox"><label><input type="checkbox" name="is_active" value="1" {{ old('is_active', $record->exists ? $record->is_active : true) ? 'checked' : '' }}> {{ __('airlineticketingnew::messages.active') }}</label></div></div>
</div></div>
<div class="atn-panel-footer text-right"><a class="btn btn-default" href="{{ route('airline-ticketing-new.masters.routes.index') }}">{{ __('airlineticketingnew::messages.cancel') }}</a> <button class="btn btn-primary">{{ __('airlineticketingnew::messages.save') }}</button></div>
</form></div>
@endsection
