@extends('airlineticketingnew::layouts.app')
@section('atn-title', __('airlineticketingnew::messages.airports'))
@section('atn-content')
<div class="atn-panel">
<form method="POST" action="{{ $record->exists ? route('airline-ticketing-new.masters.airports.update', $record) : route('airline-ticketing-new.masters.airports.store') }}">
@csrf
@if($record->exists) @method('PUT') @endif
<div class="atn-panel-header"><h4>{{ $record->exists ? __('airlineticketingnew::messages.edit') : __('airlineticketingnew::messages.add') }} {{ __('airlineticketingnew::messages.airports') }}</h4></div>
<div class="atn-panel-body"><div class="row"><div class="col-md-4"><div class="form-group"><label>{{ __('airlineticketingnew::messages.name') }}</label><input type="text" name="name" class="form-control" value="{{ old('name', $record->name) }}"></div></div>
<div class="col-md-4"><div class="form-group"><label>{{ __('airlineticketingnew::messages.iata_code') }}</label><input type="text" name="iata_code" class="form-control" value="{{ old('iata_code', $record->iata_code) }}"></div></div>
<div class="col-md-4"><div class="form-group"><label>{{ __('airlineticketingnew::messages.icao_code') }}</label><input type="text" name="icao_code" class="form-control" value="{{ old('icao_code', $record->icao_code) }}"></div></div>
<div class="col-md-4"><div class="form-group"><label>{{ __('airlineticketingnew::messages.country_code') }}</label><input type="text" name="country_code" class="form-control" value="{{ old('country_code', $record->country_code) }}"></div></div>
<div class="col-md-4"><div class="form-group"><label>{{ __('airlineticketingnew::messages.city') }}</label><input type="text" name="city" class="form-control" value="{{ old('city', $record->city) }}"></div></div>
<div class="col-md-4"><div class="form-group"><label>{{ __('airlineticketingnew::messages.timezone') }}</label><input type="text" name="timezone" class="form-control" value="{{ old('timezone', $record->timezone) }}"></div></div>
<div class="col-md-4"><div class="form-group"><label>{{ __('airlineticketingnew::messages.latitude') }}</label><input type="text" name="latitude" class="form-control" value="{{ old('latitude', $record->latitude) }}"></div></div>
<div class="col-md-4"><div class="form-group"><label>{{ __('airlineticketingnew::messages.longitude') }}</label><input type="text" name="longitude" class="form-control" value="{{ old('longitude', $record->longitude) }}"></div></div><div class="col-md-12"><div class="form-group"><label>{{ __('airlineticketingnew::messages.terminal_notes') }}</label><textarea name="terminal_notes" class="form-control" rows="3">{{ old('terminal_notes', $record->terminal_notes) }}</textarea></div></div>
<div class="col-md-4"><div class="checkbox"><label><input type="checkbox" name="is_active" value="1" {{ old('is_active', $record->exists ? $record->is_active : true) ? 'checked' : '' }}> {{ __('airlineticketingnew::messages.active') }}</label></div></div>
</div></div>
<div class="atn-panel-footer text-right"><a class="btn btn-default" href="{{ route('airline-ticketing-new.masters.airports.index') }}">{{ __('airlineticketingnew::messages.cancel') }}</a> <button class="btn btn-primary">{{ __('airlineticketingnew::messages.save') }}</button></div>
</form></div>
@endsection
