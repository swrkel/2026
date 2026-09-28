@extends('airlineticketingnew::layouts.app')
@section('atn-title', __('airlineticketingnew::messages.currencies'))
@section('atn-content')
<div class="atn-panel">
<form method="POST" action="{{ $record->exists ? route('airline-ticketing-new.masters.currencies.update', $record) : route('airline-ticketing-new.masters.currencies.store') }}">
@csrf
@if($record->exists) @method('PUT') @endif
<div class="atn-panel-header"><h4>{{ $record->exists ? __('airlineticketingnew::messages.edit') : __('airlineticketingnew::messages.add') }} {{ __('airlineticketingnew::messages.currencies') }}</h4></div>
<div class="atn-panel-body"><div class="row"><div class="col-md-4"><div class="form-group"><label>{{ __('airlineticketingnew::messages.code') }}</label><input type="text" name="code" class="form-control" value="{{ old('code', $record->code) }}"></div></div>
<div class="col-md-4"><div class="form-group"><label>{{ __('airlineticketingnew::messages.name') }}</label><input type="text" name="name" class="form-control" value="{{ old('name', $record->name) }}"></div></div>
<div class="col-md-4"><div class="form-group"><label>{{ __('airlineticketingnew::messages.symbol') }}</label><input type="text" name="symbol" class="form-control" value="{{ old('symbol', $record->symbol) }}"></div></div>
<div class="col-md-4"><div class="form-group"><label>{{ __('airlineticketingnew::messages.decimal_places') }}</label><input type="text" name="decimal_places" class="form-control" value="{{ old('decimal_places', $record->decimal_places) }}"></div></div>
<div class="col-md-4"><div class="form-group"><label>{{ __('airlineticketingnew::messages.exchange_rate') }}</label><input type="text" name="exchange_rate" class="form-control" value="{{ old('exchange_rate', $record->exchange_rate) }}"></div></div>
<div class="col-md-4"><div class="form-group"><label>{{ __('airlineticketingnew::messages.is_base') }}</label><input type="text" name="is_base" class="form-control" value="{{ old('is_base', $record->is_base) }}"></div></div>
<div class="col-md-4"><div class="checkbox"><label><input type="checkbox" name="is_active" value="1" {{ old('is_active', $record->exists ? $record->is_active : true) ? 'checked' : '' }}> {{ __('airlineticketingnew::messages.active') }}</label></div></div>
</div></div>
<div class="atn-panel-footer text-right"><a class="btn btn-default" href="{{ route('airline-ticketing-new.masters.currencies.index') }}">{{ __('airlineticketingnew::messages.cancel') }}</a> <button class="btn btn-primary">{{ __('airlineticketingnew::messages.save') }}</button></div>
</form></div>
@endsection
