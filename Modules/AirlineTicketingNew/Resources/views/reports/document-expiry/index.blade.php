@extends('airlineticketingnew::layouts.app')
@section('atn-title', __('airlineticketingnew::profiles.document_expiry_report'))
@section('atn-content')
@include('airlineticketingnew::profiles.navigation')
<div class="atn-toolbar"><form method="GET"><div class="input-group"><input type="number" min="1" max="365" class="form-control" name="days" value="{{ $days }}"><span class="input-group-btn"><button class="btn btn-primary">{{ __('airlineticketingnew::profiles.apply') }}</button></span></div></form></div>
<div class="atn-panel"><div class="table-responsive"><table class="table table-bordered atn-table"><thead><tr><th>{{ __('airlineticketingnew::profiles.expiry_date') }}</th><th>{{ __('airlineticketingnew::profiles.passenger_no') }}</th><th>{{ __('airlineticketingnew::profiles.passenger_name') }}</th><th>{{ __('airlineticketingnew::profiles.item_type') }}</th><th>{{ __('airlineticketingnew::profiles.item_number') }}</th></tr></thead><tbody>
@forelse($records as $record)<tr><td>{{ $record->expiry_date }}</td><td>{{ $record->passenger_no }}</td><td>{{ $record->passenger_name }}</td><td>{{ $record->item_type }}</td><td>{{ $record->item_number }}</td></tr>
@empty<tr><td colspan="5" class="text-center">{{ __('airlineticketingnew::profiles.no_records') }}</td></tr>@endforelse
</tbody></table></div></div>
@endsection
