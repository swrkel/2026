@extends('airlineticketingnew::layouts.app')
@section('atn-title', __('airlineticketingnew::profiles.loyalty_accounts'))
@section('atn-content')
@include('airlineticketingnew::profiles.navigation')
<div class="atn-panel"><form method="POST" action="{{ route('airline-ticketing-new.passengers.loyalty-accounts.store', $passenger) }}">@csrf
<div class="atn-panel-header"><h4>{{ __('airlineticketingnew::profiles.add') }} {{ __('airlineticketingnew::profiles.loyalty_accounts') }}</h4></div>
<div class="atn-panel-body"><div class="row"><div class="col-md-3"><div class="form-group"><label>{{ __('airlineticketingnew::profiles.airline_id') }}</label><input class="form-control" name="airline_id"></div></div>
<div class="col-md-3"><div class="form-group"><label>{{ __('airlineticketingnew::profiles.program_name') }}</label><input class="form-control" name="program_name"></div></div>
<div class="col-md-3"><div class="form-group"><label>{{ __('airlineticketingnew::profiles.membership_number') }}</label><input class="form-control" name="membership_number"></div></div>
<div class="col-md-3"><div class="form-group"><label>{{ __('airlineticketingnew::profiles.tier_name') }}</label><input class="form-control" name="tier_name"></div></div>
<div class="col-md-3"><div class="form-group"><label>{{ __('airlineticketingnew::profiles.points_balance') }}</label><input class="form-control" name="points_balance"></div></div>
<div class="col-md-3"><div class="form-group"><label>{{ __('airlineticketingnew::profiles.expiry_date') }}</label><input class="form-control" name="expiry_date"></div></div><div class="col-md-3"><label><input type="checkbox" name="is_active" value="1" checked> {{ __('airlineticketingnew::profiles.active') }}</label></div></div></div>
<div class="atn-panel-footer text-right"><button class="btn btn-primary">{{ __('airlineticketingnew::profiles.save') }}</button></div></form></div>
<div class="atn-panel"><div class="table-responsive"><table class="table table-bordered atn-table"><thead><tr><th>{{ __('airlineticketingnew::profiles.airline_id') }}</th><th>{{ __('airlineticketingnew::profiles.program_name') }}</th><th>{{ __('airlineticketingnew::profiles.membership_number') }}</th><th>{{ __('airlineticketingnew::profiles.tier_name') }}</th></tr></thead><tbody>
@forelse($records as $record)<tr><td>{{ $record->airline_id }}</td><td>{{ $record->program_name }}</td><td>{{ $record->membership_number }}</td><td>{{ $record->tier_name }}</td></tr>@empty<tr><td colspan="4" class="text-center">{{ __('airlineticketingnew::profiles.no_records') }}</td></tr>@endforelse
</tbody></table></div></div>
@endsection
