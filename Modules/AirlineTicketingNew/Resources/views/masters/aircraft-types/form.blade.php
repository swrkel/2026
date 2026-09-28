@extends('airlineticketingnew::layouts.app')
@section('atn-title', __('airlineticketingnew::messages.aircraft-types'))
@section('atn-content')
<div class="atn-panel">
<form method="POST" action="{{ $record->exists ? route('airline-ticketing-new.masters.aircraft-types.update', $record) : route('airline-ticketing-new.masters.aircraft-types.store') }}">
@csrf
@if($record->exists) @method('PUT') @endif
<div class="atn-panel-header"><h4>{{ $record->exists ? __('airlineticketingnew::messages.edit') : __('airlineticketingnew::messages.add') }} {{ __('airlineticketingnew::messages.aircraft-types') }}</h4></div>
<div class="atn-panel-body"><div class="row"><div class="col-md-4"><div class="form-group"><label>{{ __('airlineticketingnew::messages.name') }}</label><input type="text" name="name" class="form-control" value="{{ old('name', $record->name) }}"></div></div>
<div class="col-md-4"><div class="form-group"><label>{{ __('airlineticketingnew::messages.iata_code') }}</label><input type="text" name="iata_code" class="form-control" value="{{ old('iata_code', $record->iata_code) }}"></div></div>
<div class="col-md-4"><div class="form-group"><label>{{ __('airlineticketingnew::messages.icao_code') }}</label><input type="text" name="icao_code" class="form-control" value="{{ old('icao_code', $record->icao_code) }}"></div></div>
<div class="col-md-4"><div class="form-group"><label>{{ __('airlineticketingnew::messages.manufacturer') }}</label><input type="text" name="manufacturer" class="form-control" value="{{ old('manufacturer', $record->manufacturer) }}"></div></div>
<div class="col-md-4"><div class="form-group"><label>{{ __('airlineticketingnew::messages.model') }}</label><input type="text" name="model" class="form-control" value="{{ old('model', $record->model) }}"></div></div>
<div class="col-md-4"><div class="form-group"><label>{{ __('airlineticketingnew::messages.seat_capacity') }}</label><input type="text" name="seat_capacity" class="form-control" value="{{ old('seat_capacity', $record->seat_capacity) }}"></div></div>
<div class="col-md-4"><div class="checkbox"><label><input type="checkbox" name="is_active" value="1" {{ old('is_active', $record->exists ? $record->is_active : true) ? 'checked' : '' }}> {{ __('airlineticketingnew::messages.active') }}</label></div></div>
</div></div>
<div class="atn-panel-footer text-right"><a class="btn btn-default" href="{{ route('airline-ticketing-new.masters.aircraft-types.index') }}">{{ __('airlineticketingnew::messages.cancel') }}</a> <button class="btn btn-primary">{{ __('airlineticketingnew::messages.save') }}</button></div>
</form></div>
@endsection
