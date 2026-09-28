@extends('airlineticketingnew::layouts.app')
@section('atn-title', __('airlineticketingnew::profiles.corporate_customer_profile'))
@section('atn-content')
@include('airlineticketingnew::profiles.navigation')
<div class="atn-panel"><form method="POST" action="{{ $record->exists ? route('airline-ticketing-new.corporate-customers.update',$record) : route('airline-ticketing-new.corporate-customers.store') }}">
@csrf @if($record->exists) @method('PUT') @endif
<div class="atn-panel-header"><h4>{{ __('airlineticketingnew::profiles.corporate_customer_profile') }}</h4></div><div class="atn-panel-body"><div class="row">
@foreach(['company_name','registration_no','tax_no','contact_person','email','phone','alternate_phone','city','country_code','credit_limit','payment_terms_days','currency_code'] as $field)
<div class="col-md-3"><div class="form-group"><label>{{ __('airlineticketingnew::profiles.'.$field) }}</label><input class="form-control" name="{{ $field }}" value="{{ old($field,$record->{$field}) }}"></div></div>
@endforeach
<div class="col-md-12"><div class="form-group"><label>{{ __('airlineticketingnew::profiles.billing_address') }}</label><textarea class="form-control" name="billing_address">{{ old('billing_address',$record->billing_address) }}</textarea></div></div>
<div class="col-md-12"><div class="form-group"><label>{{ __('airlineticketingnew::profiles.notes') }}</label><textarea class="form-control" name="notes">{{ old('notes',$record->notes) }}</textarea></div></div>
<div class="col-md-3"><label><input type="checkbox" name="is_active" value="1" {{ old('is_active',$record->exists ? $record->is_active : true) ? 'checked' : '' }}> {{ __('airlineticketingnew::profiles.active') }}</label></div>
</div></div><div class="atn-panel-footer text-right"><a class="btn btn-default" href="{{ route('airline-ticketing-new.corporate-customers.index') }}">{{ __('airlineticketingnew::profiles.cancel') }}</a> <button class="btn btn-primary">{{ __('airlineticketingnew::profiles.save') }}</button></div>
</form></div>
@endsection
