@extends('airlineticketingnew::layouts.app')
@section('atn-title', __('airlineticketingnew::profiles.passenger_profile'))
@section('atn-content')
@include('airlineticketingnew::profiles.navigation')
<div class="atn-panel">
<form method="POST" action="{{ $record->exists ? route('airline-ticketing-new.passengers.update',$record) : route('airline-ticketing-new.passengers.store') }}">
@csrf @if($record->exists) @method('PUT') @endif
<div class="atn-panel-header"><h4>{{ __('airlineticketingnew::profiles.passenger_profile') }}</h4></div>
<div class="atn-panel-body"><div class="row">
@foreach(['title','first_name','middle_name','last_name','gender','date_of_birth','nationality_code','email','phone','alternate_phone','city','country_code'] as $field)
<div class="col-md-3"><div class="form-group"><label>{{ __('airlineticketingnew::profiles.'.$field) }}</label><input class="form-control" name="{{ $field }}" value="{{ old($field,$record->{$field}) }}"></div></div>
@endforeach
<div class="col-md-12"><div class="form-group"><label>{{ __('airlineticketingnew::profiles.address') }}</label><textarea class="form-control" name="address">{{ old('address',$record->address) }}</textarea></div></div>
<div class="col-md-12"><div class="form-group"><label>{{ __('airlineticketingnew::profiles.notes') }}</label><textarea class="form-control" name="notes">{{ old('notes',$record->notes) }}</textarea></div></div>
<div class="col-md-3"><label><input type="checkbox" name="is_vip" value="1" {{ old('is_vip',$record->is_vip) ? 'checked' : '' }}> {{ __('airlineticketingnew::profiles.vip') }}</label></div>
<div class="col-md-3"><label><input type="checkbox" name="is_active" value="1" {{ old('is_active',$record->exists ? $record->is_active : true) ? 'checked' : '' }}> {{ __('airlineticketingnew::profiles.active') }}</label></div>
</div></div>
<div class="atn-panel-footer text-right"><a class="btn btn-default" href="{{ route('airline-ticketing-new.passengers.index') }}">{{ __('airlineticketingnew::profiles.cancel') }}</a> <button class="btn btn-primary">{{ __('airlineticketingnew::profiles.save') }}</button></div>
</form></div>
@endsection
