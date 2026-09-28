@extends('airlineticketingnew::layouts.app')
@section('atn-title', __('airlineticketingnew::messages.airlines'))
@section('atn-content')
<div class="atn-panel">
<form method="POST" action="{{ $record->exists ? route('airline-ticketing-new.masters.airlines.update', $record) : route('airline-ticketing-new.masters.airlines.store') }}">
@csrf
@if($record->exists) @method('PUT') @endif
<div class="atn-panel-header"><h4>{{ $record->exists ? __('airlineticketingnew::messages.edit') : __('airlineticketingnew::messages.add') }} {{ __('airlineticketingnew::messages.airlines') }}</h4></div>
<div class="atn-panel-body"><div class="row"><div class="col-md-4"><div class="form-group"><label>{{ __('airlineticketingnew::messages.name') }}</label><input type="text" name="name" class="form-control" value="{{ old('name', $record->name) }}"></div></div>
<div class="col-md-4"><div class="form-group"><label>{{ __('airlineticketingnew::messages.iata_code') }}</label><input type="text" name="iata_code" class="form-control" value="{{ old('iata_code', $record->iata_code) }}"></div></div>
<div class="col-md-4"><div class="form-group"><label>{{ __('airlineticketingnew::messages.icao_code') }}</label><input type="text" name="icao_code" class="form-control" value="{{ old('icao_code', $record->icao_code) }}"></div></div>
<div class="col-md-4"><div class="form-group"><label>{{ __('airlineticketingnew::messages.ticketing_code') }}</label><input type="text" name="ticketing_code" class="form-control" value="{{ old('ticketing_code', $record->ticketing_code) }}"></div></div>
<div class="col-md-4"><div class="form-group"><label>{{ __('airlineticketingnew::messages.country_code') }}</label><input type="text" name="country_code" class="form-control" value="{{ old('country_code', $record->country_code) }}"></div></div>
<div class="col-md-4"><div class="form-group"><label>{{ __('airlineticketingnew::messages.phone') }}</label><input type="text" name="phone" class="form-control" value="{{ old('phone', $record->phone) }}"></div></div>
<div class="col-md-4"><div class="form-group"><label>{{ __('airlineticketingnew::messages.email') }}</label><input type="text" name="email" class="form-control" value="{{ old('email', $record->email) }}"></div></div>
<div class="col-md-4"><div class="form-group"><label>{{ __('airlineticketingnew::messages.website') }}</label><input type="text" name="website" class="form-control" value="{{ old('website', $record->website) }}"></div></div><div class="col-md-12"><div class="form-group"><label>{{ __('airlineticketingnew::messages.notes') }}</label><textarea name="notes" class="form-control" rows="3">{{ old('notes', $record->notes) }}</textarea></div></div>
<div class="col-md-4"><div class="checkbox"><label><input type="checkbox" name="is_active" value="1" {{ old('is_active', $record->exists ? $record->is_active : true) ? 'checked' : '' }}> {{ __('airlineticketingnew::messages.active') }}</label></div></div>
</div></div>
<div class="atn-panel-footer text-right"><a class="btn btn-default" href="{{ route('airline-ticketing-new.masters.airlines.index') }}">{{ __('airlineticketingnew::messages.cancel') }}</a> <button class="btn btn-primary">{{ __('airlineticketingnew::messages.save') }}</button></div>
</form></div>
@endsection
