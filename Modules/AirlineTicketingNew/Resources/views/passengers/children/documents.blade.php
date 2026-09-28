@extends('airlineticketingnew::layouts.app')
@section('atn-title', __('airlineticketingnew::profiles.documents'))
@section('atn-content')
@include('airlineticketingnew::profiles.navigation')
<div class="atn-panel"><form method="POST" action="{{ route('airline-ticketing-new.passengers.documents.store', $passenger) }}">@csrf
<div class="atn-panel-header"><h4>{{ __('airlineticketingnew::profiles.add') }} {{ __('airlineticketingnew::profiles.documents') }}</h4></div>
<div class="atn-panel-body"><div class="row"><div class="col-md-3"><div class="form-group"><label>{{ __('airlineticketingnew::profiles.document_type') }}</label><input class="form-control" name="document_type"></div></div>
<div class="col-md-3"><div class="form-group"><label>{{ __('airlineticketingnew::profiles.document_number') }}</label><input class="form-control" name="document_number"></div></div>
<div class="col-md-3"><div class="form-group"><label>{{ __('airlineticketingnew::profiles.issuing_country_code') }}</label><input class="form-control" name="issuing_country_code"></div></div>
<div class="col-md-3"><div class="form-group"><label>{{ __('airlineticketingnew::profiles.issued_date') }}</label><input class="form-control" name="issued_date"></div></div>
<div class="col-md-3"><div class="form-group"><label>{{ __('airlineticketingnew::profiles.expiry_date') }}</label><input class="form-control" name="expiry_date"></div></div>
<div class="col-md-3"><div class="form-group"><label>{{ __('airlineticketingnew::profiles.place_of_issue') }}</label><input class="form-control" name="place_of_issue"></div></div><div class="col-md-3"><label><input type="checkbox" name="is_active" value="1" checked> {{ __('airlineticketingnew::profiles.active') }}</label></div></div></div>
<div class="atn-panel-footer text-right"><button class="btn btn-primary">{{ __('airlineticketingnew::profiles.save') }}</button></div></form></div>
<div class="atn-panel"><div class="table-responsive"><table class="table table-bordered atn-table"><thead><tr><th>{{ __('airlineticketingnew::profiles.document_type') }}</th><th>{{ __('airlineticketingnew::profiles.document_number') }}</th><th>{{ __('airlineticketingnew::profiles.issuing_country_code') }}</th><th>{{ __('airlineticketingnew::profiles.issued_date') }}</th></tr></thead><tbody>
@forelse($records as $record)<tr><td>{{ $record->document_type }}</td><td>{{ $record->document_number }}</td><td>{{ $record->issuing_country_code }}</td><td>{{ $record->issued_date }}</td></tr>@empty<tr><td colspan="4" class="text-center">{{ __('airlineticketingnew::profiles.no_records') }}</td></tr>@endforelse
</tbody></table></div></div>
@endsection
