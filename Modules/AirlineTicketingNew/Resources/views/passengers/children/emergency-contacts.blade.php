@extends('airlineticketingnew::layouts.app')
@section('atn-title', __('airlineticketingnew::profiles.emergency_contacts'))
@section('atn-content')
@include('airlineticketingnew::profiles.navigation')
<div class="atn-panel"><form method="POST" action="{{ route('airline-ticketing-new.passengers.emergency-contacts.store', $passenger) }}">@csrf
<div class="atn-panel-header"><h4>{{ __('airlineticketingnew::profiles.add') }} {{ __('airlineticketingnew::profiles.emergency_contacts') }}</h4></div>
<div class="atn-panel-body"><div class="row"><div class="col-md-3"><div class="form-group"><label>{{ __('airlineticketingnew::profiles.name') }}</label><input class="form-control" name="name"></div></div>
<div class="col-md-3"><div class="form-group"><label>{{ __('airlineticketingnew::profiles.relationship') }}</label><input class="form-control" name="relationship"></div></div>
<div class="col-md-3"><div class="form-group"><label>{{ __('airlineticketingnew::profiles.phone') }}</label><input class="form-control" name="phone"></div></div>
<div class="col-md-3"><div class="form-group"><label>{{ __('airlineticketingnew::profiles.alternate_phone') }}</label><input class="form-control" name="alternate_phone"></div></div>
<div class="col-md-3"><div class="form-group"><label>{{ __('airlineticketingnew::profiles.email') }}</label><input class="form-control" name="email"></div></div>
<div class="col-md-3"><div class="form-group"><label>{{ __('airlineticketingnew::profiles.country_code') }}</label><input class="form-control" name="country_code"></div></div><div class="col-md-3"><label><input type="checkbox" name="is_active" value="1" checked> {{ __('airlineticketingnew::profiles.active') }}</label></div></div></div>
<div class="atn-panel-footer text-right"><button class="btn btn-primary">{{ __('airlineticketingnew::profiles.save') }}</button></div></form></div>
<div class="atn-panel"><div class="table-responsive"><table class="table table-bordered atn-table"><thead><tr><th>{{ __('airlineticketingnew::profiles.name') }}</th><th>{{ __('airlineticketingnew::profiles.relationship') }}</th><th>{{ __('airlineticketingnew::profiles.phone') }}</th><th>{{ __('airlineticketingnew::profiles.alternate_phone') }}</th></tr></thead><tbody>
@forelse($records as $record)<tr><td>{{ $record->name }}</td><td>{{ $record->relationship }}</td><td>{{ $record->phone }}</td><td>{{ $record->alternate_phone }}</td></tr>@empty<tr><td colspan="4" class="text-center">{{ __('airlineticketingnew::profiles.no_records') }}</td></tr>@endforelse
</tbody></table></div></div>
@endsection
